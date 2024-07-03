<?php
/**
 * @author Tim Starling
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @file
 */

namespace CLDRPluralRuleParser;

/**
 * Parse and evaluate a plural rule.
 *
 * UTS #35 Revision 33
 * http://www.unicode.org/reports/tr35/tr35-33/tr35-numbers.html#Language_Plural_Rules
 */
class Evaluator {
	/**
	 * Evaluate a number against a set of plural rules. If a rule passes,
	 * return the index of plural rule.
	 *
	 * @param int $number The number to be evaluated against the rules
	 * @param array $rules The associative array of plural rules in pluralform => rule format.
	 * @return int The index of the plural form which passed the evaluation
	 */
	public static function evaluate( $number, array $rules ) {
		$rules = self::compile( $rules );

		return self::evaluateCompiled( $number, $rules );
	}

	/**
	 * Convert a set of rules to a compiled form which is optimised for
	 * fast evaluation. The result will be an array of strings, and may be cached.
	 *
	 * @param array $rules The rules to compile
	 * @return array An array of compile rules.
	 */
	public static function compile( array $rules ) {
		// We can't use array_map() for this because it generates a warning if
		// there is an exception.
		foreach ( $rules as &$rule ) {
			$rule = Converter::convert( $rule );
		}

		return $rules;
	}

	/**
	 * Evaluate a compiled set of rules returned by compile(). Do not allow
	 * the user to edit the compiled form, or else PHP errors may result.
	 *
	 * @param string|int $number The number to be evaluated against the rules, in English, or it
	 *   may be a type convertible to string.
	 * @param array $rules The associative array of plural rules in pluralform => rule format.
	 * @return int The index of the plural form which passed the evaluation
	 */
	public static function evaluateCompiled( $number, array $rules ) {
		// Calculate the values of the operand symbols
		$number = strval( $number );
		if ( !preg_match( '/^ -? ( ([0-9]+) (?: \. ([0-9]+) )? )$/x', $number, $m ) ) {
			return count( $rules );
		}
		if ( !isset( $m[3] ) ) {
			$operandSymbols = [
				'n' => intval( $m[1] ),
				'i' => intval( $m[1] ),
				'v' => 0,
				'w' => 0,
				'f' => 0,
				't' => 0
			];
		} else {
			$absValStr = $m[1];
			$intStr = $m[2];
			$fracStr = $m[3];
			$operandSymbols = [
				'n' => floatval( $absValStr ),
				'i' => intval( $intStr ),
				'v' => strlen( $fracStr ),
				'w' => strlen( rtrim( $fracStr, '0' ) ),
				'f' => intval( $fracStr ),
				't' => intval( rtrim( $fracStr, '0' ) ),
			];
		}

		// The compiled form is RPN, with tokens strictly delimited by
		// spaces, so this is a simple RPN evaluator.
		foreach ( $rules as $i => $rule ) {
			$stack = [];
			$zero = ord( '0' );
			$nine = ord( '9' );

			foreach ( explode( ' ', $rule ) as $token ) {
				$ord = ord( $token );
				if ( isset( $operandSymbols[$token] ) ) {
					$stack[] = $operandSymbols[$token];
				} elseif ( $ord >= $zero && $ord <= $nine ) {
					$stack[] = intval( $token );
				} else {
					$right = array_pop( $stack );
					$left = array_pop( $stack );
					$result = self::doOperation( $token, $left, $right );
					$stack[] = $result;
				}
			}
			if ( $stack[0] ) {
				return $i;
			}
		}
		// None of the provided rules match. The number belongs to category
		// 'other', which comes last.
		return count( $rules );
	}

	/**
	 * Do a single operation
	 *
	 * @param string $token The token string
	 * @param mixed $left The left operand. If it is an object, its state may be destroyed.
	 * @param mixed $right The right operand
	 * @throws Error
	 * @return mixed The operation result
	 */
	private static function doOperation( $token, $left, $right ) {
		if ( in_array( $token, [ 'in', 'not-in', 'within', 'not-within' ] ) ) {
			if ( !$right instanceof Range ) {
				$right = new Range( $right );
			}
		}
		switch ( $token ) {
			case 'or':
				return $left || $right;
			case 'and':
				return $left && $right;
			case 'is':
				return $left == $right;
			case 'is-not':
				return $left != $right;
			case 'in':
				return $right->isNumberIn( $left );
			case 'not-in':
				return !$right->isNumberIn( $left );
			case 'within':
				return $right->isNumberWithin( $left );
			case 'not-within':
				return !$right->isNumberWithin( $left );
			case 'mod':
				if ( is_int( $left ) ) {
					return (int)fmod( $left, $right );
				}

				return fmod( $left, $right );
			case ',':
				if ( $left instanceof Range ) {
					$range = $left;
				} else {
					$range = new Range( $left );
				}
				$range->add( $right );

				return $range;
			case '..':
				return new Range( $left, $right );
			default:
				throw new Error( "Invalid RPN token" );
		}
	}
}
