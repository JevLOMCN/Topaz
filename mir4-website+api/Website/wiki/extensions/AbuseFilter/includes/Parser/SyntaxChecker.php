<?php

namespace MediaWiki\Extension\AbuseFilter\Parser;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\InternalException;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;
use Message;

/**
 * SyntaxChecker statically analyzes the code without actually running it.
 * Currently, it only checks for
 *
 * - unbound variables
 * - unused variables: note that a := 1; a := 1; a
 *	 is considered OK even though the first `a` seems unused
 *	 because the pattern "a := null; if ... then (a := ...) end; ..."
 *	 should not count first `a` as unused.
 * - assignment to built-in identifiers
 * - invalid function call (arity mismatch, non-valid function)
 * - first-order information of `set_var` and `set`
 *
 * Because it doesn't cover all checks that the current Check Syntax does,
 * it is currently complementary to the current Check Syntax.
 * In the future, it could subsume the current Check Syntax, and could be
 * extended to perform type checking or type inference.
 */
class SyntaxChecker {
	/**
	 * @var AFPTreeNode|null Root of the AST to check
	 */
	private $treeRoot;

	/** @var KeywordsManager */
	private $keywordsManager;

	public const MCONSERVATIVE = 'MODE_CONSERVATIVE';
	public const MLIBERAL = 'MODE_LIBERAL';
	public const DUMMYPOS = 0;
	public const CACHE_VERSION = 1;

	/**
	 * @var string The mode of checking. The value should be either
	 *
	 *	 - MLIBERAL: which guarantees that all user-defined variables
	 *	   will be bound, but incompatible with what the evaluator currently
	 *	   permits. E.g.,
	 *
	 *	   if true then (a := 1) else null end; a
	 *
	 *	   is rejected in this mode, even though `a` is in fact always bound.
	 *
	 *	 - MCONSERVATIVE which is compatible with what the evaluator
	 *	   currently permits, but could allow undefined variables to occur.
	 *	   E.g.,
	 *
	 *	   if false then (a := 1) else null end; a
	 *
	 *	   is accepted in this mode, even though `a` is in fact always unbound.
	 */
	private $mode;

	/**
	 * @var bool Whether we want to check for unused variables
	 */
	private $checkUnusedVars;

	/**
	 * @param AFPSyntaxTree $tree
	 * @param KeywordsManager $keywordsManager
	 * @param string $mode
	 * @param bool $checkUnusedVars
	 */
	public function __construct(
		AFPSyntaxTree $tree,
		KeywordsManager $keywordsManager,
		string $mode = self::MCONSERVATIVE,
		bool $checkUnusedVars = false
	) {
		$this->treeRoot = $tree->getRoot();
		$this->keywordsManager = $keywordsManager;
		$this->mode = $mode;
		$this->checkUnusedVars = $checkUnusedVars;
	}

	/**
	 * Start the static analysis
	 *
	 * @throws UserVisibleException
	 */
	public function start(): void {
		if ( !$this->treeRoot ) {
			return;
		}
		$bound = $this->check( $this->desugar( $this->treeRoot ), [] );
		$unused = array_keys( array_filter( $bound, static function ( $v ) {
			return !$v;
		} ) );
		if ( $this->checkUnusedVars && $unused ) {
			throw new UserVisibleException(
				'unusedvars',
				self::DUMMYPOS,
				[ Message::listParam( $unused, 'comma' ) ]
			);
		}
	}

