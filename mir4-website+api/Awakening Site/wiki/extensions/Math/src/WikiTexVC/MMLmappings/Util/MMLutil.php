<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util;

use IntlChar;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Curly;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;

/**
 * Utility Methods for parsing Tex to MathML
 * @author Johannes Stegmüller
 */
class MMLutil {
	/**
	 * Splits a regular expression in the form '\operatorname {someparams}
	 * Also recognizes succeeding parentheses '\operatorname (' as params
	 * @param string $input tex expression
	 * @return array|null found groups or null
	 */
	public static function initalParseLiteralExpression( $input ): ?array {
		$pattern = "/([\\a-zA-Z\s]+)\{([^}]+)\}/";
		$matches = [];
		$matched = preg_match_all( $pattern, $input, $matches );
		return $matched ? $matches : null;
	}

	/**
	 * Recognize if input is a unicode string "\\u1235"
	 * If yes converts it to notation "&#x123;", if no just returns the input.
	 * @param string $input input to be checked
	 * @return string modified input or input
	 */
	public static function uc2xNotation( string $input ): string {
		if ( str_starts_with( $input, "\\u" ) ) {
			return str_replace( "\\u", "&#x", $input ) . ";";
		}
		return $input;
	}

	/**
	 * If an input string is in "&#x12..:" notation
	 * then convert it to a string of notation \\u12..
	 * @param string $input input to be checked
	 * @return string modified input or input
	 */
	public static function x2uNotation( string $input ): string {
		if ( str_starts_with( $input, "&#x" ) ) {
			return rtrim( str_replace( "&#x", "\\u", $input ), ";" );
		}
		return $input;
	}

	public static function number2xNotation( $input ): string {
		return "&#x" . $input . ";";
	}

	/**
	 * Rounds a floating point input to three digits precision
	 * and returns it as string with succeeding "em".
	 * @param float $size input to be processed
	 * @return string rounded digits with em
	 */
	public static function round2em( float $size ) {
		$rounded = round( $size, 3 );
		return $rounded . "em";
	}

	/**
	 * In a floating point digit as string, set input to precision of three digits
	 * without rounding.
	 * @param string $size input to be checked
	 * @return string digits of precision three with em
	 */
	public static function size2em( string $size ): string {
		return preg_replace( "/(\.\d\d\d).+/", '$1', $size ) . "em";
	}

	/**
	 * Assumes the input curly contains an TexArray of literals, squashes the TexArray characters to a string.
	 * @param Curly $node curly containing a TexArray of literals
	 * @return ?string squashed string in example "2mu", "-3mu" etc. Null if no TexArray inside curly.
	 */
	public static function squashLitsToUnit( Curly $node ): ?string {
		$unit = "";
		foreach ( $node->getArg()->getArgs() as $literal ) {
			if ( !$literal instanceof Literal ) {
				continue;
			}
			$unit .= $literal->getArg();
		}

		return $unit;
	}

	/**
	 * em or other dimensional unit gets multiplied by pre-operator.
	 * @param string $size input size i.e-123em
	 * @param string $operator "plus (+) or minus (-)
	 * @return string ++ => + , -- => +, -+ => -
	 */
	public static function addPreOperator( string $size, string $operator ): string {
		$emtr = trim( $size );

		$ok = preg_match( "/^([+\-])$/", $operator );
		if ( !$ok ) {
			return '';
		}
		switch ( $emtr[0] ) {
			case "-":
				if ( $operator == "+" ) {
					return $emtr;
				} elseif ( $operator == "-" ) {
					$emtr[0] = "+";
					return $emtr;
				}
				break;
			case "+":
				if ( $operator == "+" ) {
					return $emtr;
				} elseif ( $operator == "-" ) {
					$emtr[0] = "-";
					return $emtr;
				}
				break;
			default:
				return $operator . $emtr;
		}
		return $emtr;
	}

