<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;

/**
 * This class contains functionalities for MML-node
 * parsing which can be extracted and are used
 * for multiple functions.
 */
class MMLParsingUtil {
	public static function renderApplyFunction() {
		$mo = new MMLmo();
		return $mo->encapsulateRaw( "&#x2061;" );
	}

	public static function getFontArgs( $name, $variant, $passedArgs ) {
		$args = [];
		switch ( $name ) {
			case "cal":
			case "mathcal":
				$args = [ Tag::MJXVARIANT => "-tex-calligraphic", "mathvariant" => Variants::SCRIPT ];
				break;
			case "it":
			case "mathit":
				$args = [ Tag::MJXVARIANT => $variant, "mathvariant" => Variants::ITALIC ];
				break;
			case "bf":
			case "mathbf":
				$args = [ "mathvariant" => $variant ];
				break;
			// Sstatements from here come from other fct ok ? otherwise create second fct
			case "textit":
				$args = [ "mathvariant" => Variants::ITALIC ];
				break;
			case "textbf":
				$args = [ "mathvariant" => Variants::BOLD ];
				break;
			case "textsf":
				$args = [ "mathvariant" => Variants::SANSSERIF ];
				break;
			case "texttt":
				$args = [ "mathvariant" => Variants::MONOSPACE ];
				break;
			case "textrm":
				break;
			case "emph":
				// Toggle by passed args in emph
				if ( isset( $passedArgs["mathvariant"] ) ) {
					if ( $passedArgs["mathvariant"] === Variants::ITALIC ) {
						$args = [ "mathvariant" => Variants::NORMAL ];
					}
				} else {
					$args = [ "mathvariant" => Variants::ITALIC ];
				}
				break;
			default:
				$args = [ "mathvariant" => $variant ];

		}
		return $args;
	}

	/**
	 * Parses an expression that defines a color; this is usually an argument in Literal.
	 * Example expression is: "\definecolor {ultramarine}{rgb}{0,0.12549019607843,0.37647058823529}"
	 * @param string $input tex-string, which contains the expression
	 * @return array|null either an array which contains hex of parsed expression or null if not parsable
	 */
	public static function parseDefineColorExpression( string $input ): ?array {
		$returnObj = null;
		$matches = [];
		$matched = preg_match_all( '/\{(.*?)\}/', $input, $matches );
		if ( !$matched ) {
			return null;
		}
		$ctr = count( $matches[1] ?? [] );

		if ( $ctr == 3 && $matches[1][1] === "rgb" ) {
			$returnObj = [];
			$rgbValues = explode( ",", $matches[1][2] );
			$r  = round( floatval( $rgbValues[0] ) * 255 );
			$g = round( floatval( $rgbValues[1] ) * 255 );
			$b = round( floatval( $rgbValues[2] ) * 255 );
			$color = sprintf( "#%02x%02x%02x", $r, $g, $b );
			$returnObj["name"] = $matches[1][0];
			$returnObj["type"] = "rgb";
			$returnObj["hex"] = $color;
		}

		return $returnObj;
	}

	/**
	 * Creates a negation block in MathML, usually preceding the negated statement
	 * @return string negation block as MathML
	 */
	public static function createNot() {
		$mmlMrow = new MMLmrow( TexClass::REL );
		$mpadded = new MMLmpadded( "", [ "width" => "0" ] );
		$mtext = new MMLmtext();
		return $mmlMrow->encapsulateRaw( $mpadded->encapsulateRaw( $mtext->encapsulateRaw( "&#x29F8;" ) ) );
	}

