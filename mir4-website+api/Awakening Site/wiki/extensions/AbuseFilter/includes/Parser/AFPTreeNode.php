<?php

namespace MediaWiki\Extension\AbuseFilter\Parser;

use MediaWiki\Extension\AbuseFilter\Parser\Exception\InternalException;

/**
 * Represents a node of a parser tree.
 */
class AFPTreeNode {
	// Each of the constants below represents a node corresponding to a level
	// of the parser, from the top of the tree to the bottom.

	// ENTRY is always one-element and thus does not have its own node.

	// SEMICOLON is a many-children node, denoting that the nodes have to be
	// evaluated in order and the last value has to be returned.
	public const SEMICOLON = 'SEMICOLON';

	// ASSIGNMENT (formerly known as SET) is a node which is responsible for
	// assigning values to variables.  ASSIGNMENT is a (variable name [string],
	// value [tree node]) tuple, INDEX_ASSIGNMENT (which is used to assign
	// values at array offsets) is a (variable name [string], index [tree node],
	// value [tree node]) tuple, and ARRAY_APPEND has the form of (variable name
	// [string], value [tree node]).
	public const ASSIGNMENT = 'ASSIGNMENT';
	public const INDEX_ASSIGNMENT = 'INDEX_ASSIGNMENT';
	public const ARRAY_APPEND = 'ARRAY_APPEND';

	// CONDITIONAL represents both a ternary operator and an if-then-else-end
	// construct.  The format is (condition, evaluated-if-true, evaluated-in-false).
	// The first two are tree nodes, the last one can be a node, or null if there's no else.
	public const CONDITIONAL = 'CONDITIONAL';

	// LOGIC is a logic operator accepted by AFPData::boolOp.  The format is
	// (operation, left operand, right operand).
	public const LOGIC = 'LOGIC';

	// COMPARE is a comparison operator accepted by AFPData::boolOp.  The format is
	// (operation, left operand, right operand).
	public const COMPARE = 'COMPARE';

	// SUM_REL is either '+' or '-'.  The format is (operation, left operand,
	// right operand).
	public const SUM_REL = 'SUM_REL';

	// MUL_REL is a multiplication-related operation accepted by AFPData::mulRel.
	// The format is (operation, left operand, right operand).
	public const MUL_REL = 'MUL_REL';

	// POW is an exponentiation operator.  The format is (base, exponent).
	public const POW = 'POW';

	// BOOL_INVERT is a boolean inversion operator.  The format is (operand).
	public const BOOL_INVERT = 'BOOL_INVERT';

	// KEYWORD_OPERATOR is one of the binary keyword operators supported by the
	// filter language.  The format is (keyword, left operand, right operand).
	public const KEYWORD_OPERATOR = 'KEYWORD_OPERATOR';

	// UNARY is either unary minus or unary plus.  The format is (operator, operand).
	public const UNARY = 'UNARY';

	// ARRAY_INDEX is an operation of accessing an array by an offset.  The format
	// is (array, offset).
	public const ARRAY_INDEX = 'ARRAY_INDEX';

	// Since parenthesis only manipulate precedence of the operators, they are
	// not explicitly represented in the tree.

	// FUNCTION_CALL is an invocation of built-in function.  The format is a
	// tuple where the first element is a function name, and all subsequent
	// elements are the arguments.
	public const FUNCTION_CALL = 'FUNCTION_CALL';

	// ARRAY_DEFINITION is an array literal.  The $children field contains tree
	// nodes for the values of each of the array element used.
	public const ARRAY_DEFINITION = 'ARRAY_DEFINITION';

	// ATOM is a node representing a literal.  The only element of $children is a
	// token corresponding to the literal.
	public const ATOM = 'ATOM';

	// BINOP is a combination of LOGIC (^), COMPARE (<=, <, etc.),
	// SUM_REL (+, -), MUL_REL (*, /, %), POW (**),
	// KEYWORD_OPERATOR (like, rlike, etc.), and ARRAY_INDEX ([]).
	// The format is (operator, operand, operand).
	// Currently, it's only used in SyntaxChecker
	// & and | which is in LOGIC is not in BINOP because it affects
	// control flow.
	public const BINOP = 'BINOP';

	/** @var string Type of the node, one of the constants above */
	public $type;
	/**
	 * Parameters of the value. Typically it is an array of children nodes,
	 * which might be either strings (for parametrization of the node) or another
	 * node. In case of ATOM it's a parser token.
	 * @var AFPTreeNode[]|string[]|AFPToken
	 */
	public $children;

	/** @var int Position used for error reporting. */
	public $position;

	/**
	 * @param string $type
	 * @param (AFPTreeNode|null)[]|string[]|AFPToken $children
	 * @param int $position
	 */
	public function __construct( $type, $children, $position ) {
		$this->type = $type;
		$this->children = $children;
		$this->position = $position;
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function toDebugString() {
		return implode( "\n", $this->toDebugStringInner() );
	}

	/**
	 * @return array
	 * @codeCoverageIgnore
	 */
	private function toDebugStringInner() {
		if ( $this->type === self::ATOM ) {
			return [ "ATOM({$this->children->type} {$this->children->value})" ];
		}

		$align = static function ( $line ) {
			return '  ' . $line;
		};

		$lines = [ $this->type ];
		// @phan-suppress-next-line PhanTypeSuspiciousNonTraversableForeach children is array here
		foreach ( $this->children as $subnode ) {
			if ( $subnode instanceof AFPTreeNode ) {
				$sublines = array_map( $align, $subnode->toDebugStringInner() );
			} elseif ( is_string( $subnode ) ) {
				$sublines = [ "  {$subnode}" ];
			} else {
				throw new InternalException( "Each node parameter has to be either a node or a string" );
			}

			$lines = array_merge( $lines, $sublines );
		}
		return $lines;
	}
}
