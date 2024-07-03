<?php

/**
 * A version of the abuse filter parser that separates parsing the filter and
 * evaluating it into different passes, allowing the parse tree to be cached.
 *
 * @file
 * @phan-file-suppress PhanPossiblyInfiniteRecursionSameParams Recursion controlled by class props
 */

namespace MediaWiki\Extension\AbuseFilter\Parser;

use IBufferingStatsdDataFactory;
use InvalidArgumentException;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;
use Psr\Log\LoggerInterface;

/**
 * A parser that transforms the text of the filter into a parse tree.
 */
class AFPTreeParser {
	/**
	 * @var array[] Contains the AFPTokens for the code being parsed
	 * @phan-var array<int,array{0:AFPToken,1:int}>
	 */
	private $mTokens;
	/**
	 * @var AFPToken The current token
	 */
	private $mCur;
	/** @var int The position of the current token */
	private $mPos;

	/**
	 * @var string|null The ID of the filter being parsed, if available. Can also be "global-$ID"
	 */
	private $mFilter;

	public const CACHE_VERSION = 2;

	/**
	 * @var LoggerInterface Used for debugging
	 */
	private $logger;

	/**
	 * @var IBufferingStatsdDataFactory
	 */
	private $statsd;

	/** @var KeywordsManager */
	private $keywordsManager;

	/**
	 * @param LoggerInterface $logger Used for debugging
	 * @param IBufferingStatsdDataFactory $statsd
	 * @param KeywordsManager $keywordsManager
	 */
	public function __construct(
		LoggerInterface $logger,
		IBufferingStatsdDataFactory $statsd,
		KeywordsManager $keywordsManager
	) {
		$this->logger = $logger;
		$this->statsd = $statsd;
		$this->keywordsManager = $keywordsManager;
		$this->resetState();
	}

	/**
	 * @param string $filter
	 */
	public function setFilter( $filter ) {
		$this->mFilter = $filter;
	}

	/**
	 * Resets the state
	 */
	private function resetState() {
		$this->mTokens = [];
		$this->mPos = 0;
		$this->mFilter = null;
	}

	/**
	 * Advances the parser to the next token in the filter code.
	 */
	private function move() {
		[ $this->mCur, $this->mPos ] = $this->mTokens[$this->mPos];
	}

	/**
	 * Get the next token. This is similar to move() but doesn't change class members,
	 *   allowing to look ahead without rolling back the state.
	 *
	 * @return AFPToken
	 */
	private function getNextToken() {
		return $this->mTokens[$this->mPos][0];
	}

	/**
	 * getState() function allows parser state to be rollbacked to several tokens
	 * back.
	 *
	 * @return AFPParserState
	 */
	private function getState() {
		return new AFPParserState( $this->mCur, $this->mPos );
	}

	/**
	 * setState() function allows parser state to be rollbacked to several tokens
	 * back.
	 *
	 * @param AFPParserState $state
	 */
	private function setState( AFPParserState $state ) {
		$this->mCur = $state->token;
		$this->mPos = $state->pos;
	}

	/**
	 * Parse the supplied filter source code into a tree.
	 *
	 * @param array[] $tokens
	 * @phan-param array<int,array{0:AFPToken,1:int}> $tokens
	 * @return AFPSyntaxTree
	 * @throws UserVisibleException
	 */
	public function parse( array $tokens ): AFPSyntaxTree {
		$this->mTokens = $tokens;
		$this->mPos = 0;

		return $this->buildSyntaxTree();
	}

	/**
	 * @return AFPSyntaxTree
	 */
	private function buildSyntaxTree(): AFPSyntaxTree {
		$startTime = microtime( true );
		$root = $this->doLevelEntry();
		$this->statsd->timing( 'abusefilter_cachingParser_buildtree', microtime( true ) - $startTime );
		return new AFPSyntaxTree( $root );
	}

	/* Levels */

	/**
	 * Handles unexpected characters after the expression.
	 * @return AFPTreeNode|null Null only if no statements
	 * @throws UserVisibleException
	 */
	private function doLevelEntry() {
		$result = $this->doLevelSemicolon();

		if ( $this->mCur->type !== AFPToken::TNONE ) {
			throw new UserVisibleException(
				'unexpectedatend',
				$this->mPos, [ $this->mCur->type ]
			);
		}

		return $result;
	}