	/**
	 * Remove syntactic sugar so that we don't need to deal with
	 * too many cases.
	 *
	 * This could benefit the evaluator as well, but for now, this is
	 * only used for static analysis.
	 *
	 * Postcondition:
	 *	 - The tree will not contain nodes of
	 *	   type ASSIGNMENT, LOGIC, COMPARE, SUM_REL, MUL_REL, POW,
	 *	   KEYWORD_OPERATOR, and ARRAY_INDEX
	 *	 - The tree may additionally contain a node of type BINOP.
	 *	 - The tree should not have set_var function application.
	 *	 - Conditionals will have both branches.
	 *
	 * @param AFPTreeNode $node
	 * @return AFPTreeNode
	 * @throws InternalException
	 */
	private function desugar( AFPTreeNode $node ): AFPTreeNode {
		switch ( $node->type ) {
			case AFPTreeNode::ATOM:
				return $node;

			case AFPTreeNode::FUNCTION_CALL:
				if ( $node->children[0] === 'set_var' ) {
					$node->children[0] = 'set';
				}
				return $this->newNodeMapExceptFirst( $node );

			case AFPTreeNode::ARRAY_INDEX:
				return $this->newNodeNamedBinop( $node, '[]' );

			case AFPTreeNode::POW:
				return $this->newNodeNamedBinop( $node, '**' );

			case AFPTreeNode::UNARY:
			case AFPTreeNode::INDEX_ASSIGNMENT:
			case AFPTreeNode::ARRAY_APPEND:
				return $this->newNodeMapExceptFirst( $node );

			case AFPTreeNode::BOOL_INVERT:
				/*
				 * @todo this should really be combined with UNARY,
				 * but let's wait to change the meaning of UNARY across
				 * the codebase together
				 */
				return $this->newNodeMapAll( $node );

			case AFPTreeNode::KEYWORD_OPERATOR:
			case AFPTreeNode::MUL_REL:
			case AFPTreeNode::SUM_REL:
			case AFPTreeNode::COMPARE:
				return $this->newNodeBinop( $node );

			case AFPTreeNode::LOGIC:
				$result = $this->newNodeBinop( $node );
				[ $op, $left, $right ] = $result->children;
				if ( $op === '&' || $op === '|' ) {
					return $this->desugarAndOr( $op, $left, $right, $node->position );
				} else {
					return $result;
				}

			case AFPTreeNode::ARRAY_DEFINITION:
			case AFPTreeNode::SEMICOLON:
				return $this->newNodeMapAll( $node );

			case AFPTreeNode::CONDITIONAL:
				if ( $node->children[2] === null ) {
					$node->children[2] = new AFPTreeNode(
						AFPTreeNode::ATOM,
						new AFPToken(
							AFPToken::TKEYWORD,
							"null",
							$node->position
						),
						$node->position
					);
				}
				return $this->newNodeMapAll( $node );

			case AFPTreeNode::ASSIGNMENT:
				[ $varname, $value ] = $node->children;

				return new AFPTreeNode(
					AFPTreeNode::FUNCTION_CALL,
					[
						"set",
						new AFPTreeNode(
							AFPTreeNode::ATOM,
							new AFPToken(
								AFPToken::TSTRING,
								$varname,
								$node->position
							),
							$node->position
						),
						$this->desugar( $value )
					],
					$node->position
				);

			default:
				// @codeCoverageIgnoreStart
				throw new InternalException( "Unknown node type passed: {$node->type}" );
				// @codeCoverageIgnoreEnd
		}
	}

	/**
	 * @param string $op
	 * @param AFPTreeNode $left
	 * @param AFPTreeNode $right
	 * @param int $position
	 * @return AFPTreeNode
	 */
	private function desugarAndOr(
		string $op,
		AFPTreeNode $left,
		AFPTreeNode $right,
		int $position
	): AFPTreeNode {
		$trueNode = new AFPTreeNode(
			AFPTreeNode::ATOM,
			new AFPToken(
				AFPToken::TKEYWORD,
				"true",
				$position
			),
			$position
		);
		$falseNode = new AFPTreeNode(
			AFPTreeNode::ATOM,
			new AFPToken(
				AFPToken::TKEYWORD,
				"false",
				$position
			),
			$position
		);
		$conditionalNode = new AFPTreeNode(
			AFPTreeNode::CONDITIONAL,
			[
				$right,
				$trueNode,
				$falseNode
			],
			$position
		);

		if ( $op === '&' ) {
			// <a> & <b> is supposed to be equivalent to
			// if <a> then (if <b> then true else false) else false end
			// See T237336 for why this is currently not the case.
			return new AFPTreeNode(
				AFPTreeNode::CONDITIONAL,
				[
					$left,
					$conditionalNode,
					$falseNode
				],
				$position
			);
		} elseif ( $op === '|' ) {
			// <a> | <b> is supposed to be equivalent to
			// if <a> then true else (if <b> then true else false) end
			// See T237336 for why this is currently not the case.
			return new AFPTreeNode(
				AFPTreeNode::CONDITIONAL,
				[
					$left,
					$trueNode,
					$conditionalNode
				],
				$position
			);
		} else {
			// @codeCoverageIgnoreStart
			throw new InternalException( "Unknown operator: {$op}" );
			// @codeCoverageIgnoreEnd
		}
	}

