<?php

namespace Shellbox\ShellParser;

class SyntaxInfo {
	/** @var Node */
	private $root;

	/** @var string[]|null */
	private $featureList;

	/** @var string[]|null */
	private $literalArgv;

	public const LIST = 'list';
	public const BACKGROUND = 'background';
	public const PIPELINE = 'pipeline';
	public const COMPOUND = 'compound';
	public const REDIRECT = 'redirect';
	public const COMMAND_EXPANSION = 'command_expansion';
	public const PARAMETER = 'parameter';
	public const EXOTIC_EXPANSION = 'exotic_expansion';
	public const ASSIGNMENT = 'assignment';

	/**
	 * @internal Use SyntaxTree::getInfo()
	 *
	 * @param Node $root
	 */
	public function __construct( $root ) {
		$this->root = $root;
	}

	/**
	 * @var array Node types used to identify features. Note that features do
	 *   not need to be mutually exclusive.
	 */
	private static $nodeTypesByFeature = [
		'list' => [ 'list', 'and_if', 'or_if' ],
		'background' => [ 'background' ],
		'pipeline' => [ 'pipeline' ],
		'compound' => [
			'subshell',
			'for',
			'case',
			'if',
			'while',
			'until',
			'function_definition',
			'brace_group'
		],
		'redirect' => [ 'io_redirect' ],
		'command_expansion' => [ 'backquote', 'command_expansion' ],
		'parameter' => [ 'special_parameter', 'positional_parameter', 'named_parameter' ],
		'exotic_expansion' => [
			'use_default',
			'use_default_unset',
			'assign_default',
			'assign_default_unset',
			'indicate_error',
			'indicate_error_unset',
			'use_alternative',
			'use_alternative_unset',
			'remove_smallest_suffix',
			'remove_largest_suffix',
			'remove_smallest_prefix',
			'remove_largest_prefix',
			'string_length',
			'arithmetic_expansion',
			'braced_parameter_expansion'
		],
		'assignment' => [ 'assignment' ],
	];

	/**
	 * @var array Features by node type, compiled with compileFeaturesByNodeType().
	 */
	private static $featuresByNodeType = [
		'and_if' => [ 'list' ],
		'arithmetic_expansion' => [ 'exotic_expansion' ],
		'assign_default' => [ 'exotic_expansion' ],
		'assign_default_unset' => [ 'exotic_expansion' ],
		'assignment' => [ 'assignment' ],
		'background' => [ 'background' ],
		'backquote' => [ 'command_expansion' ],
		'brace_group' => [ 'compound' ],
		'braced_parameter_expansion' => [ 'exotic_expansion' ],
		'case' => [ 'compound' ],
		'command_expansion' => [ 'command_expansion' ],
		'for' => [ 'compound' ],
		'function_definition' => [ 'compound' ],
		'if' => [ 'compound' ],
		'indicate_error' => [ 'exotic_expansion' ],
		'indicate_error_unset' => [ 'exotic_expansion' ],
		'io_redirect' => [ 'redirect' ],
		'list' => [ 'list' ],
		'named_parameter' => [ 'parameter' ],
		'or_if' => [ 'list' ],
		'pipeline' => [ 'pipeline' ],
		'positional_parameter' => [ 'parameter' ],
		'remove_largest_prefix' => [ 'exotic_expansion' ],
		'remove_largest_suffix' => [ 'exotic_expansion' ],
		'remove_smallest_prefix' => [ 'exotic_expansion' ],
		'remove_smallest_suffix' => [ 'exotic_expansion' ],
		'special_parameter' => [ 'parameter' ],
		'string_length' => [ 'exotic_expansion' ],
		'subshell' => [ 'compound' ],
		'until' => [ 'compound' ],
		'use_alternative' => [ 'exotic_expansion' ],
		'use_alternative_unset' => [ 'exotic_expansion' ],
		'use_default' => [ 'exotic_expansion' ],
		'use_default_unset' => [ 'exotic_expansion' ],
		'while' => [ 'compound' ],
	];

	/**
	 * A function for use from a PHP CLI which inverts the $nodeTypesByFeature
	 * array to produce $featuresByNodeType.
	 *
	 * @return string
	 */
	public static function compileFeaturesByNodeType() {
		$featuresByNodeType = [];
		foreach ( self::$nodeTypesByFeature as $feature => $types ) {
			foreach ( $types as $type ) {
				$featuresByNodeType[$type][] = $feature;
			}
		}
		$s = '';
		ksort( $featuresByNodeType );
		foreach ( $featuresByNodeType as $type => $features ) {
			$s .= "\t'$type' => [ '" . implode( "', '", $features ) . "' ],\n";
		}
		return "private static \$featuresByNodeType = [\n$s];\n";
	}