	/**
	 * Handles the semicolon operator.
	 *
	 * @return AFPTreeNode|null
	 */
	private function doLevelSemicolon() {
		$statements = [];

		do {
			$this->move();
			$position = $this->mPos;

			if (
				$this->mCur->type === AFPToken::TNONE ||
				( $this->mCur->type === AFPToken::TBRACE && $this->mCur->value == ')' )
			) {
				// Handle special cases which the other parser handled in doLevelAtom
				break;
			}

			// Allow empty statements.
			if ( $this->mCur->type === AFPToken::TSTATEMENTSEPARATOR ) {
				continue;
			}

			$statements[] = $this->doLevelSet();
			$position = $this->mPos;
		} while ( $this->mCur->type === AFPToken::TSTATEMENTSEPARATOR );

		// Flatten the tree if possible.
		if ( count( $statements ) === 0 ) {
			return null;
		} elseif ( count( $statements ) === 1 ) {
			return $statements[0];
		} else {
			return new AFPTreeNode( AFPTreeNode::SEMICOLON, $statements, $position );
		}
	}

	/**
	 * Handles variable assignment.
	 *
	 * @return AFPTreeNode
	 * @throws UserVisibleException
	 */
	private function doLevelSet() {
		if ( $this->mCur->type === AFPToken::TID ) {
			$varname = (string)$this->mCur->value;

			// Speculatively parse the assignment statement assuming it can
			// potentially be an assignment, but roll back if it isn't.
			// @todo Use $this->getNextToken for clearer code
			$initialState = $this->getState();
			$this->move();

			if ( $this->mCur->type === AFPToken::TOP && $this->mCur->value === ':=' ) {
				$position = $this->mPos;
				$this->move();
				$value = $this->doLevelSet();

				return new AFPTreeNode( AFPTreeNode::ASSIGNMENT, [ $varname, $value ], $position );
			}

			if ( $this->mCur->type === AFPToken::TSQUAREBRACKET && $this->mCur->value === '[' ) {
				$this->move();

				if ( $this->mCur->type === AFPToken::TSQUAREBRACKET && $this->mCur->value === ']' ) {
					$index = 'append';
				} else {
					// Parse index offset.
					$this->setState( $initialState );
					$this->move();
					$index = $this->doLevelSemicolon();
					if ( !( $this->mCur->type === AFPToken::TSQUAREBRACKET && $this->mCur->value === ']' ) ) {
						throw new UserVisibleException( 'expectednotfound', $this->mPos,
							[ ']', $this->mCur->type, $this->mCur->value ] );
					}
				}

				$this->move();
				if ( $this->mCur->type === AFPToken::TOP && $this->mCur->value === ':=' ) {
					$position = $this->mPos;
					$this->move();
					$value = $this->doLevelSet();
					if ( $index === 'append' ) {
						return new AFPTreeNode(
							AFPTreeNode::ARRAY_APPEND, [ $varname, $value ], $position );
					} else {
						return new AFPTreeNode(
							AFPTreeNode::INDEX_ASSIGNMENT,
							[ $varname, $index, $value ],
							$position
						);
					}
				}
			}

			// If we reached this point, we did not find an assignment.  Roll back
			// and assume this was just a literal.
			$this->setState( $initialState );
		}

		return $this->doLevelConditions();
	}

