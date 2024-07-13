<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmerror;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;

/**
 * This contains the basic parsing methods for tex elements, which get invoked
 * to check if there is a specific parsing function defined in the mappings
 * and then forward to the parsing function.
 *
 * Much of this is WIP since there are many cases.
 * @author Johannes Stegmüller
 */
class BaseMethods {

	public static function checkAndParse( $input, $passedArgs, $operatorContent, TexNode $node, $prepareInput = true ) {
		if ( !is_string( $input ) ) {
			// just discard these elements, sometimes empty TexArray
			return null;
		}
		if ( $prepareInput ) {
			$input = MMLutil::inputPreparation( $input );
		}

		// Checking for a named parsing function
		$resFct = BaseMappings::getMacroByKey( $input );
		if ( $resFct == null ) {
			$resFct = AMSMappings::getMacroByKey( $input );
			if ( $resFct == null ) {
				// Also check for mathtools environment, this is currently done to find some form of matrices,
				// probably refactored later
				$resFct = AMSMappings::getEnvironmentByKey( $input );
				if ( $resFct == null ) {
					$resFct = BaseMappings::getCustomByKey( $input );
					if ( $resFct == null ) {
						$resFct = BaseMappings::getSpecialByKey( $input );
						if ( $resFct == null ) {
							$resFct = BaseMappings::getCancelByKey( $input );
							if ( $resFct == null ) {
								$resFct = BaseMappings::getMhChemByKey( $input );
							}
						}
					}
				}
			}
		}
		if ( $resFct == null ) {
			return null;
		}
		// If the function has been found, dynamically call the associated parsing function.
		if ( is_string( $resFct ) ) {
			$resFct = [ $resFct ];
		}
		try {
			// Passing resolved function as param without first id
			if ( count( $resFct ) > 1 ) {
				$shifted = array_shift( $resFct );
				return BaseParsing::{$shifted}( $node, $passedArgs, $operatorContent, $input, ...$resFct );
			} else {
				return BaseParsing::{$resFct[0]}( $node, $passedArgs, $operatorContent, $input );
			}
		} catch ( \Exception $exception ) {
			return null;
		}
	}

	public function checkAndParseOperator( $input, $node, $passedArgs, $operatorContent,
										   $state, $prepareInput = true ) {
		if ( $prepareInput ) {
			$input = MMLutil::inputPreparation( $input );
		}
		$resOperator = BaseMappings::getOperatorByKey( $input );
		if ( $resOperator == null ) {

			$resOperator = AMSMappings::getOperatorByKey( $input );
			if ( $resOperator == null ) {
				$resOperator = OperatorDictionary::getOperatorByKey( $input );
				if ( $resOperator ) {
					if ( isset( $resOperator[1] ) ) {
						// custom parsing here
						return $this->parseOperatorDict( $node, $passedArgs, $operatorContent, $input, false );
					}
					// Atm just do simple parsing for elements in operator dictionary
					$mmlMo = new MMLmo();
					return $mmlMo->encapsulateRaw( $input );
				}
			}
		}
		// If the macro has been found, dynamically call the associated parsing function.
		if ( is_string( $resOperator ) ) {
			$resOperator = [ $resOperator ];
		}

		if ( $resOperator == null ) {
			return null;
		}
		try {
			return $this->parseOperator( $node, $passedArgs, $operatorContent, $input, $state, ...$resOperator );

		} catch ( ArgumentCountError $errArgcount ) {
			return null;
		}
	}

	public function parseOperatorDict( $node, $passedArgs, $operatorContent, $input, $uc = null, $attrs = [] ) {
		// Some custom parsing from operatorDict
		switch ( $input ) {
			case ";":
			case ",":
				// this maybe just a default case, this is not rendered when it is the last in row
				$mmlMo = new MMLmo();
				return $mmlMo->encapsulate( $input );
			case "<":
				$mmlMo = new MMLmo();
				return $mmlMo->encapsulateRaw( "&lt;" );
			case ">":
				$mmlMo = new MMLmo();
				return $mmlMo->encapsulateRaw( "&gt;" );
			case "\\":
				 // instead of carriage return, force whitespace here:
				 // see: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Math/+/961213
				$mspace = new MMLmspace( "", [ "width" => "0.5em" ] );
				return $mspace->getEmpty();
		}
		return $input;
	}

	public function parseOperator( $node, $passedArgs, $operatorContent, $name, $state, $uc = null, $attrs = [] ) {
		// if($name == "equiv" || $name == "dotplus" || $name == "mp"  || $name == "pm"){
		$attrs = array_merge( $passedArgs, $attrs ); // this is rather a workaround
		$mo = new MMLmo( "", $attrs );

		if ( $state != null && array_key_exists( "not", $state ) && $state["not"] ) {
			$text = $mo->encapsulateRaw( $uc . "&#x338;" );
		} else {
			$text = $mo->encapsulateRaw( $uc );
		}

		// Some attributes are nnot used which come from the mapping, tbd refactor this
		$text = str_replace( " largeop=\"\"", "", $text );
		$text = str_replace( "variantForm=\"True\"", "data-mjx-alternate=\"1\"", $text );
		$text = str_replace( "variantForm=\"1\"", "data-mjx-alternate=\"1\"", $text );
		$text = str_replace( " movesupsub=\"1\"", "", $text );
		return str_replace( "texClass", "data-mjx-texclass", $text );
	}

