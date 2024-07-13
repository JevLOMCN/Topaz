<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace MediaWiki\Extension\ParserFunctions;

use UtfNormal\Validator;

class ExprParser {

	// Character classes
	private const EXPR_WHITE_CLASS = " \t\r\n";
	private const EXPR_NUMBER_CLASS = '0123456789.';

	// Token types
	private const EXPR_WHITE = 1;
	private const EXPR_NUMBER = 2;
	private const EXPR_NEGATIVE = 3;
	private const EXPR_POSITIVE = 4;
	private const EXPR_PLUS = 5;
	private const EXPR_MINUS = 6;
	private const EXPR_TIMES = 7;
	private const EXPR_DIVIDE = 8;
	private const EXPR_MOD = 9;
	private const EXPR_OPEN = 10;
	private const EXPR_CLOSE = 11;
	private const EXPR_AND = 12;
	private const EXPR_OR = 13;
	private const EXPR_NOT = 14;
	private const EXPR_EQUALITY = 15;
	private const EXPR_LESS = 16;
	private const EXPR_GREATER = 17;
	private const EXPR_LESSEQ = 18;
	private const EXPR_GREATEREQ = 19;
	private const EXPR_NOTEQ = 20;
	private const EXPR_ROUND = 21;
	private const EXPR_EXPONENT = 22;
	private const EXPR_SINE = 23;
	private const EXPR_COSINE = 24;
	private const EXPR_TANGENS = 25;
	private const EXPR_ARCSINE = 26;
	private const EXPR_ARCCOS = 27;
	private const EXPR_ARCTAN = 28;
	private const EXPR_EXP = 29;
	private const EXPR_LN = 30;
	private const EXPR_ABS = 31;
	private const EXPR_FLOOR = 32;
	private const EXPR_TRUNC = 33;
	private const EXPR_CEIL = 34;
	private const EXPR_POW = 35;
	private const EXPR_PI = 36;
	private const EXPR_FMOD = 37;
	private const EXPR_SQRT = 38;

	private const MAX_STACK_SIZE = 100;

	private const PRECEDENCE = [
		self::EXPR_NEGATIVE => 10,
		self::EXPR_POSITIVE => 10,
		self::EXPR_EXPONENT => 10,
		self::EXPR_SINE => 9,
		self::EXPR_COSINE => 9,
		self::EXPR_TANGENS => 9,
		self::EXPR_ARCSINE => 9,
		self::EXPR_ARCCOS => 9,
		self::EXPR_ARCTAN => 9,
		self::EXPR_EXP => 9,
		self::EXPR_LN => 9,
		self::EXPR_ABS => 9,
		self::EXPR_FLOOR => 9,
		self::EXPR_TRUNC => 9,
		self::EXPR_CEIL => 9,
		self::EXPR_NOT => 9,
		self::EXPR_SQRT => 9,
		self::EXPR_POW => 8,
		self::EXPR_TIMES => 7,
		self::EXPR_DIVIDE => 7,
		self::EXPR_MOD => 7,
		self::EXPR_FMOD => 7,
		self::EXPR_PLUS => 6,
		self::EXPR_MINUS => 6,
		self::EXPR_ROUND => 5,
		self::EXPR_EQUALITY => 4,
		self::EXPR_LESS => 4,
		self::EXPR_GREATER => 4,
		self::EXPR_LESSEQ => 4,
		self::EXPR_GREATEREQ => 4,
		self::EXPR_NOTEQ => 4,
		self::EXPR_AND => 3,
		self::EXPR_OR => 2,
		self::EXPR_PI => 0,
		self::EXPR_OPEN => -1,
		self::EXPR_CLOSE => -1,
	];

	private const NAMES = [
		self::EXPR_NEGATIVE => '-',
		self::EXPR_POSITIVE => '+',
		self::EXPR_NOT => 'not',
		self::EXPR_TIMES => '*',
		self::EXPR_DIVIDE => '/',
		self::EXPR_MOD => 'mod',
		self::EXPR_FMOD => 'fmod',
		self::EXPR_PLUS => '+',
		self::EXPR_MINUS => '-',
		self::EXPR_ROUND => 'round',
		self::EXPR_EQUALITY => '=',
		self::EXPR_LESS => '<',
		self::EXPR_GREATER => '>',
		self::EXPR_LESSEQ => '<=',
		self::EXPR_GREATEREQ => '>=',
		self::EXPR_NOTEQ => '<>',
		self::EXPR_AND => 'and',
		self::EXPR_OR => 'or',
		self::EXPR_EXPONENT => 'e',
		self::EXPR_SINE => 'sin',
		self::EXPR_COSINE => 'cos',
		self::EXPR_TANGENS => 'tan',
		self::EXPR_ARCSINE => 'asin',
		self::EXPR_ARCCOS => 'acos',
		self::EXPR_ARCTAN => 'atan',
		self::EXPR_LN => 'ln',
		self::EXPR_EXP => 'exp',
		self::EXPR_ABS => 'abs',
		self::EXPR_FLOOR => 'floor',
		self::EXPR_TRUNC => 'trunc',
		self::EXPR_CEIL => 'ceil',
		self::EXPR_POW => '^',
		self::EXPR_PI => 'pi',
		self::EXPR_SQRT => 'sqrt',
	];