	/**
	 * Handles ternary operator and if-then-else-end.
	 *
	 * @return AFPTreeNode
	 * @throws UserVisibleException
	 */
	private function doLevelConditions() {
		if ( $this->mCur->type === AFPToken::TKEYWORD && $this->mCur->value === 'if' ) {
			$position = $this->mPos;
			$this->move();
			$condition = $this->doLevelBoolOps();

			if ( !( $this->mCur->type === AFPToken::TKEYWORD && $this->mCur->value === 'then' ) ) {
				throw new UserVisibleException( 'expectednotfound',
					$this->mPos,
					[
						'then',
						$this->mCur->type,
						$this->mCur->value
					]
				);
			}
			$this->move();

			$valueIfTrue = $this->doLevelConditions();

			if ( $this->mCur->type === AFPToken::TKEYWORD && $this->mCur->value === 'else' ) {
				$this->move();
				$valueIfFalse = $this->doLevelConditions();
			} else {
				$valueIfFalse = null;
			}

			if ( !( $this->mCur->type === AFPToken::TKEYWORD && $this->mCur->value === 'end' ) ) {
				throw new UserVisibleException( 'expectednotfound',
					$this->mPos,
					[
						'end',
						$this->mCur->type,
						$this->mCur->value
					]
				);
			}
			$this->move();

			return new AFPTreeNode(
				AFPTreeNode::CONDITIONAL,
				[ $condition, $valueIfTrue, $valueIfFalse ],
				$position
			);
		}

		$condition = $this->doLevelBoolOps();
		if ( $this->mCur->type === AFPToken::TOP && $this->mCur->value === '?' ) {
			$position = $this->mPos;
			$this->move();

			$valueIfTrue = $this->doLevelConditions();
			if ( !( $this->mCur->type === AFPToken::TOP && $this->mCur->value === ':' ) ) {
				throw new UserVisibleException( 'expectednotfound',
					$this->mPos,
					[
						':',
						$this->mCur->type,
						$this->mCur->value
					]
				);
			}
			$this->move();

			$valueIfFalse = $this->doLevelConditions();
			return new AFPTreeNode(
				AFPTreeNode::CONDITIONAL,
				[ $condition, $valueIfTrue, $valueIfFalse ],
				$position
			);
		}

		return $condition;
	}

	/**
	 * Handles logic operators.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelBoolOps() {
		$leftOperand = $this->doLevelCompares();
		$ops = [ '&', '|', '^' ];
		while ( $this->mCur->type === AFPToken::TOP && in_array( $this->mCur->value, $ops ) ) {
			$op = $this->mCur->value;
			$position = $this->mPos;
			$this->move();

			$rightOperand = $this->doLevelCompares();

			$leftOperand = new AFPTreeNode(
				AFPTreeNode::LOGIC,
				[ $op, $leftOperand, $rightOperand ],
				$position
			);
		}
		return $leftOperand;
	}

	/**
	 * Handles comparison operators.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelCompares() {
		$leftOperand = $this->doLevelSumRels();
		$equalityOps = [ '==', '===', '!=', '!==', '=' ];
		$orderOps = [ '<', '>', '<=', '>=' ];
		// Only allow either a single operation, or a combination of a single equalityOps and a single
		// orderOps. This resembles what PHP does, and allows `a < b == c` while rejecting `a < b < c`
		$allowedOps = array_merge( $equalityOps, $orderOps );
		while ( $this->mCur->type === AFPToken::TOP && in_array( $this->mCur->value, $allowedOps ) ) {
			$op = $this->mCur->value;
			$allowedOps = in_array( $op, $equalityOps ) ?
				array_diff( $allowedOps, $equalityOps ) :
				array_diff( $allowedOps, $orderOps );
			$position = $this->mPos;
			$this->move();
			$rightOperand = $this->doLevelSumRels();
			$leftOperand = new AFPTreeNode(
				AFPTreeNode::COMPARE,
				[ $op, $leftOperand, $rightOperand ],
				$position
			);
		}
		return $leftOperand;
	}

	/**
	 * Handle addition and subtraction.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelSumRels() {
		$leftOperand = $this->doLevelMulRels();
		$ops = [ '+', '-' ];
		while ( $this->mCur->type === AFPToken::TOP && in_array( $this->mCur->value, $ops ) ) {
			$op = $this->mCur->value;
			$position = $this->mPos;
			$this->move();
			$rightOperand = $this->doLevelMulRels();
			$leftOperand = new AFPTreeNode(
				AFPTreeNode::SUM_REL,
				[ $op, $leftOperand, $rightOperand ],
				$position
			);
		}
		return $leftOperand;
	}

	/**
	 * Handles multiplication and division.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelMulRels() {
		$leftOperand = $this->doLevelPow();
		$ops = [ '*', '/', '%' ];
		while ( $this->mCur->type === AFPToken::TOP && in_array( $this->mCur->value, $ops ) ) {
			$op = $this->mCur->value;
			$position = $this->mPos;
			$this->move();
			$rightOperand = $this->doLevelPow();
			$leftOperand = new AFPTreeNode(
				AFPTreeNode::MUL_REL,
				[ $op, $leftOperand, $rightOperand ],
				$position
			);
		}
		return $leftOperand;
	}

	/**
	 * Handles exponentiation.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelPow() {
		$base = $this->doLevelBoolInvert();
		while ( $this->mCur->type === AFPToken::TOP && $this->mCur->value === '**' ) {
			$position = $this->mPos;
			$this->move();
			$exponent = $this->doLevelBoolInvert();
			$base = new AFPTreeNode( AFPTreeNode::POW, [ $base, $exponent ], $position );
		}
		return $base;
	}

	/**
	 * Handles boolean inversion.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelBoolInvert() {
		if ( $this->mCur->type === AFPToken::TOP && $this->mCur->value === '!' ) {
			$position = $this->mPos;
			$this->move();
			$argument = $this->doLevelKeywordOperators();
			return new AFPTreeNode( AFPTreeNode::BOOL_INVERT, [ $argument ], $position );
		}

		return $this->doLevelKeywordOperators();
	}

	/**
	 * Handles keyword operators.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelKeywordOperators() {
		$leftOperand = $this->doLevelUnarys();
		$keyword = strtolower( $this->mCur->value );
		if ( $this->mCur->type === AFPToken::TKEYWORD &&
			isset( FilterEvaluator::KEYWORDS[$keyword] )
		) {
			$position = $this->mPos;
			$this->move();
			$rightOperand = $this->doLevelUnarys();

			return new AFPTreeNode(
				AFPTreeNode::KEYWORD_OPERATOR,
				[ $keyword, $leftOperand, $rightOperand ],
				$position
			);
		}

		return $leftOperand;
	}

	/**
	 * Handles unary operators.
	 *
	 * @return AFPTreeNode
	 */
	private function doLevelUnarys() {
		$op = $this->mCur->value;
		if ( $this->mCur->type === AFPToken::TOP && ( $op === "+" || $op === "-" ) ) {
			$position = $this->mPos;
			$this->move();
			$argument = $this->doLevelArrayElements();
			return new AFPTreeNode( AFPTreeNode::UNARY, [ $op, $argument ], $position );
		}
		return $this->doLevelArrayElements();
	}