	/**
	 * Construct a new node with information based on the old node but
	 * with different children
	 *
	 * @param AFPTreeNode $node
	 * @param AFPTreeNode[]|string[]|AFPToken $children
	 * @return AFPTreeNode
	 */
	private function newNode( AFPTreeNode $node, $children ): AFPTreeNode {
		return new AFPTreeNode( $node->type, $children, $node->position );
	}

	/**
	 * Construct a new node with information based on the old node but
	 * with different type
	 *
	 * @param AFPTreeNode $node
	 * @param string $type
	 * @return AFPTreeNode
	 */
	private function newNodeReplaceType(
		AFPTreeNode $node,
		string $type
	): AFPTreeNode {
		return new AFPTreeNode( $type, $node->children, $node->position );
	}

	/**
	 * Recursively desugar on all children
	 *
	 * @param AFPTreeNode $node
	 * @return AFPTreeNode
	 */
	private function newNodeMapAll( AFPTreeNode $node ): AFPTreeNode {
		$children = $node->children;
		if ( !is_array( $children ) ) {
			// @codeCoverageIgnoreStart
			throw new LogicException(
				"Unexpected non-array children of an AFPTreeNode of type " .
				"{$node->type} at position {$node->position}"
			);
			// @codeCoverageIgnoreEnd
		}
		return $this->newNode( $node, array_map( [ $this, 'desugar' ], $children ) );
	}

	/**
	 * Recursively desugar on all children except the first one
	 *
	 * @param AFPTreeNode $node
	 * @return AFPTreeNode
	 */
	private function newNodeMapExceptFirst( AFPTreeNode $node ): AFPTreeNode {
		$items = [ $node->children[0] ];
		$args = array_slice( $node->children, 1 );
		foreach ( $args as $el ) {
			$items[] = $this->desugar( $el );
		}
		return $this->newNode( $node, $items );
	}

	/**
	 * Convert a node with an operation into a BINOP
	 *
	 * @param AFPTreeNode $node
	 * @return AFPTreeNode
	 */
	private function newNodeBinop( AFPTreeNode $node ): AFPTreeNode {
		return $this->newNodeReplaceType(
			$this->newNodeMapExceptFirst( $node ),
			AFPTreeNode::BINOP
		);
	}

	/**
	 * Convert a node without an operation into a BINOP with the specified operation
	 *
	 * @param AFPTreeNode $node
	 * @param string $op
	 * @return AFPTreeNode
	 */
	private function newNodeNamedBinop(
		AFPTreeNode $node,
		string $op
	): AFPTreeNode {
		$items = $this->newNodeMapAll( $node )->children;
		array_unshift( $items, $op );
		return $this->newNodeReplaceType(
			$this->newNode( $node, $items ),
			AFPTreeNode::BINOP
		);
	}