	public static function mapToDoubleStruckUnicode( $inputString ) {
		$map = [
			'0' => '&#x1D7D8;', '1' => '&#x1D7D9;', '2' => '&#x1D7DA;', '3' => '&#x1D7DB;', '4' => '&#x1D7DC;',
			'5' => '&#x1D7DD;', '6' => '&#x1D7DE;', '7' => '&#x1D7DF;', '8' => '&#x1D7E0;', '9' => '&#x1D7E1;',
			'A' => '&#x1D538;', 'B' => '&#x1D539;', 'C' => '&#x2102;', 'D' => '&#x1D53B;', 'E' => '&#x1D53C;',
			'F' => '&#x1D53D;', 'G' => '&#x1D53E;', 'H' => '&#x210D;', 'I' => '&#x1D540;', 'J' => '&#x1D541;',
			'K' => '&#x1D542;', 'L' => '&#x1D543;', 'M' => '&#x1D544;', 'N' => '&#x2115;', 'O' => '&#x1D546;',
			'P' => '&#x2119;', 'Q' => '&#x211A;', 'R' => '&#x211D;', 'S' => '&#x1D54A;', 'T' => '&#x1D54B;',
			'U' => '&#x1D54C;', 'V' => '&#x1D54D;', 'W' => '&#x1D54E;', 'X' => '&#x1D54F;', 'Y' => '&#x1D550;',
			'Z' => '&#x2124;', 'a' => '&#x1D552;', 'b' => '&#x1D553;', 'c' => '&#x1D554;', 'd' => '&#x1D555;',
			'e' => '&#x1D556;', 'f' => '&#x1D557;', 'g' => '&#x1D558;', 'h' => '&#x1D559;', 'i' => '&#x1D55A;',
			'j' => '&#x1D55B;', 'k' => '&#x1D55C;', 'l' => '&#x1D55D;', 'm' => '&#x1D55E;', 'n' => '&#x1D55F;',
			'o' => '&#x1D560;', 'p' => '&#x1D561;', 'q' => '&#x1D562;', 'r' => '&#x1D563;', 's' => '&#x1D564;',
			't' => '&#x1D565;', 'u' => '&#x1D566;', 'v' => '&#x1D567;', 'w' => '&#x1D568;', 'x' => '&#x1D569;',
			'y' => '&#x1D56A;', 'z' => '&#x1D56B;'
		];

		return self::matchAlphanumeric( $inputString, $map );
	}

	public static function mapToCaligraphicUnicode( $inputString ) {
		$map = [
			'0' => '&#x1D7CE;', '1' => '&#x1D7CF;', '2' => '&#x1D7D0;', '3' => '&#x1D7D1;', '4' => '&#x1D7D2;',
			'5' => '&#x1D7D3;', '6' => '&#x1D7D4;', '7' => '&#x1D7D5;', '8' => '&#x1D7D6;', '9' => '&#x1D7D7;',
			'A' => '&#x1D49C;', 'B' => '&#x212C;', 'C' => '&#x1D49E;', 'D' => '&#x1D49F;', 'E' => '&#x2130;',
			'F' => '&#x2131;', 'G' => '&#x1D4A2;', 'H' => '&#x210B;', 'I' => '&#x2110;', 'J' => '&#x1D4A5;',
			'K' => '&#x1D4A6;', 'L' => '&#x2112;', 'M' => '&#x2133;', 'N' => '&#x1D4A9;', 'O' => '&#x1D4AA;',
			'P' => '&#x1D4AB;', 'Q' => '&#x1D4AC;', 'R' => '&#x211B;', 'S' => '&#x1D4AE;', 'T' => '&#x1D4AF;',
			'U' => '&#x1D4B0;', 'V' => '&#x1D4B1;', 'W' => '&#x1D4B2;', 'X' => '&#x1D4B3;', 'Y' => '&#x1D4B4;',
			'Z' => '&#x1D4B5;', 'a' => '&#x1D4B6;', 'b' => '&#x1D4B7;', 'c' => '&#x1D4B8;', 'd' => '&#x1D4B9;',
			'e' => '&#x212F;', 'f' => '&#x1D4BB;', 'g' => '&#x210A;', 'h' => '&#x1D4BD;', 'i' => '&#x1D4BE;',
			'j' => '&#x1D4BF;', 'k' => '&#x1D4C0;', 'l' => '&#x1D4C1;', 'm' => '&#x1D4C2;', 'n' => '&#x1D4C3;',
			'o' => '&#x2134;', 'p' => '&#x1D4C5;', 'q' => '&#x1D4C6;', 'r' => '&#x1D4C7;', 's' => '&#x1D4C8;',
			't' => '&#x1D4C9;', 'u' => '&#x1D4CA;', 'v' => '&#x1D4CB;', 'w' => '&#x1D4CC;', 'x' => '&#x1D4CD;',
			'y' => '&#x1D4CE;', 'z' => '&#x1D4CF;'
		];

		return self::matchAlphanumeric( $inputString, $map );
	}