	private const WORDS = [
		'mod' => self::EXPR_MOD,
		'fmod' => self::EXPR_FMOD,
		'and' => self::EXPR_AND,
		'or' => self::EXPR_OR,
		'not' => self::EXPR_NOT,
		'round' => self::EXPR_ROUND,
		'div' => self::EXPR_DIVIDE,
		'e' => self::EXPR_EXPONENT,
		'sin' => self::EXPR_SINE,
		'cos' => self::EXPR_COSINE,
		'tan' => self::EXPR_TANGENS,
		'asin' => self::EXPR_ARCSINE,
		'acos' => self::EXPR_ARCCOS,
		'atan' => self::EXPR_ARCTAN,
		'exp' => self::EXPR_EXP,
		'ln' => self::EXPR_LN,
		'abs' => self::EXPR_ABS,
		'trunc' => self::EXPR_TRUNC,
		'floor' => self::EXPR_FLOOR,
		'ceil' => self::EXPR_CEIL,
		'pi' => self::EXPR_PI,
		'sqrt' => self::EXPR_SQRT,
	];

	/**
	 * Evaluate a mathematical expression
	 *
	 * The algorithm here is based on the infix to RPN algorithm given in
	 * http://montcs.bloomu.edu/~bobmon/Information/RPN/infix2rpn.shtml
	 * It's essentially the same as Dijkstra's shunting yard algorithm.
	 * @param string $expr
	 * @return string
	 * @throws ExprError
	 */
	public function doExpression( $expr ) {
		$operands = [];
		$operators = [];

		# Unescape inequality operators
		$expr = strtr( $expr, [ '&lt;' => '<', '&gt;' => '>',
			'&minus;' => '-', '−' => '-' ] );

		$p = 0;
		$end = strlen( $expr );
		$expecting = 'expression';
		$name = '';

		while ( $p < $end ) {
			if ( count( $operands ) > self::MAX_STACK_SIZE || count( $operators ) > self::MAX_STACK_SIZE ) {
				throw new ExprError( 'stack_exhausted' );
			}
			$char = $expr[$p];
			$char2 = substr( $expr, $p, 2 );

			// Mega if-elseif-else construct
			// Only binary operators fall through for processing at the bottom, the rest
			// finish their processing and continue

			// First the unlimited length classes

			// @phan-suppress-next-line PhanParamSuspiciousOrder false positive
			if ( strpos( self::EXPR_WHITE_CLASS, $char ) !== false ) {
				// Whitespace
				$p += strspn( $expr, self::EXPR_WHITE_CLASS, $p );
				continue;
				// @phan-suppress-next-line PhanParamSuspiciousOrder false positive
			} elseif ( strpos( self::EXPR_NUMBER_CLASS, $char ) !== false ) {
				// Number
				if ( $expecting !== 'expression' ) {
					throw new ExprError( 'unexpected_number' );
				}

				// Find the rest of it
				$length = strspn( $expr, self::EXPR_NUMBER_CLASS, $p );
				// Convert it to float, silently removing double decimal points
				$operands[] = (float)substr( $expr, $p, $length );
				$p += $length;
				$expecting = 'operator';
				continue;
			} elseif ( ctype_alpha( $char ) ) {
				// Word
				// Find the rest of it
				$remaining = substr( $expr, $p );
				if ( !preg_match( '/^[A-Za-z]*/', $remaining, $matches ) ) {
					// This should be unreachable
					throw new ExprError( 'preg_match_failure' );
				}
				$word = strtolower( $matches[0] );
				$p += strlen( $word );

				// Interpret the word
				if ( !isset( self::WORDS[$word] ) ) {
					throw new ExprError( 'unrecognised_word', $word );
				}
				$op = self::WORDS[$word];
				switch ( $op ) {
					// constant
					case self::EXPR_EXPONENT:
						if ( $expecting !== 'expression' ) {
							break;
						}
						$operands[] = exp( 1 );
						$expecting = 'operator';
						continue 2;
					case self::EXPR_PI:
						if ( $expecting !== 'expression' ) {
							throw new ExprError( 'unexpected_number' );
						}
						$operands[] = pi();
						$expecting = 'operator';
						continue 2;
					// Unary operator
					case self::EXPR_NOT:
					case self::EXPR_SINE:
					case self::EXPR_COSINE:
					case self::EXPR_TANGENS:
					case self::EXPR_ARCSINE:
					case self::EXPR_ARCCOS:
					case self::EXPR_ARCTAN:
					case self::EXPR_EXP:
					case self::EXPR_LN:
					case self::EXPR_ABS:
					case self::EXPR_FLOOR:
					case self::EXPR_TRUNC:
					case self::EXPR_CEIL:
					case self::EXPR_SQRT:
						if ( $expecting !== 'expression' ) {
							throw new ExprError( 'unexpected_operator', $word );
						}
						$operators[] = $op;
						continue 2;
				}
				// Binary operator, fall through
				$name = $word;
			} elseif ( $char2 === '<=' ) {
				$name = $char2;
				$op = self::EXPR_LESSEQ;
				$p += 2;
			} elseif ( $char2 === '>=' ) {
				$name = $char2;
				$op = self::EXPR_GREATEREQ;
				$p += 2;
			} elseif ( $char2 === '<>' || $char2 === '!=' ) {
				$name = $char2;
				$op = self::EXPR_NOTEQ;
				$p += 2;
			} elseif ( $char === '+' ) {
				++$p;
				if ( $expecting === 'expression' ) {
					// Unary plus
					$operators[] = self::EXPR_POSITIVE;
					continue;
				} else {
					// Binary plus
					$op = self::EXPR_PLUS;
				}
			} elseif ( $char === '-' ) {
				++$p;
				if ( $expecting === 'expression' ) {
					// Unary minus
					$operators[] = self::EXPR_NEGATIVE;
					continue;
				} else {
					// Binary minus
					$op = self::EXPR_MINUS;
				}
			} elseif ( $char === '*' ) {
				$name = $char;
				$op = self::EXPR_TIMES;
				++$p;
			} elseif ( $char === '/' ) {
				$name = $char;
				$op = self::EXPR_DIVIDE;
				++$p;
			} elseif ( $char === '^' ) {
				$name = $char;
				$op = self::EXPR_POW;
				++$p;
			} elseif ( $char === '(' ) {
				if ( $expecting === 'operator' ) {
					throw new ExprError( 'unexpected_operator', '(' );
				}
				$operators[] = self::EXPR_OPEN;
				++$p;
				continue;
			} elseif ( $char === ')' ) {
				$lastOp = end( $operators );
				while ( $lastOp && $lastOp !== self::EXPR_OPEN ) {
					$this->doOperation( $lastOp, $operands );
					array_pop( $operators );
					$lastOp = end( $operators );
				}
				if ( $lastOp ) {
					array_pop( $operators );
				} else {
					throw new ExprError( 'unexpected_closing_bracket' );
				}
				$expecting = 'operator';
				++$p;
				continue;
			} elseif ( $char === '=' ) {
				$name = $char;
				$op = self::EXPR_EQUALITY;
				++$p;
			} elseif ( $char === '<' ) {
				$name = $char;
				$op = self::EXPR_LESS;
				++$p;
			} elseif ( $char === '>' ) {
				$name = $char;
				$op = self::EXPR_GREATER;
				++$p;
			} else {
				$utfExpr = Validator::cleanUp( substr( $expr, $p ) );
				throw new ExprError( 'unrecognised_punctuation', mb_substr( $utfExpr, 0, 1 ) );
			}

			// Binary operator processing
			if ( $expecting === 'expression' ) {
				throw new ExprError( 'unexpected_operator', $name );
			}

			// Shunting yard magic
			$lastOp = end( $operators );
			while ( $lastOp && self::PRECEDENCE[$op] <= self::PRECEDENCE[$lastOp] ) {
				$this->doOperation( $lastOp, $operands );
				array_pop( $operators );
				$lastOp = end( $operators );
			}
			$operators[] = $op;
			$expecting = 'expression';
		}

		// Finish off the operator array
		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( $op = array_pop( $operators ) ) {
			if ( $op === self::EXPR_OPEN ) {
				throw new ExprError( 'unclosed_bracket' );
			}
			$this->doOperation( $op, $operands );
		}

		return implode( "<br />\n", $operands );
	}

