<?php
/**
 * @author Tim Starling
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @file
 */

namespace CLDRPluralRuleParser;

use CLDRPluralRuleParser\Converter\Expression;
use CLDRPluralRuleParser\Converter\Fragment;
use CLDRPluralRuleParser\Converter\Operator;

/**
 * Helper class for converting rules to reverse polish notation (RPN).
 */
class Converter {
	/**
	 * The input string
	 *
	 * @var string
	 */
	public $rule;

	/**
	 * The current position
	 *
	 * @var int
	 */
	public $pos;

	/**
	 * The past-the-end position
	 *
	 * @var int
	 */
	public $end;

	/**
	 * The operator stack
	 *
	 * @var array
	 */
	public $operators = [];

	/**
	 * The operand stack
	 *
	 * @var array
	 */
	public $operands = [];

	/**
	 * Precedence levels. Note that there's no need to worry about associativity
	 * for the level 4 operators, since they return boolean and don't accept
	 * boolean inputs.
	 *
	 * @var array
	 */
	private static $precedence = [
		'or' => 2,
		'and' => 3,
		'is' => 4,
		'is-not' => 4,
		'in' => 4,
		'not-in' => 4,
		'within' => 4,
		'not-within' => 4,
		'mod' => 5,
		',' => 6,
		'..' => 7,
	];

	/**
	 * A character list defining whitespace, for use in strspn() etc.
	 */
	private const WHITESPACE_CLASS = " \t\r\n";

	/**
	 * Same for digits. Note that the grammar given in UTS #35 doesn't allow
	 * negative numbers or decimal separators.
	 */
	private const NUMBER_CLASS = '0123456789';

	/**
	 * A character list of symbolic operands.
	 */
	private const OPERAND_SYMBOLS = 'nivwft';

	/**
	 * An anchored regular expression which matches a word at the current offset.
	 */
	private const WORD_REGEX = '/[a-zA-Z@]+/A';

	/**
	 * Convert a rule to RPN. This is the only public entry point.
	 *
	 * @param string $rule The rule to convert
	 * @return string The RPN representation of the rule
	 */
	public static function convert( $rule ) {
		$parser = new self( $rule );

		return $parser->doConvert();
	}

	/**
	 * Private constructor.
	 * @param string $rule
	 */
	protected function __construct( $rule ) {
		$this->rule = $rule;
		$this->pos = 0;
		$this->end = strlen( $rule );
	}

	/**
	 * Do the operation.
	 *
	 * @return string The RPN representation of the rule (e.g. "5 3 mod n is")
	 */
	protected function doConvert() {
		$expectOperator = true;

		// Iterate through all tokens, saving the operators and operands to a
		// stack per Dijkstra's shunting yard algorithm.
		/** @var Operator $token */
		$token = $this->nextToken();
		while ( $token !== false ) {
			// In this grammar, there are only binary operators, so every valid
			// rule string will alternate between operator and operand tokens.
			$expectOperator = !$expectOperator;

			if ( $token instanceof Expression ) {
				// Operand
				if ( $expectOperator ) {
					$token->error( 'unexpected operand' );
				}
				$this->operands[] = $token;
			} else {
				// Operator
				if ( !$expectOperator ) {
					$token->error( 'unexpected operator' );
				}
				// Resolve higher precedence levels
				/** @var Operator $lastOp */
				$lastOp = end( $this->operators );
				// @phan-suppress-next-line PhanUndeclaredProperty
				while ( $lastOp && self::$precedence[$token->name] <= self::$precedence[$lastOp->name] ) {
					$this->doOperation( $lastOp );
					array_pop( $this->operators );
					$lastOp = end( $this->operators );
				}
				$this->operators[] = $token;
			}

			$token = $this->nextToken();
		}

		// Finish off the stack
		while ( $this->operators ) {
			$this->doOperation( array_pop( $this->operators ) );
		}

		// Make sure the result is sane. The first case is possible for an empty
		// string input, the second should be unreachable.
		if ( !count( $this->operands ) ) {
			$this->error( 'condition expected' );
		} elseif ( count( $this->operands ) > 1 ) {
			$this->error( 'missing operator or too many operands' );
		}

		$value = $this->operands[0];
		if ( $value->type !== 'boolean' ) {
			$this->error( 'the result must have a boolean type' );
		}

		return $this->operands[0]->rpn;
	}