	/**
	 * - Statically compute what are bound after evaluating $node,
	 *	 provided that variables in $bound are already bound.
	 * - Similarly compute for each bound variable after evaluating $node
	 *	 whether it is used provided that we already have $bound
	 *	 that contains necessary information.
	 * - Ensure function application's validity.
	 * - Ensure that the first argument of set is a literal string.
	 * - Ensure that all assignment is not done on built-in identifier.
	 *
	 * Precondition:
	 *	 - The tree $node should be desugared and normalized.
	 *
	 * Postcondition:
	 *	 - $node is guaranteed to have no unbound variables
	 *	   provided that variables in $bound are already bound
	 *	   (for the definition of unbound variable indicated by $this->mode)
	 *	 - All function applications should be valid and have correct arity.
	 *	 - The set function application's first argument should be
	 *	   a literal string.
	 *
	 * @param AFPTreeNode $node
	 * @param bool[] $bound Map of [ variable_name => used ]
	 * @return bool[] Map of [ variable_name => used ]
	 * @throws UserVisibleException
	 * @throws InternalException
	 */
	private function check( AFPTreeNode $node, array $bound ): array {
		switch ( $node->type ) {
			// phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
			case AFPTreeNode::ATOM:
				$tok = $node->children;
				switch ( $tok->type ) {
					case AFPToken::TID:
						return $this->lookupVar(
							$tok->value,
							$tok->pos,
							$bound
						);

					case AFPToken::TSTRING:
					case AFPToken::TFLOAT:
					case AFPToken::TINT:
					case AFPToken::TKEYWORD:
						return $bound;

					default:
						// @codeCoverageIgnoreStart
						throw new InternalException( "Unknown token {$tok->type} provided in the ATOM node" );
						// @codeCoverageIgnoreEnd
				}
			case AFPTreeNode::ARRAY_DEFINITION:
				// @phan-suppress-next-line PhanTypeSuspiciousNonTraversableForeach children is array here
				foreach ( $node->children as $el ) {
					$bound = $this->check( $el, $bound );
				}
				return $bound;

			case AFPTreeNode::FUNCTION_CALL:
				$fname = $node->children[0];
				$args = array_slice( $node->children, 1 );
				if ( !array_key_exists( $fname, FilterEvaluator::FUNCTIONS ) ) {
					throw new UserVisibleException(
						'unknownfunction',
						$node->position,
						[ $fname ]
					);
				}
				$this->checkArgCount( $args, $fname, $node->position );

				if ( $fname === 'set' ) {
					// arity is checked, so we know $args[0] and $args[1] exist
					$tok = $args[0]->children;

					if (
						!( $tok instanceof AFPToken ) ||
						$tok->type !== AFPToken::TSTRING
					) {
						throw new UserVisibleException(
							'variablevariable',
							$node->position,
							[]
						);
					}

					$bound = $this->check( $args[1], $bound );
					// set the variable as unused
					return $this->assignVar(
						$tok->value,
						$tok->pos,
						$bound
					);
				} else {
					foreach ( $args as $arg ) {
						$bound = $this->check( $arg, $bound );
					}
					return $bound;
				}

			case AFPTreeNode::BINOP:
				[ , $left, $right ] = $node->children;
				return $this->check( $right, $this->check( $left, $bound ) );

			case AFPTreeNode::UNARY:
				[ , $argument ] = $node->children;
				return $this->check( $argument, $bound );

			case AFPTreeNode::BOOL_INVERT:
				[ $argument ] = $node->children;
				return $this->check( $argument, $bound );
			// phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
			case AFPTreeNode::CONDITIONAL:
				[ $condition, $exprIfTrue, $exprIfFalse ] = $node->children;
				$bound = $this->check( $condition, $bound );
				$boundLeft = $this->check( $exprIfTrue, $bound );
				$boundRight = $this->check( $exprIfFalse, $bound );
				switch ( $this->mode ) {
					case self::MCONSERVATIVE:
						return $this->mapUnion( $boundLeft, $boundRight );
					case self::MLIBERAL:
						return $this->mapIntersect( $boundLeft, $boundRight );
					default:
						// @codeCoverageIgnoreStart
						throw new LogicException( "Unknown mode: {$this->mode}" );
						// @codeCoverageIgnoreEnd
				}

			case AFPTreeNode::INDEX_ASSIGNMENT:
				[ $varName, $offset, $value ] = $node->children;

				// deal with unbound $varName
				$bound = $this->lookupVar( $varName, $node->position, $bound );
				$bound = $this->check( $offset, $bound );
				$bound = $this->check( $value, $bound );
				// deal with built-in $varName and set $varName as unused
				return $this->assignVar( $varName, $node->position, $bound );

			case AFPTreeNode::ARRAY_APPEND:
				[ $varName, $value ] = $node->children;

				// deal with unbound $varName
				$bound = $this->lookupVar( $varName, $node->position, $bound );
				$bound = $this->check( $value, $bound );
				// deal with built-in $varName and set $varName as unused
				return $this->assignVar( $varName, $node->position, $bound );

			case AFPTreeNode::SEMICOLON:
				// @phan-suppress-next-line PhanTypeSuspiciousNonTraversableForeach children is array here
				foreach ( $node->children as $statement ) {
					$bound = $this->check( $statement, $bound );
				}
				return $bound;

			default:
				// @codeCoverageIgnoreStart
				throw new LogicException( "Unknown type: {$node->type}" );
				// @codeCoverageIgnoreEnd
		}
	}