	/**
	 * Convert a length dimension to em format
	 * currently supports "mu: math unit and forwards em"
	 * @param string $dimen input for length dimension  like "-2mu" or "3 em"
	 * @return string|null converted string i.e. "0.333em"  or null if error
	 */
	public static function dimen2em( string $dimen ): ?string {
		$matches = [];
		$matched = preg_match( '/([+-]?)(\d*\.*\d+)\s*(mu|em)/', $dimen, $matches );

		if ( !$matched ) {
			return null;
		}
		if ( $matches[3] == "mu" ) {
			$ret = self::size2em( strval( intval( $matches[2] ) / 18 ) );
		} elseif ( $matches[3] == "em" ) {
			$ret = $matches[2] . "em";
		} else {
			return null;
		}

		return ( $matches[1] == "-" ? "-" : "" ) . $ret;
	}

	/**
	 * Some common steps of processing an input string before passing it as a key to the mappings.
	 * @param string $input string to be processed
	 * @return string prepared input string
	 */
	public static function inputPreparation( $input ): string {
		if ( $input === null ) {
			return "";
		}
		$input = trim( $input );
		if ( str_starts_with( $input, "\\" ) && strlen( $input ) >= 2 ) {
			$input = substr( $input, 1 );
			// These are edge cases where input can be a Literal OR an Operator
			$edgeCases = [ "S", "P", ";", ",", "!", "'", ">" ];
			if ( in_array( $input, $edgeCases, true ) ) {
				$input = "\\" . $input;
			}
		}
		return $input;
	}

	public static function createEntity( $code ): ?string {
		return IntlChar::chr( intval( $code, 16 ) );
	}

	/**
	 * From a defined mapping table get the value by key.
	 * Do some optional common conversion steps for the values.
	 * @param string $key key to find entry in table
	 * @param array $mappingTable table which the value is found by key
	 * @param bool $convertUc2X convert first value of the found entry in array to specific "&#x123;" notation
	 * @return mixed|string[]|null the found entry in the table
	 */
	public static function getMappingByKey( string $key, array $mappingTable, bool $convertUc2X = false ) {
		if ( isset( $mappingTable[$key] ) ) {
			$found = $mappingTable[$key];
			if ( is_string( $found ) ) {
				$found = [ $found ];
			}
			if ( $convertUc2X ) {
				$found[0] = self::uc2xNotation( $found[0] );
			}
			return $found;
		}
		return null;
	}

	/**
	 * From a defined mapping table get the value by key.
	 * @param string $key key to find entry in table
	 * @param array $mappingTable table which the value is found by key
	 * @return mixed|string[]|null the found entry in the table
	 */
	public static function getMappingByKeySimple( string $key, array $mappingTable ) {
		if ( isset( $mappingTable[$key] ) ) {
			return $mappingTable[$key];
		}
		return null;
	}

	/**
	 * Assumes the input curly contains an TexArray of literals, squashes the TexArray characters to a string.
	 * It checks for dollar escaping and removes a backslash, also renders DQ args with underscores
	 * @param TexNode $node curly containing a TexArray of literals
	 * @return ?string squashed string in example "2mu", "-3mu" etc. Null if no TexArray inside curly.
	 */
	public static function squashLitsToUnitIntent( TexNode $node ): ?string {
		if ( !$node instanceof Curly ) {
			return null;
		}
		$unit = "";
		foreach ( $node->getArg()->getArgs() as $literal ) {
			if ( $literal instanceof DQ ) {
				$args = $literal->getArgs();
				if ( !$args[0] instanceof Literal || !$args[1] instanceof Literal ) {
					continue;
				}
				$arg = self::removeDollarEscaping( $args[0]->getArgs()[0] ) . "_"
					. self::removeDollarEscaping( $args[1]->getArgs()[0] );
			} else {
				if ( !$literal instanceof Literal ) {
					continue;
				}
				$arg = self::removeDollarEscaping( $literal->getArg() );
			}
			$unit .= $arg;
		}
		return $unit;
	}

	public static function removeDollarEscaping( $input ) {
		if ( $input == "\\$" ) {
			return "\$";
		}
		return $input;
	}

}