	/**
	 * Handles accessing an array element by an offset.
	 *
	 * @return AFPTreeNode
	 * @throws UserVisibleException
	 */
	private function doLevelArrayElements() {
		$array = $this->doLevelParenthesis();
		while ( $this->mCur->type === AFPToken::TSQUAREBRACKET && $this->mCur->value === '[' ) {
			$position = $this->mPos;
			$index = $this->doLevelSemicolon();
			$array = new AFPTreeNode( AFPTreeNode::ARRAY_INDEX, [ $array, $index ], $position );

			if ( !( $this->mCur->type === AFPToken::TSQUAREBRACKET && $this->mCur->value === ']' ) ) {
				throw new UserVisibleException( 'expectednotfound', $this->mPos,
					[ ']', $this->mCur->type, $this->mCur->value ] );
			}
			$this->move();
		}

		return $array;
	}

	/**
	 * Handles parenthesis.
	 *
	 * @return AFPTreeNode
	 * @throws UserVisibleException
	 */
	private function doLevelParenthesis() {
		if ( $this->mCur->type === AFPToken::TBRACE && $this->mCur->value === '(' ) {
			$next = $this->getNextToken();
			if ( $next->type === AFPToken::TBRACE && $next->value === ')' ) {
				// Empty parentheses are never allowed
				throw new UserVisibleException(
					'unexpectedtoken',
					$this->mPos,
					[
						$this->mCur->type,
						$this->mCur->value
					]
				);
			}
			$result = $this->doLevelSemicolon();

			if ( !( $this->mCur->type === AFPToken::TBRACE && $this->mCur->value === ')' ) ) {
				throw new UserVisibleException(
					'expectednotfound',
					$this->mPos,
					[ ')', $this->mCur->type, $this->mCur->value ]
				);
			}
			$this->move();

			return $result;
		}

		return $this->doLevelFunction();
	}