	/**
	 * Get the features used in this shell program
	 *
	 * @return array
	 */
	public function getFeatureList() {
		if ( $this->featureList === null ) {
			$features = [];
			$this->root->traverse(
				static function ( $node ) use ( &$features ) {
					if ( $node instanceof Node ) {
						$newFeatures = self::$featuresByNodeType[$node->type] ?? [];
						foreach ( $newFeatures as $feature ) {
							$features[$feature] = true;
						}
					}
				}
			);
			$this->featureList = array_keys( $features );
		}
		return $this->featureList;
	}

	/**
	 * If the program is a single command and all of its arguments can be
	 * represented as string literals, return the unquoted literals. Otherwise,
	 * return null.
	 *
	 * @return string[]|null
	 */
	public function getLiteralArgv() {
		if ( $this->literalArgv !== null ) {
			return $this->literalArgv;
		}

		$argv = [];
		$node = $this->root;
		if ( $this->getNodeType( $node ) !== 'program' || $this->getChildCount( $node ) !== 1 ) {
			return null;
		}
		$node = $node->contents[0];
		if ( $this->getNodeType( $node ) !== 'complete_command' || $this->getChildCount( $node ) !== 1 ) {
			return null;
		}
		$node = $node->contents[0];
		if ( $this->getNodeType( $node ) !== 'simple_command' ) {
			return null;
		}
		foreach ( $this->getChildren( $node ) as $child ) {
			$type = $this->getNodeType( $child );
			if ( $type !== 'word' ) {
				continue;
			}
			$unquotedWord = $this->unquoteWord( $child );
			if ( $unquotedWord === null ) {
				return null;
			}
			$argv[] = $unquotedWord;
		}
		$this->literalArgv = $argv;
		return $argv;
	}

	/**
	 * @param string|array|Node $node
	 * @return string
	 */
	private function getNodeType( $node ) {
		if ( $node instanceof Node ) {
			return $node->type;
		} elseif ( is_array( $node ) ) {
			return 'array';
		} else {
			return 'string';
		}
	}

	/**
	 * @param string|array|Node $node
	 * @return array
	 */
	private function getChildren( $node ) {
		if ( $node instanceof Node ) {
			return $node->contents;
		} else {
			return [];
		}
	}

	/**
	 * @param string|array|Node $node
	 * @return int
	 */
	private function getChildCount( $node ) {
		return count( $this->getChildren( $node ) );
	}

	/**
	 * Remove quotes from a word node. If the word cannot be converted to a
	 * literal string, return null.
	 *
	 * @param Node $word
	 * @return string|null
	 */
	private function unquoteWord( Node $word ) {
		$unquotedWord = '';
		foreach ( $this->getChildren( $word ) as $part ) {
			$type = $this->getNodeType( $part );
			if ( $type === 'single_quote'
				|| $type === 'unquoted_literal'
				|| $type === 'bare_escape'
			) {
				if ( $this->getChildCount( $part ) !== 1 ) {
					return null;
				}
				$literalPart = $this->getChildren( $part )[0];
				if ( !is_string( $literalPart ) ) {
					return null;
				}
				$unquotedWord .= $literalPart;
			} elseif ( $type === 'double_quote' ) {
				$literalPart = $this->unquoteDoubleQuote( $part );
				if ( !is_string( $literalPart ) ) {
					return null;
				}
				$unquotedWord .= $literalPart;
			} else {
				return null;
			}
		}
		return $unquotedWord;
	}

	/**
	 * Remove quotes from a double-quote node. If it contains expansions, null
	 * will be returned.
	 *
	 * @param Node $dquote
	 * @return string|null
	 */
	private function unquoteDoubleQuote( Node $dquote ) {
		$unquoted = '';
		foreach ( $this->getChildren( $dquote ) as $part ) {
			$type = $this->getNodeType( $part );
			if ( $type === 'string' ) {
				$unquoted .= $part;
			} elseif ( $type === 'dquoted_escape' ) {
				if ( $this->getChildCount( $part ) !== 1 ) {
					return null;
				}
				$literalPart = $this->getChildren( $part )[0];
				if ( !is_string( $literalPart ) ) {
					return null;
				}
				$unquoted .= $literalPart;
			} else {
				return null;
			}
		}
		return $unquoted;
	}
}