	/**
	 * @param int $op
	 * @param array &$stack
	 * @throws ExprError
	 */
	public function doOperation( $op, &$stack ) {
		switch ( $op ) {
			case self::EXPR_NEGATIVE:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = -$arg;
				break;
			case self::EXPR_POSITIVE:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				break;
			case self::EXPR_TIMES:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = $left * $right;
				break;
			case self::EXPR_DIVIDE:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				if ( !$right ) {
					throw new ExprError( 'division_by_zero', self::NAMES[$op] );
				}
				$stack[] = $left / $right;
				break;
			case self::EXPR_MOD:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = (int)array_pop( $stack );
				$left = (int)array_pop( $stack );
				if ( !$right ) {
					throw new ExprError( 'division_by_zero', self::NAMES[$op] );
				}
				$stack[] = $left % $right;
				break;
			case self::EXPR_FMOD:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = (double)array_pop( $stack );
				$left = (double)array_pop( $stack );
				if ( !$right ) {
					throw new ExprError( 'division_by_zero', self::NAMES[$op] );
				}
				$stack[] = fmod( $left, $right );
				break;
			case self::EXPR_PLUS:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = $left + $right;
				break;
			case self::EXPR_MINUS:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = $left - $right;
				break;
			case self::EXPR_AND:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left && $right ) ? 1 : 0;
				break;
			case self::EXPR_OR:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left || $right ) ? 1 : 0;
				break;
			case self::EXPR_EQUALITY:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left == $right ) ? 1 : 0;
				break;
			case self::EXPR_NOT:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = ( !$arg ) ? 1 : 0;
				break;
			case self::EXPR_ROUND:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$digits = (int)array_pop( $stack );
				$value = array_pop( $stack );
				$stack[] = round( $value, $digits );
				break;
			case self::EXPR_LESS:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left < $right ) ? 1 : 0;
				break;
			case self::EXPR_GREATER:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left > $right ) ? 1 : 0;
				break;
			case self::EXPR_LESSEQ:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left <= $right ) ? 1 : 0;
				break;
			case self::EXPR_GREATEREQ:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left >= $right ) ? 1 : 0;
				break;
			case self::EXPR_NOTEQ:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = ( $left != $right ) ? 1 : 0;
				break;
			case self::EXPR_EXPONENT:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$stack[] = $left * pow( 10, $right );
				break;
			case self::EXPR_SINE:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = sin( $arg );
				break;
			case self::EXPR_COSINE:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = cos( $arg );
				break;
			case self::EXPR_TANGENS:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = tan( $arg );
				break;
			case self::EXPR_ARCSINE:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				if ( $arg < -1 || $arg > 1 ) {
					throw new ExprError( 'invalid_argument', self::NAMES[$op] );
				}
				$stack[] = asin( $arg );
				break;
			case self::EXPR_ARCCOS:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				if ( $arg < -1 || $arg > 1 ) {
					throw new ExprError( 'invalid_argument', self::NAMES[$op] );
				}
				$stack[] = acos( $arg );
				break;
			case self::EXPR_ARCTAN:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = atan( $arg );
				break;
			case self::EXPR_EXP:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = exp( $arg );
				break;
			case self::EXPR_LN:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				if ( $arg <= 0 ) {
					throw new ExprError( 'invalid_argument_ln', self::NAMES[$op] );
				}
				$stack[] = log( $arg );
				break;
			case self::EXPR_ABS:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = abs( $arg );
				break;
			case self::EXPR_FLOOR:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = floor( $arg );
				break;
			case self::EXPR_TRUNC:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = (int)$arg;
				break;
			case self::EXPR_CEIL:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$stack[] = ceil( $arg );
				break;
			case self::EXPR_POW:
				if ( count( $stack ) < 2 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$right = array_pop( $stack );
				$left = array_pop( $stack );
				$result = pow( $left, $right );
				$stack[] = $result;
				break;
			case self::EXPR_SQRT:
				if ( count( $stack ) < 1 ) {
					throw new ExprError( 'missing_operand', self::NAMES[$op] );
				}
				$arg = array_pop( $stack );
				$result = sqrt( $arg );
				if ( is_nan( $result ) ) {
					throw new ExprError( 'not_a_number', self::NAMES[$op] );
				}
				$stack[] = $result;
				break;
			default:
				// Should be impossible to reach here.
				// @codeCoverageIgnoreStart
				throw new ExprError( 'unknown_error' );
				// @codeCoverageIgnoreEnd
		}
	}
}