	public function checkAndParseIdentifier( $input, $node, $passedArgs, $operatorContent, $prepareInput = true ) {
		if ( $prepareInput ) {
			$input = MMLutil::inputPreparation( $input );
		}
		$resIdentifier = BaseMappings::getIdentifierByKey( $input );
		if ( $resIdentifier == null ) {
			$resIdentifier = AMSMappings::getIdentifierByKey( $input );
		}
		// If the macro has been found, dynamically call the associated parsing function.
		if ( is_string( $resIdentifier ) ) {
			$resIdentifier = [ $resIdentifier ];
		}

		if ( $resIdentifier == null ) {
			return null;
		}
		try {
			return $this->parseIdentifier( $node, $passedArgs, $operatorContent, $input, ...$resIdentifier );
		} catch ( ArgumentCountError $errArgcount ) {
			return null;
		}
	}

	public function parseIdentifier( $node, $passedArgs, $operatorContent, $name, $uc = null, $attrs = [] ) {
		// tbd verify rule: Lowercase name ("operator" instead "Operator") seems to
		// indicate additional italic mathvariant when bold already
		if ( !ctype_upper( $name ) ) {
			if ( isset( $passedArgs["mathvariant"] ) && $passedArgs["mathvariant"] ) {
				$passedArgs["mathvariant"] = $passedArgs["mathvariant"] . "-" . Variants::ITALIC;
			}
		}

		$args = array_merge( $passedArgs, $attrs );
		$mi = new MMLmi( "", $args );
		$text = $mi->encapsulateRaw( $uc );
		// TODO refactor just for test
		$text = str_replace( "variantForm=\"True\"", "data-mjx-alternate=\"1\"", $text );
		$text = str_replace( "variantForm=\"1\"", "data-mjx-alternate=\"1\"", $text );
		return str_replace( "texClass", "data-mjx-texclass", $text );
	}

	public function checkAndParseDelimiter( $input, $node, $passedArgs,
											$operatorContent, $noargs = false, $texClass = "" ) {
		if ( $input === null ) {
			return null;
		}
		$resDelimiter = BaseMappings::getDelimiterByKey( trim( $input ) );

		if ( $resDelimiter == null ) {
			$input = MMLutil::inputPreparation( $input );
			$resDelimiter = AMSMappings::getSymbolDelimiterByKey( $input );
			if ( $resDelimiter == null ) {
				$resDelimiter = AMSMappings::getMathDelimiterByKey( $input );
				if ( $resDelimiter == null ) {
					return null;
				}
			}
		}
		if ( is_string( $resDelimiter ) ) {
			$resDelimiter = [ $resDelimiter ];
		} else {
			if ( isset( $resDelimiter[1] ) && is_array( $resDelimiter[1] ) && !$noargs ) {
				$passedArgs = array_merge( $resDelimiter[1], $passedArgs );
			}
		}

		$mo = new MMLmo( $texClass, $passedArgs );
		return $mo->encapsulateRaw( $resDelimiter[0] );
	}

	public function checkAndParseMathCharacter( $input, $node, $passedArgs, $operatorContent, $prepareInput = true ) {
		if ( $prepareInput ) {
			$input = MMLutil::inputPreparation( $input );
		}
		$resChar = BaseMappings::getCharacterByKey( $input );
		if ( $resChar == null ) {
			return null;
		}

		// Maybe move this to the mapping
		$args = [ "mathvariant" => "normal" ];

		$mi = new MMLmi( "", $args );
		$enc = MMLutil::uc2xNotation( $resChar );
		return $mi->encapsulateRaw( $enc );
	}

	public function checkAndParseColor( $input, $node, $passedArgs, $operatorContent, $prepareInput = true ) {
		// tbd usually this encapsulates the succeeding box element
		if ( $operatorContent == null ) {
			return null;
		}

		if ( $prepareInput ) {
			$input = MMLutil::inputPreparation( $input );
		}
		if ( !( $input === 'color' || $input === 'pagecolor' ) ) {
			return null;
		}

		$resColor = BaseMappings::getColorByKey( $operatorContent );
		if ( $resColor == null ) {
			return null;
		}
		if ( is_array( $resColor ) ) {
			$resColor = $resColor[0]; // tbd refactor or correct mappings
		}

		if ( $input === 'color' ) {
			$mstyle = new MMLmstyle( "", [ "mathcolor" => $resColor ] );
			return $mstyle->encapsulate();
		} else {
			// Input is 'pagecolor'
			$mtext = new MMLmtext( "", [ "mathcolor" => $resColor ] );
			$mrow = new MMLmrow();
			$mi = new MMLmi();
			// Mj3 does this, probably not necessary
			$innerRow = "";
			foreach ( str_split( $operatorContent ) as $char ) {
				$innerRow .= $mi->encapsulateRaw( $char );
			}
			if ( $innerRow !== "" ) {
				return $mtext->encapsulate( "\\pagecolor" ) . $mrow->encapsulateRaw( $innerRow );
			} else {
				return $mtext->encapsulate( "\\pagecolor" );
			}
		}
	}

	public static function generateMMLError( $msg ): string {
		return ( new MMLmerror() )->encapsulateRaw(
			( new MMLmtext() )->encapsulate( $msg )
		);
	}
}