	public static function matchAlphanumeric( $inputString, $map ) {
		// Replace each character in the input string with its caligraphic Unicode equivalent
		return preg_replace_callback( '/[A-Za-z0-9]/u', static function ( $matches ) use ( $map ) {
			return $map[$matches[0]] ?? $matches[0];
		}, $inputString );
	}

	public static function getIntentContent( ?string $input ) {
		if ( !$input ) {
			return null;
		}
		$matchesInt = [];
		$matchInt = preg_match( "/intent=[\'\"](.*)[\'\"]/", $input, $matchesInt );
		if ( $matchInt && count( $matchesInt ) >= 2 ) {
			return $matchesInt[1];
		}
		return null;
	}

	public static function getIntentParams( ?string $intentContent ) {
		if ( !$intentContent ) {
			return null;
		}
		$matchesParams = [];
		// tbd eventually not only alphanumerical chars valid in intent params
		$matchParams = preg_match_all( "/\\\$([a-zA-Z]+)/", $intentContent, $matchesParams );
		if ( $matchParams && count( $matchesParams ) >= 2 ) {
			return $matchesParams[1];
		}
		return null;
	}

	public static function getIntentArgs( ?string $input ) {
		if ( !$input ) {
			return null;
		}
		$matchesArgs = [];
		$matchArg = preg_match( "/arg\s*=\s*[\'\"](.*?)[\'\"]/", $input, $matchesArgs );
		if ( $matchArg && count( $matchesArgs ) >= 2 ) {
			return $matchesArgs[1];
		}
		return null;
	}

	/**
	 * Converts a rendered MathML string to a XML tree and adds the attributes from input
	 * to the top-level element.Valid attributes for adding are "arg" and "intent.
	 * It overwrites pre-existing attributes in the top-level element.
	 * TBD: currently contains a hacky way to remove xml header in the output string
	 * example:" <msup intent="_($op,_of,$arg)">" intent attributes comes from input variables
	 * @param string $renderedMML defines input MathML string
	 * @param array $intentContentAtr defines attributes to add
	 * @return string MML with added attributes
	 */
	public static function forgeIntentToTopElement( string $renderedMML, $intentContentAtr ) {
		if ( !$intentContentAtr || !$renderedMML ) {
			return $renderedMML;
		}

		return self::addAttributesToMML( $renderedMML, $intentContentAtr, "" );
	}

	/**
	 * Add parameters from aattributes to the MML string
	 * @param string $renderedMML defines input MathML string
	 * @param array $intentContentAtr defines attributes to add
	 * @param string $elementTag element tag when using foundNodes
	 * @param bool $useFoundNodes use found nodes
	 * @return string MML with added attributes
	 */
	public static function addAttributesToMML( $renderedMML, $intentContentAtr, $elementTag, $useFoundNodes = false ) {
		$xml = simplexml_load_string( $renderedMML );
		if ( !$xml ) {
			return "";
		}
		if ( $useFoundNodes ) {
			$foundNodes = $xml->xpath( $elementTag );
			if ( !( isset( $foundNodes ) && count( $foundNodes ) >= 1 ) ) {
				return $renderedMML;
			}
		}

		if ( isset( $intentContentAtr["intent"] ) ) {
			if ( isset( $xml["intent"] ) ) {
				$xml["intent"] = $intentContentAtr["intent"];
			} elseif ( $intentContentAtr["intent"] != null && is_string( $intentContentAtr["intent"] ) ) {
				$xml->addAttribute( "intent", $intentContentAtr["intent"] );
			}
		}
		if ( isset( $intentContentAtr["arg"] ) ) {
			if ( isset( $xml["arg"] ) ) {
				$xml["arg"] = $intentContentAtr["arg"];
			} elseif ( $intentContentAtr["arg"] != null && is_string( $intentContentAtr["arg"] ) ) {
				$xml->addAttribute( "arg", $intentContentAtr["arg"] );
			}
		}

		$hackyXML = str_replace( "<?xml version=\"1.0\"?>", "", $xml->asXML() );
		return str_replace( "\n", "", $hackyXML );
	}

	public static function forgeIntentToSpecificElement( string $renderedMML, $intentContentAtr, string $elementTag ) {
		if ( !$intentContentAtr || !$renderedMML || !$elementTag ) {
			return $renderedMML;
		}
		return self::addAttributesToMML( $elementTag, $intentContentAtr, $elementTag, true );
	}

}