	/**
	 * Handles function calls.
	 *
	 * @return AFPTreeNode
	 * @throws UserVisibleException
	 */
	private function doLevelFunction() {
		$next = $this->getNextToken();
		if ( $this->mCur->type === AFPToken::TID &&
			$next->type === AFPToken::TBRACE &&
			$next->value === '('
		) {
			$func = $this->mCur->value;
			$position = $this->mPos;
			$this->move();

			$args = [];
			$next = $this->getNextToken();
			if ( $next->type !== AFPToken::TBRACE || $next->value !== ')' ) {
				do {
					$thisArg = $this->doLevelSemicolon();
					if ( $thisArg !== null ) {
						$args[] = $thisArg;
					} elseif ( !$this->functionIsVariadic( $func ) ) {
						throw new UserVisibleException(
							'unexpectedtoken',
							$this->mPos,
							[
								$this->mCur->type,
								$this->mCur->value
							]
						);
					}
				} while ( $this->mCur->type === AFPToken::TCOMMA );
			} else {
				$this->move();
			}

			if ( $this->mCur->type !== AFPToken::TBRACE || $this->mCur->value !== ')' ) {
				throw new UserVisibleException( 'expectednotfound',
					$this->mPos,
					[
						')',
						$this->mCur->type,
						$this->mCur->value
					]
				);
			}
			$this->move();

			array_unshift( $args, $func );
			return new AFPTreeNode( AFPTreeNode::FUNCTION_CALL, $args, $position );
		}

		return $this->doLevelAtom();
	}

	/**
	 * Handle literals.
	 * @return AFPTreeNode
	 * @throws UserVisibleException
	 */
	private function doLevelAtom() {
		$tok = $this->mCur->value;
		switch ( $this->mCur->type ) {
			case AFPToken::TID:
				$this->checkLogDeprecatedVar( strtolower( $tok ) );
				// Fallthrough intended
			case AFPToken::TSTRING:
			case AFPToken::TFLOAT:
			case AFPToken::TINT:
				$result = new AFPTreeNode( AFPTreeNode::ATOM, $this->mCur, $this->mPos );
				break;
			case AFPToken::TKEYWORD:
				if ( in_array( $tok, [ "true", "false", "null" ] ) ) {
					$result = new AFPTreeNode( AFPTreeNode::ATOM, $this->mCur, $this->mPos );
					break;
				}

				throw new UserVisibleException(
					'unrecognisedkeyword',
					$this->mPos,
					[ $tok ]
				);
			/** @noinspection PhpMissingBreakStatementInspection */
			case AFPToken::TSQUAREBRACKET:
				if ( $this->mCur->value === '[' ) {
					$array = [];
					while ( true ) {
						$this->move();
						if ( $this->mCur->type === AFPToken::TSQUAREBRACKET && $this->mCur->value === ']' ) {
							break;
						}

						$array[] = $this->doLevelSet();

						if ( $this->mCur->type === AFPToken::TSQUAREBRACKET && $this->mCur->value === ']' ) {
							break;
						}
						if ( $this->mCur->type !== AFPToken::TCOMMA ) {
							throw new UserVisibleException(
								'expectednotfound',
								$this->mPos,
								[ ', or ]', $this->mCur->type, $this->mCur->value ]
							);
						}
					}

					$result = new AFPTreeNode( AFPTreeNode::ARRAY_DEFINITION, $array, $this->mPos );
					break;
				}

			// Fallthrough expected
			default:
				throw new UserVisibleException(
					'unexpectedtoken',
					$this->mPos,
					[
						$this->mCur->type,
						$this->mCur->value
					]
				);
		}

		$this->move();
		// @phan-suppress-next-next-line PhanPossiblyUndeclaredVariable
		// @phan-suppress-next-line PhanTypeMismatchReturnNullable Until phan can understand the switch
		return $result;
	}

	/**
	 * Given a variable name, check if the variable is deprecated. If it is, log the use.
	 * Do that here, and not every time the AST is eval'ed. This means less logging, but more
	 * performance.
	 * @param string $varname
	 */
	private function checkLogDeprecatedVar( $varname ) {
		if ( $this->keywordsManager->isVarDeprecated( $varname ) ) {
			$this->logger->debug( "Deprecated variable $varname used in filter {$this->mFilter}." );
		}
	}

	/**
	 * @param string $fname
	 * @return bool
	 */
	private function functionIsVariadic( string $fname ): bool {
		if ( !array_key_exists( $fname, FilterEvaluator::FUNC_ARG_COUNT ) ) {
			// @codeCoverageIgnoreStart
			throw new InvalidArgumentException( "Function $fname is not valid" );
			// @codeCoverageIgnoreEnd
		}
		return FilterEvaluator::FUNC_ARG_COUNT[$fname][1] === INF;
	}
}