	/**
	 * @param array $left
	 * @param array $right
	 * @return array
	 */
	private function mapUnion( array $left, array $right ): array {
		foreach ( $right as $key => $val ) {
			if ( array_key_exists( $key, $left ) ) {
				$left[ $key ] = $left[ $key ] || $val;
			} else {
				$left[ $key ] = $val;
			}
		}
		return $left;
	}

	/**
	 * @param array $left
	 * @param array $right
	 * @return array
	 */
	private function mapIntersect( array $left, array $right ): array {
		$keys = array_intersect_key( $left, $right );
		$result = [];
		foreach ( $keys as $key => $val ) {
			$result[ $key ] = $left[ $key ] || $right[ $key ];
		}
		return $result;
	}

	/**
	 * @param string $var
	 * @param int $pos
	 * @param array $bound
	 * @return array
	 */
	private function assignVar( string $var, int $pos, array $bound ): array {
		$var = strtolower( $var );
		if ( $this->isReservedIdentifier( $var ) ) {
			throw new UserVisibleException(
				'overridebuiltin',
				$pos,
				[ $var ]
			);
		}
		$bound[ $var ] = false;
		return $bound;
	}

	/**
	 * @param string $var
	 * @param int $pos
	 * @param array $bound
	 * @return array
	 */
	private function lookupVar( string $var, int $pos, array $bound ): array {
		$var = strtolower( $var );
		if ( array_key_exists( $var, $bound ) ) {
			// user-defined variable
			$bound[ $var ] = true;
			return $bound;
		} elseif ( $this->keywordsManager->isVarDisabled( $var ) ) {
			// disabled built-in variables
			throw new UserVisibleException(
				'disabledvar',
				$pos,
				[ $var ]
			);
		} elseif ( $this->keywordsManager->varExists( $var ) ) {
			// non-disabled built-in variables
			return $bound;
		} elseif ( $this->isReservedIdentifier( $var ) ) {
			// other built-in identifiers
			throw new UserVisibleException(
				'usebuiltin',
				$pos,
				[ $var ]
			);
		} else {
			// unbound variables
			throw new UserVisibleException(
				'unrecognisedvar',
				$pos,
				[ $var ]
			);
		}
	}

	/**
	 * Check that a built-in function has been provided the right amount of arguments
	 *
	 * @param array $args The arguments supplied to the function
	 * @param string $func The function name
	 * @param int $position
	 * @throws UserVisibleException
	 */
	private function checkArgCount( array $args, string $func, int $position ): void {
		if ( !array_key_exists( $func, FilterEvaluator::FUNC_ARG_COUNT ) ) {
			// @codeCoverageIgnoreStart
			throw new InvalidArgumentException( "$func is not a valid function." );
			// @codeCoverageIgnoreEnd
		}
		[ $min, $max ] = FilterEvaluator::FUNC_ARG_COUNT[ $func ];
		if ( count( $args ) < $min ) {
			throw new UserVisibleException(
				$min === 1 ? 'noparams' : 'notenoughargs',
				$position,
				[ $func, $min, count( $args ) ]
			);
		} elseif ( count( $args ) > $max ) {
			throw new UserVisibleException(
				'toomanyargs',
				$position,
				[ $func, $max, count( $args ) ]
			);
		}
	}

	/**
	 * Check whether the given name is a reserved identifier, e.g. the name of a built-in variable,
	 * function, or keyword.
	 *
	 * @param string $name
	 * @return bool
	 */
	private function isReservedIdentifier( string $name ): bool {
		return $this->keywordsManager->varExists( $name ) ||
			array_key_exists( $name, FilterEvaluator::FUNCTIONS ) ||
			// We need to check for true, false, if/then/else etc. because, even if they have a different
			// AFPToken type, they may be used inside set/set_var()
			in_array( $name, AbuseFilterTokenizer::KEYWORDS, true );
	}
}