	/**
	 * Fetch the next token from the input string.
	 *
	 * @return Fragment|false The next token
	 */
	protected function nextToken() {
		if ( $this->pos >= $this->end ) {
			return false;
		}

		// Whitespace
		$length = strspn( $this->rule, self::WHITESPACE_CLASS, $this->pos );
		$this->pos += $length;

		if ( $this->pos >= $this->end ) {
			return false;
		}

		// Number
		$length = strspn( $this->rule, self::NUMBER_CLASS, $this->pos );
		if ( $length !== 0 ) {
			$token = $this->newNumber( substr( $this->rule, $this->pos, $length ), $this->pos );
			$this->pos += $length;

			return $token;
		}

		// Two-character operators
		$op2 = substr( $this->rule, $this->pos, 2 );
		if ( $op2 === '..' || $op2 === '!=' ) {
			$token = $this->newOperator( $op2, $this->pos, 2 );
			$this->pos += 2;

			return $token;
		}

		// Single-character operators
		$op1 = $this->rule[$this->pos];
		if ( $op1 === ',' || $op1 === '=' || $op1 === '%' ) {
			$token = $this->newOperator( $op1, $this->pos, 1 );
			$this->pos++;

			return $token;
		}

		// Word
		if ( !preg_match( self::WORD_REGEX, $this->rule, $m, 0, $this->pos ) ) {
			$this->error( 'unexpected character "' . $this->rule[$this->pos] . '"' );
		}
		$word1 = strtolower( $m[0] );
		$word2 = '';
		$nextTokenPos = $this->pos + strlen( $word1 );
		if ( $word1 === 'not' || $word1 === 'is' ) {
			// Look ahead one word
			$nextTokenPos += strspn( $this->rule, self::WHITESPACE_CLASS, $nextTokenPos );
			if ( $nextTokenPos < $this->end
				&& preg_match( self::WORD_REGEX, $this->rule, $m, 0, $nextTokenPos )
			) {
				$word2 = strtolower( $m[0] );
				$nextTokenPos += strlen( $word2 );
			}
		}

		// Two-word operators like "is not" take precedence over single-word operators like "is"
		if ( $word2 !== '' ) {
			$bothWords = "{$word1}-{$word2}";
			if ( isset( self::$precedence[$bothWords] ) ) {
				$token = $this->newOperator( $bothWords, $this->pos, $nextTokenPos - $this->pos );
				$this->pos = $nextTokenPos;

				return $token;
			}
		}

		// Single-word operators
		if ( isset( self::$precedence[$word1] ) ) {
			$token = $this->newOperator( $word1, $this->pos, strlen( $word1 ) );
			$this->pos += strlen( $word1 );

			return $token;
		}

		// The single-character operand symbols
		// @phan-suppress-next-line PhanParamSuspiciousOrder
		if ( strpos( self::OPERAND_SYMBOLS, $word1 ) !== false ) {
			$token = $this->newNumber( $word1, $this->pos );
			$this->pos++;

			return $token;
		}

		// Samples
		if ( $word1 === '@integer' || $word1 === '@decimal' ) {
			// Samples are like comments, they have no effect on rule evaluation.
			// They run from the first sample indicator to the end of the string.
			$this->pos = $this->end;

			return false;
		}

		$this->error( 'unrecognised word' );
	}

	/**
	 * For the binary operator $op, pop its operands off the stack and push
	 * a fragment with rpn and type members describing the result of that
	 * operation.
	 *
	 * @param Operator $op
	 */
	protected function doOperation( Operator $op ) {
		if ( count( $this->operands ) < 2 ) {
			$op->error( 'missing operand' );
		}
		$right = array_pop( $this->operands );
		$left = array_pop( $this->operands );
		$result = $op->operate( $left, $right );
		$this->operands[] = $result;
	}

	/**
	 * Create a numerical expression object
	 *
	 * @param string $text
	 * @param int $pos
	 * @return Expression The numerical expression
	 */
	protected function newNumber( $text, $pos ) {
		return new Expression( $this, 'number', $text, $pos, strlen( $text ) );
	}

	/**
	 * Create a binary operator
	 *
	 * @param string $type
	 * @param int $pos
	 * @param int $length
	 * @return Operator The operator
	 */
	protected function newOperator( $type, $pos, $length ) {
		return new Operator( $this, $type, $pos, $length );
	}

	/**
	 * Throw an error
	 * @param string $message
	 */
	protected function error( $message ) {
		throw new Error( $message );
	}
}
