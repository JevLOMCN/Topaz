<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use IntlChar;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Misc;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Sizes;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmenclose;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmerror;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmfrac;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmmultiscripts;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmphantom;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmroot;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsqrt;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsub;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsup;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtable;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtd;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtr;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunder;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunderover;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Curly;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\FQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1nb;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2sq;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun4;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Matrix;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;

/**
 * Parsing functions for specific recognized mappings.
 * Usually the parsing functions are invoked from the BaseMethods classes.
 */
class BaseParsing {

	public static function accent( $node, $passedArgs, $name, $operatorContent, $accent, $stretchy = null ) {
		// Currently this is own implementation from Fun1.php
		// TODO The first if-clause is mathjax specific (and not necessary by generic parsers)
		// and will most probably removed (just for running all tc atm)
		if ( $accent == "00B4" || $accent == "0060" ) {
			$attrs = [ Tag::SCRIPTTAG => "true" ];
		} else {
			if ( $stretchy == null ) {
				// $attrs = [ "stretchy" => "false" ]; // not mention explicit stretchy
				$attrs = [];
			} else {
				$attrs = [ "stretchy" => "true" ];
			}
		}
		// Fetching entity from $accent key tbd
		$entity = MMLutil::createEntity( $accent );
		if ( !$entity ) {
			$entity = $accent;
		}

		$mrow = new MMLmrow();
		$mo = new MMLmo( "", $attrs ); // $passedArgs
		$mover = new MMLmover();
		$ret = $mrow->encapsulateRaw(
			$mrow->encapsulateRaw(
				$mover->encapsulateRaw(
					$node->getArg()->renderMML( $passedArgs ) .
					$mo->encapsulateRaw( $entity )
				)
			)
		);
		return $ret;
	}

	public static function array( $node, $passedArgs, $operatorContent, $name, $begin = null, $open = null,
								  $close = null, $align = null, $spacing = null,
								  $vspacing = null, $style = null, $raggedHeight = null ) {
		$output = "";
		$mrow = new MMLmrow();
		if ( $open != null ) {
			$resDelimiter = BaseMappings::getDelimiterByKey( trim( $open ) );
			if ( $resDelimiter ) {
				// $retDelim = $bm->checkAndParseDelimiter($open, $node,$passedArgs,true);
				$moOpen = new MMLmo( TexClass::OPEN );
				$output .= $moOpen->encapsulateRaw( $resDelimiter[0] );
			}
		}
		if ( $name == "Bmatrix" || $name == "bmatrix" || $name == "Vmatrix"
			|| $name == "vmatrix" || $name == "smallmatrix" || $name == "pmatrix" || $name == "matrix" ) {
			// This is a workaround and might be improved mapping BMatrix to Matrix directly instead of array
			return self::matrix( $node, $passedArgs, $operatorContent, $name,
				$open, $close, null, null, null, null, true );

		} else {
			$output .= $mrow->encapsulateRaw( $node->getMainarg()->renderMML() );
		}

		if ( $close != null ) {
			$resDelimiter = BaseMappings::getDelimiterByKey( trim( $close ) );
			if ( $resDelimiter ) {
				// $retDelim = $bm->checkAndParseDelimiter($open, $node,$passedArgs,true);
				$moClose = new MMLmo( TexClass::CLOSE );
				$output .= $moClose->encapsulateRaw( $resDelimiter[0] );
			}
		}
		return $output;
	}

	public static function alignAt( $node, $passedArgs, $operatorContent, $name, $smth, $smth2 = null ) {
		// Parsing is very similar to AmsEQArray, maybe extract function ... tcs: 178
		$mrow = new MMLmrow();
		// tbd how are the table args composed ?
		$tableArgs = [ "columnalign" => "right",
			"columnspacing" => "0em", "displaystyle" => "true", "rowspacing" => "3pt" ];
		$mtable  = new MMLmtable( "", $tableArgs );
		$mtr = new MMLmtr();
		$mtd = new MMLmtd();
		$renderedInner = "";

		$tableElements = array_slice( $node->getArgs(), 1 )[0];
		$discarded = false;
		foreach ( $tableElements->getArgs() as $tableRow ) {
			$renderedInner .= $mtr->getStart();
			foreach ( $tableRow->getArgs() as $tableCell ) {
				$renderedInner .= $mtd->getStart();
				foreach ( $tableCell->getArgs() as $cellItem ) {
					if ( !$discarded && $cellItem instanceof Curly ) {
						$discarded = true;
						// Just discard the number of rows atm, it is in the first Curly
					} else {
						$renderedInner .= $cellItem->renderMML(); // pass args here ?
					}
				}

				$renderedInner .= $mtd->getEnd();

			}
			$renderedInner .= $mtr->getEnd();
		}
		return $mrow->encapsulateRaw( $mtable->encapsulateRaw( $renderedInner ) );
	}

	public static function amsEqnArray( $node, $passedArgs, $operatorContent, $name, $smth, $smth2 = null ) {
		// this goes for name =="aligned" ... tcs: 358 420 421
		$mrow = new MMLmrow();
		// tbd how are the table args composed ?

		$direction = ( $name == "aligned" ) ? "left" : "right";
		$tableArgs = [ "columnalign" => $direction,
			"columnspacing" => "0em", "displaystyle" => "true", "rowspacing" => "3pt" ];
		$mtable  = new MMLmtable( "", $tableArgs );
		$mtr = new MMLmtr();
		$mtd = new MMLmtd();
		$renderedInner = "";
		$tableElements = array_slice( $node->getArgs(), 1 )[0];
		foreach ( $tableElements->getArgs() as $tableRow ) {
			$renderedInner .= $mtr->getStart();
			foreach ( $tableRow->getArgs() as $tableCell ) {
				$renderedInner .= $mtd->encapsulateRaw( $tableCell->renderMML() ); // pass args here ?
			}
			$renderedInner .= $mtr->getEnd();
		}
		return $mrow->encapsulateRaw( $mtable->encapsulateRaw( $renderedInner ) );
	}

	public static function boldsymbol( $node, $passedArgs, $operatorContent, $name, $smth = null, $smth2 = null ) {
		$mrow = new MMLmrow();
		$passedArgs = array_merge( [ "mathvariant" => Variants::BOLDITALIC ] );
		return $mrow->encapsulateRaw( $node->getArg()->renderMML( $passedArgs ) );
	}

	public static function cancel( $node, $passedArgs, $operatorContent, $name, $notation = null, $smth2 = null ) {
		$mrow = new MMLmrow();
		$menclose = new MMLmenclose( "", [ "notation" => $notation ] );
		return $mrow->encapsulateRaw( $menclose->encapsulateRaw( $node->getArg()->renderMML() ) );
	}

	public static function cancelTo( $node, $passedArgs, $operatorContent, $name, $notation = null ) {
		$mrow = new MMLmrow();
		$msup = new MMLmsup();
		$mpAdded = new MMLmpadded( "", [ "depth" => "-.1em", "height" => "+.1em", "voffset" => ".1em" ] );

		$menclose = new MMLmenclose( "", [ "notation" => $notation ] );
		$inner = $menclose->encapsulateRaw(
				$node->getArg2()->renderMML() ) . $mpAdded->encapsulateRaw( $node->getArg1()->renderMML() );
		return $mrow->encapsulateRaw( $msup->encapsulateRaw( $inner ) );
	}

	public static function chemCustom( $node, $passedArgs, $operatorContent, $name, $translation = null ) {
		if ( $translation ) {
			return $translation;
		}
		return "tbd chemCustom";
	}

	public static function customLetters( $node, $passedArgs, $operatorContent, $name, $char, $isOperator = false ) {
		$mrow = new MMLmrow();

		if ( $isOperator ) {
			$mo = new MMLmo();
			return $mrow->encapsulateRaw( $mo->encapsulateRaw( $char ) );
		}

		$mi = new MMLmi( "", [ "mathvariant" => "normal" ] );
		return $mrow->encapsulateRaw( $mi->encapsulateRaw( $char ) );
	}

	public static function cFrac( $node, $passedArgs, $operatorContent, $name ) {
		$mrow = new MMLmrow();
		$mfrac = new MMLmfrac();
		$mstyle = new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ] );
		$mpAdded = new MMLmpadded( "", [ "depth" => "3pt", "height" => "8.6pt", "width" => "0" ] );
		// See TexUtilMMLTest testcase 81
		// (mml3 might be erronous here, but this element seems to be rendered correctly)
		$whatIsThis = $mrow->getStart() . $mpAdded->getStart() . $mpAdded->getEnd() . $mrow->getEnd();
		$inner = $mrow->encapsulateRaw( $whatIsThis .
				$mstyle->encapsulateRaw( $mrow->encapsulateRaw( $node->getArg1()->renderMML() ) ) ) .
			$mrow->encapsulateRaw( $whatIsThis . $mstyle->encapsulateRaw(
					$mrow->encapsulateRaw( $node->getArg2()->renderMML() ) ) );

		return $mrow->encapsulateRaw( $mfrac->encapsulateRaw( $inner ) );
	}

	public static function crLaTeX( $node, $passedArgs, $operatorContent, $name ) {
		$mspace = new MMLmspace( "", [ "linebreak" => "newline" ] );
		return $mspace->getEmpty();
	}

	public static function dots( $node, $passedArgs, $operatorContent, $name, $smth = null, $smth2 = null ) {
		// lowerdots || centerdots seems aesthetical, just using lowerdots atm s
		$mo = new MMLmo( "", $passedArgs );
		return $mo->encapsulateRaw( "&#x2026;" );
	}

	public static function genFrac( $node, $passedArgs, $operatorContent, $name,
									$left = null, $right = null, $thick = null, $style = null ) {
		// Actually this is in AMSMethods, consider refactoring  left, right, thick, style
		$bm = new BaseMethods();
		$ret = $bm->checkAndParseDelimiter( $name, $node, $passedArgs, $operatorContent, true );
		if ( $ret ) {
			// TBD
			if ( $left == null ) {
				$left = $ret;
			}
			if ( $right == null ) {
				$right = $ret;
			}
			if ( $thick == null ) {
				$thick = $ret;
			}
			if ( $style == null ) {
				$style = trim( $ret );
			}
		}
		$attrs = [];
		$fract = null;
		$styleAttr = [];
		$displayStyle = "false";
		if ( in_array( $thick, [ 'thin', 'medium', 'thick', '0' ], true ) ) {
			$attrs = array_merge( $attrs, [ "linethickness" => $thick ] );
		}
		if ( $style !== '' ) {
			$styleDigit = intval( $style, 10 );
			$styleAlpha = [ 'D', 'T', 'S', 'SS' ][$styleDigit];
			if ( $styleAlpha == null ) {
				$mrow = new MMLmrow();
				return $mrow->encapsulateRaw( "Bad math style" );
			}

			if ( $styleAlpha === 'D' ) {
				// NodeUtil_js_1.default.setProperties(frac, { displaystyle: true, scriptlevel: 0 });

				// tbd add props
				$displayStyle = "true";
				$styleAttr = [ "minsize" => "2.047em" ];

			} else {
				$styleAttr = [ "minsize" => "1.2em" ];
			}

			/* @phan-suppress-next-line SecurityCheck-DoubleEscaped */
			$frac = new MMLmfrac( '', $attrs );
		} else {
			// NodeUtil_js_1.default.setProperties(frac, { displaystyle: false,
			//    scriptlevel: styleDigit - 1 });
			// tbd add props
			/* @phan-suppress-next-line SecurityCheck-DoubleEscaped */
			$frac = new MMLmfrac( '', $attrs );
			$styleAttr = [ "maxsize" => "1.2em", "minsize" => "1.2em" ];

		}
		$mrow = new MMLmrow();
		$mstyle = new MMLmstyle( "", [ "displaystyle" => $displayStyle, "scriptlevel" => "0" ] );
		$output = $mrow->getStart();
		if ( $style !== '' ) {
			$output .= $mstyle->getStart();
		}
		$output .= $mrow->getStart();
		if ( $left ) {
			$mrowOpen = new MMLmrow( TexClass::OPEN );
			$moL = new MMLmo( "", $styleAttr );
			$output .= $mrowOpen->encapsulateRaw( $moL->encapsulateRaw( $left ) );
		}
		$output .= $frac->encapsulateRaw( $mrow->encapsulateRaw( $node->getArg1()->renderMML() ) .
			$mrow->encapsulateRaw( $node->getArg2()->renderMML() ) );
		if ( $right ) {
			$mrowClose = new MMLmrow( TexClass::CLOSE );
			$moR = new MMLmo( "", $styleAttr );
			$output .= $mrowClose->encapsulateRaw( $moR->encapsulateRaw( $right ) );

		}
		$output .= $mrow->getEnd();
		if ( $style !== '' ) {
			$output .= $mstyle->getEnd();
		}
		$output .= $mrow->getEnd();

		return $output;
	}

	public static function frac( $node, $passedArgs, $operatorContent, $name ) {
		$mrow = new MMLmrow();
		$mfrac = new MMLmfrac();
		if ( $node instanceof Fun2 ) {
			$inner = $mrow->encapsulateRaw( $node->getArg1()->renderMML() ) .
				$mrow->encapsulateRaw( $node->getArg2()->renderMML() );
		} elseif ( $node instanceof DQ ) {
			$inner = $mrow->encapsulateRaw( $node->getBase()->renderMML() ) .
				$mrow->encapsulateRaw( $node->getDown()->renderMML() );
		} else {
			$inner = "";
			foreach ( $node->getArgs() as $arg ) {
				$rendered = is_string( $arg ) ? $arg : $arg->renderMML();
				$inner .= $mrow->encapsulateRaw( $rendered );
			}
		}
		return $mrow->encapsulateRaw( $mfrac->encapsulateRaw( $inner ) );
	}

	public static function hline( $node, $passedArgs, $operatorContent, $name,
								  $smth1 = null, $smth2 = null, $smth3 = null, $smth4 = null ) {
		// HLine is most probably not parsed this way, since only parsed in Matrix context
		$mmlRow = new MMLmrow( "tbd" );
		return $mmlRow->encapsulateRaw( "HLINE TBD" );
	}

	public static function hskip( $node, $passedArgs, $operatorContent, $name ) {
		if ( $node->getArg() instanceof Curly ) {
			$unit = MMLutil::squashLitsToUnit( $node->getArg() );
			if ( !$unit ) {
				return null;
			}
			$em = MMLutil::dimen2em( $unit );
		} else {
			// Prevent parsing in unmapped cases
			return null;
		}
		// Added kern j4t
		if ( $name == "mskip" || $name == "mkern" || "kern" ) {
			$args = [ "width" => $em ];
		} else {
			return null;
		}

		$mspace = new MMLmspace( "", $args );
		return $mspace->encapsulateRaw( "" );
	}

	public static function handleOperatorName( $node, $passedArgs, $operatorContent, $name ) {
		// In example "\\operatorname{a}"
		$mmlNot = "";
		if ( isset( $operatorContent['not'] ) && $operatorContent['not'] == true ) {
			$mmlNot = MMLParsingUtil::createNot();
		}
		$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::OP, "mathvariant" => Variants::NORMAL ] );
		return $mmlNot . $node->getArg()->renderMML( $passedArgs );
	}

	public static function lap( $node, $passedArgs, $operatorContent, $name ) {
		if ( !$node instanceof Fun1 ) {
			return null;
		}
		if ( $name == "rlap" ) {
			$args = [ "width" => "0" ];
		} elseif ( $name == "llap" ) {
			$args = [ "width" => "0", "lspace" => "-1width" ];
		} else {
			return null;
		}
		$mrow = new MMLmrow();
		$mpAdded = new MMLmpadded( "", $args );
		return $mrow->encapsulateRaw( $mpAdded->encapsulateRaw( $node->getArg()->renderMML() ) );
	}

	public static function macro( $node, $passedArgs, $operatorContent, $name, $macro, $argcount = null, $def = null ) {
		// Parse the Macro
		switch ( $name ) {
			case "mod":
				$mmlRow = new MMLmrow();
				$mo = new MMLmo( "", [ "lspace" => "2.5pt", "rspace" => "2.5pt" ] );
				$inner = $node->getArg() instanceof TexNode ? $node->getArg()->renderMML() : "";
				return $mmlRow->encapsulateRaw( $mo->encapsulate( "mod" ) . $inner );
			case "pmod":
				// tbd indicate in mapping that this is composed within php
				$mmlRow = new MMLmrow();
				$mspace = new MMLmspace( "", [ "width" => "0.444em" ] );
				$mspace2 = new MMLmspace( "", [ "width" => "0.333em" ] );
				$mo = new MMLmo( "", [ "stretchy" => "false" ] );
				$mi = new MMLmi();
				$inner = $node->getArg() instanceof TexNode ? $node->getArg()->renderMML() : "";

				return $mmlRow->encapsulateRaw( $mspace->encapsulate() .
					$mo->encapsulate( "(" ) . $mi->encapsulate( "mod" ) .
					$mspace2->encapsulate() . $inner . $mo->encapsulate( ")" ) );
			case "varlimsup":
			case "varliminf":
				// hardcoded macro in php (there is also a dynamic mapping which is not completely resolved atm)
				$mmlRow = new MMLmrow( TexClass::OP );
				if ( $name === "varlimsup" ) {
					$movu = new MMLmover();

				} else {
					$movu = new MMLmunder();
				}
				$mmlMi = new MMLmi();
				$mo = new MMLmo( "", [ "accent" => "true" ] );
				return $mmlRow->encapsulateRaw( $movu->encapsulateRaw(
					$mmlMi->encapsulateRaw( "lim" ) . $mo->encapsulateRaw( "&#x2015;" ) ) );

			case "varinjlim":
				$mmlRow = new MMLmrow( TexClass::OP );
				$mmlMunder = new MMLmunder();
				$mi = new MMLmi();
				$mo = new MMLmo();
				return $mmlRow->encapsulateRaw( $mmlMunder->encapsulateRaw(
					$mi->encapsulateRaw( "lim" ) .
					$mo->encapsulateRaw( "&#x2192;" ) )
				);
			case "varprojlim":
				$mmlRow = new MMLmrow( TexClass::OP );
				$mmlMunder = new MMLmunder();
				$mi = new MMLmi();
				$mo = new MMLmo();
				return $mmlRow->encapsulateRaw( $mmlMunder->encapsulateRaw(
					$mi->encapsulate( "lim" ) .
					$mo->encapsulateRaw( "&#x2190;" )
				) );
			case "stackrel":
				// hardcoded macro in php (there is also a dynamic mapping which is not not completely resolved atm)
				$mmlRow = new MMLmrow();
				$mmlRowInner = new MMLmrow( TexClass::REL );
				$mover = new MMLmover();
				$mmlRowArg2 = new MMLmrow( TexClass::OP );
				if ( $node instanceof DQ ) {
					$inner = $mover->encapsulateRaw( $mmlRowArg2->encapsulateRaw(
							$node->getBase()->renderMML() ) .
						$mmlRow->encapsulateRaw( $node->getDown()->renderMML() )
					);
				} else {
					$inner = $mover->encapsulateRaw( $mmlRowArg2->encapsulateRaw(
							$node->getArg2()->renderMML() ) .
						$mmlRow->encapsulateRaw( $node->getArg1()->renderMML() )
					);
				}
				return $mmlRow->encapsulateRaw( $mmlRowInner->encapsulateRaw( $inner ) );
			case "bmod":
				$mo = new MMLmo( "", [ "lspace" => Sizes::THICKMATHSPACE, "rspace" => Sizes::THICKMATHSPACE ] );
				$mmlRow = new MMLmrow( TexClass::ORD );
				$mspace = new MMLmspace( "", [ "width" => "0.167em" ] );
				$inner = $node->getArg() instanceof TexNode ?
					$mmlRow->encapsulateRaw( $node->getArg()->renderMML() ) : "";
				return $mmlRow->encapsulateRaw( $mo->encapsulate( "mod" ) .
					$inner . $mmlRow->encapsulateRaw( $mspace->getEmpty() ) );
			case "implies":
				$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
				$mspace = new MMLmspace( "", [ "width" => "0.278em" ] );
				$mo = new MMLmo();
				return $mstyle->encapsulateRaw( $mspace->getEmpty() ) . $mo->encapsulateRaw( "&#x27F9;" ) .
					$mstyle->encapsulateRaw( $mspace->getEmpty() );
			case "iff":
				$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
				$mspace = new MMLmspace( "", [ "width" => "0.278em" ] );
				$mo = new MMLmo();
				return $mstyle->encapsulateRaw( $mspace->getEmpty() ) . $mo->encapsulateRaw( "&#x27FA;" ) .
					$mstyle->encapsulateRaw( $mspace->getEmpty() );
			case "tripledash":
				// Using emdash for rendering here.
				$mo = new MMLmo();
				return $mo->encapsulateRaw( "&#x2014;" );
			case "longrightleftharpoons":
			case "longLeftrightharpoons":
			case "longRightleftharpoons":
				$texvc = new TexVC();
				$warnings = [];
				$checkRes = $texvc->check( $macro, [ "usemhchem" => true, "usemhchemtexified" => true ],
					$warnings, true );
				return $checkRes["input"]->renderMML();
			case "longleftrightarrows":
				// The tex-cmds used in makro are not supported, just use a hardcoded mml macro here.
				$mtext = new MMLmtext();
				$mrowRel = new MMLmrow( TexClass::REL );
				$mrowOrd = new MMLmrow( TexClass::ORD );
				$mrowOp = new MMLmrow( TexClass::OP );
				$mover = new MMLmover();
				$mpadded = new MMLmpadded( "", [ "height" => "0", "depth" => "0" ] );
				$mo = new MMLmo( "", [ "stretchy" => "false" ] );
				$mspace = new MMLmspace( "", [ "width" => "0px", "height" => ".25em",
					"depth" => "0px", "mathbackground" => "black" ] );
				return $mtext->encapsulateRaw( "&#xA0;" ) .
					$mrowRel->encapsulateRaw( $mover->encapsulateRaw(
						$mrowOp->encapsulateRaw(
							$mrowOrd->encapsulateRaw( $mpadded->encapsulateRaw(
								$mo->encapsulateRaw( "&#x27F5;" ) ) ) .
							$mspace->getEmpty() ) .
						$mrowOrd->encapsulateRaw(
							$mo->encapsulateRaw( "&#x27F6;" )
						) ) );
		}

		// Removed all token based parsing, since macro resolution for the supported macros can be hardcoded in php
		$mmlMrow = new MMLmrow();
		return $mmlMrow->encapsulate( "macro not resolved: " . $macro );
	}

	public static function matrix( $node, $passedArgs, $operatorContent,
								   $name, $open = null, $close = null, $align = null, $spacing = null,
								   $vspacing = null, $style = null, $cases = null, $numbered = null ) {
		$resInner = "";
		$mtr = new MMLmtr();
		$mtd = new MMLmtd();
		$addHlines = false;
		$columnInfo = [];
		// tbd hline element is the first literal element within second texarray -> resolve
		foreach ( $node->getMainarg()->getArgs() as $mainarg ) {
			$resInner .= $mtr->getStart();
			foreach ( $mainarg->getArgs() as $arg ) {
				$usedArg = clone $arg;
				if ( count( $arg->getArgs() ) >= 1 && $arg->getArgs()[0] instanceof Literal ) {
					// Discarding the column information Curly at the moment
					if ( $arg->getArgs()[0]->getArg() == "\\hline " ) {
						// discarding the hline
						// $usedArg->args[0] = null; // this does no work tbd
						$usedArg->pop();
						$addHlines = true;
					}
				}
				if ( count( $arg->getArgs() ) >= 1 && $arg->getArgs()[0] instanceof Curly ) {
					// Discarding the column information Curly at the moment
					// $usedArg->getArgs()[0] = null;
					$columnInfo = $usedArg->getArgs()[0]->render();
					$usedArg->pop();

				}
				$resInner .= $mtd->encapsulateRaw( $usedArg->renderMML( $passedArgs, [ 'inMatrix'
					=> true ]
				) );
			}
			$resInner .= $mtr->getEnd();
		}
		$mrow = new MMLmrow();
		$tableArgs = [ "columnspacing" => "1em", "rowspacing" => "4pt" ];
		$mencloseArgs = null;
		if ( $addHlines ) {
			// TBD this is just simple check, create a parsing function for hlines when there are more cases
			// solid as first val: hline for header row
			// none as second val: no hlines for follow up rows
			$tableArgs = array_merge( $tableArgs, [ "rowlines" => "solid none" ] );
		}
		if ( $columnInfo ) {
			// TBD this is just simple check, create a parsing function for hlines when there are more cases
			if ( str_contains( $columnInfo, "|" ) ) {
				$mencloseArgs = [ "data-padding" => "0", "notation" => "left right" ];
				// it seems this is creted when left and right is solely coming from columninfo
				$tableArgs = array_merge( $tableArgs, [ "columnlines" => "solid" ] );
			}
		}
		$mtable = new MMLmtable( "", $tableArgs );
		if ( $cases || ( $open != null && $close != null ) ) {
			$bm = new BaseMethods();
			$mmlMoOpen = $bm->checkAndParseDelimiter( $open, $node, [], [],
				true, TexClass::OPEN );
			if ( $mmlMoOpen == null ) {
				$open = MMLutil::inputPreparation( $open );
				$mmlMoOpen = new MMLmo( TexClass::OPEN, [] );
				$mmlMoOpen = $mmlMoOpen->encapsulateRaw( $open );
			}

			$closeAtts = [ "fence" => "true", "stretchy" => "true", "symmetric" => "true" ];
			$mmlMoClose = $bm->checkAndParseDelimiter( $close, $node, $closeAtts,
				null, true, TexClass::CLOSE );
			if ( $mmlMoOpen == null ) {
				$close = MMLutil::inputPreparation( $close );
				$mmlMoClose = new MMLmo( TexClass::CLOSE, $closeAtts );
				$mmlMoClose = $mmlMoClose->encapsulateRaw( $close );
			}
			$resInner = $mmlMoOpen . $mtable->encapsulateRaw( $resInner ) . $mmlMoClose;
		} else {
			$resInner = $mtable->encapsulateRaw( $resInner );
		}
		if ( $mencloseArgs ) {
			$menclose = new MMLmenclose( "", $mencloseArgs );
			$matrix = $mrow->encapsulateRaw( $menclose->encapsulateRaw( $resInner ) );

		} else {
			$matrix = $mrow->encapsulateRaw( $resInner );
		}
		return $matrix;
	}

	public static function namedOp( $node, $passedArgs, $operatorContent, $name, $id = null ) {
		/* Determine wether the named function should have an added apply function. The operatorContent is defined
		 as state in parsing of TexArray */
		$applyFct = "";
		if ( array_key_exists( "foundNamedFct", $operatorContent ) ) {
			$hasNamedFct = $operatorContent['foundNamedFct'][0];
			$hasValidParameters = $operatorContent["foundNamedFct"][1];
			if ( $hasNamedFct && $hasValidParameters ) {
				$applyFct = MMLParsingUtil::renderApplyFunction();
			}
		}

		if ( $node instanceof Literal ) {
			$mi = new MMLmi( "", $passedArgs );
			return $mi->encapsulateRaw( $id ?? $name ) . $applyFct;
		}
		$mrow = new MMLmrow( TexClass::ORD, [] );
		$msub = new MMLmsub( "", $passedArgs );
		return $msub->encapsulateRaw( $node->getBase()->renderMML() .
			$applyFct .
			$mrow->encapsulateRaw( $node->getDown()->renderMML() ) );
	}

	public static function over( $node, $passedArgs, $operatorContent, $name, $id = null ) {
		$attributes = [];
		$start = "";
		$tail = "";
		if ( $name === "atop" ) {
			$attributes = [ "linethickness" => "0" ];
		} elseif ( $name == "choose" ) {
			$mrowAll = new MMLmrow( TexClass::ORD );
			$mrowOpen = new MMLmrow( TexClass::OPEN );
			$mrowClose = new MMLmrow( TexClass::CLOSE );
			$mo = new MMLmo( "", [ "maxsize" => "1.2em", "minsize" => "1.2em" ] );
			$start = $mrowAll->getStart() . $mrowOpen->encapsulateRaw( $mo->encapsulate( "(" ) );
			$tail = $mrowClose->encapsulateRaw( $mo->encapsulate( ")" ) ) . $mrowAll->getEnd();
			$attributes = [ "linethickness" => "0" ];

		}
		$mfrac = new MMLmfrac( "", $attributes );

		$mrow = new MMLmrow( "", [] );
		if ( $node instanceof Fun2 ) {
			return $start . $mfrac->encapsulateRaw( $mrow->encapsulateRaw(
						$node->getArg1()->renderMML() ) . $mrow->encapsulateRaw( $node->getArg2()->renderMML() ) )
				. $tail;
		}
		$inner = "";
		foreach ( $node->getArgs() as $arg ) {
			if ( is_string( $arg ) && str_contains( $arg, $name ) ) {
				continue;
			}
			$rendered = $arg instanceof TexNode ? $arg->renderMML() : $arg;
			$inner .= $mrow->encapsulateRaw( $rendered );
		}

		return $start . $mfrac->encapsulateRaw( $inner ) . $tail;
	}

	public static function oint( $node, $passedArgs, $operatorContent,
								 $name, $uc = null, $attributes = null, $smth2 = null ) {
		// This is a custom mapping not in js.
		$mmlText = new MMLmtext( "", $attributes );
		$mrow = new MMLmrow();
		switch ( $name ) {
			case "oint":
				$mStyle = new MMLmstyle( "", [ "displaystyle" => "true" ] );
				$mo = new MMLmo();
				return $mStyle->encapsulateRaw( $mo->encapsulateRaw( MMLutil::uc2xNotation( $uc ) ) );
			case "\\P":
				$mo = new MMLmo();
				return $mo->encapsulateRaw( MMLutil::uc2xNotation( $uc ) );
			case "oiint":
			case "oiiint":
			case "ointctrclockwise":
			case "varointclockwise":
				$mStyle = new MMLmstyle( "", [ "mathsize" => "2.07em" ] );
				$mSpace = new MMLmspace( "", [ "width" => Sizes::THINMATHSPACE ] );
				return $mrow->encapsulateRaw( $mStyle->encapsulateRaw(
					$mmlText->encapsulateRaw( MMLutil::uc2xNotation( $uc ) )
					. $mSpace->getEmpty() ) );
			default:
				return $mmlText->encapsulate( "not found in OintMethod" );

		}
	}

	public static function overset( $node, $passedArgs, $operatorContent, $name, $id = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] ); // tbd remove mathjax specifics
		$mrow2 = new MMLmrow( "", [] );
		$mover = new MMLmover();

		if ( $node instanceof DQ ) {
			return $mrow->encapsulateRaw( $mover->encapsulateRaw( $mrow2->encapsulateRaw(
				$node->getDown()->renderMML() . $node->getDown()->renderMML() ) ) );
		} else {
			$inrow = $mrow2->encapsulateRaw( $node->getArg2()->renderMML() );
		}
		return $mrow->encapsulateRaw( $mover->encapsulateRaw( $inrow . $node->getArg1()->renderMML() ) );
	}

	public static function phantom( $node, $passedArgs, $operatorContent,
									$name, $vertical = null, $horizontal = null, $smh3 = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] );

		$attrs = [];
		if ( $vertical ) {
			$attrs = array_merge( $attrs, [ "width" => "0" ] );
		}
		if ( $horizontal ) {
			$attrs = array_merge( $attrs, [ "depth" => "0", "height" => "0" ] );
		}
		$mpadded = new MMLmpadded( "", $attrs );
		$mphantom = new MMLmphantom();
		return $mrow->encapsulateRaw( $mrow->encapsulateRaw(
			$mpadded->encapsulateRaw( $mphantom->encapsulateRaw( $node->getArg()->renderMML() ) ) ) );
	}

	public static function raiseLower( $node, $passedArgs, $operatorContent, $name ) {
		if ( !$node instanceof Fun2 ) {
			return null;
		}

		$arg1 = $node->getArg1();
		if ( $arg1 instanceof Curly ) {
			$unit = MMLutil::squashLitsToUnit( $arg1 );
			if ( !$unit ) {
				return null;
			}
			$em = MMLutil::dimen2em( $unit );
			if ( !$em ) {
				return null;
			}
		} else {
			return null;
		}

		if ( $name == "raise" ) {
			$args = [ "height" => MMLutil::addPreOperator( $em, "+" ),
				"depth" => MMLutil::addPreOperator( $em, "-" ),
				"voffset" => MMLutil::addPreOperator( $em, "+" ) ];
		} elseif ( $name == "lower" ) {
			$args = [ "height" => MMLutil::addPreOperator( $em, "-" ),
				"depth" => MMLutil::addPreOperator( $em, "+" ),
				"voffset" => MMLutil::addPreOperator( $em, "-" ) ];
		} else {
			// incorrect name, should not happen, prevent erroneous mappings from getting rendered.
			return null;
		}
		$mrow = new MMLmrow();
		$mpAdded = new MMLmpadded( "", $args );
		return $mrow->encapsulateRaw( $mpAdded->encapsulateRaw( $node->getArg2()->renderMML() ) );
	}

	public static function underset( $node, $passedArgs, $operatorContent, $name, $smh = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] );
		$inrow = $node->getArg2()->renderMML();
		$arg1 = $node->getArg1()->renderMML();
		if ( $inrow && $arg1 ) {
			$munder = new MMLmunder();
			return $mrow->encapsulateRaw( $munder->encapsulateRaw( $inrow . $arg1 ) );
		}

		// If there are no two elements in munder, not render munder
		return $mrow->encapsulateRaw( $inrow . $arg1 );
	}

	public static function underOver( $node, $passedArgs, $operatorContent,
									  $name, $operatorId = null, $stack = null, $nonHex = false ) {
		// tbd verify if stack interpreted correctly ?
		$texClass = $stack ? TexClass::OP : TexClass::ORD; // ORD or ""

		$mrow = new MMLmrow( $texClass );

		if ( $name[0] === 'o' ) {
			$movun = new MMLmover();
		} else {
			$movun = new MMLmunder();
		}

		if ( $operatorId == 2015 ) { // eventually move such cases to mapping
			$mo = new MMLmo( "", [ "accent" => "true" ] );
		} else {
			$mo = new MMLmo();
		}
		if ( $node instanceof DQ ) {
			$mrowI = new MMLmrow();
			return $movun->encapsulateRaw(
				$node->getBase()->renderMML( $passedArgs ) .
				$mrowI->encapsulateRaw( $node->getDown()->renderMML( $passedArgs ) )
			);
		}

		$inner = $nonHex ? $operatorId : MMLutil::number2xNotation( $operatorId );
		return $mrow->encapsulateRaw( $movun->encapsulateRaw(
			$node->getArg()->renderMML( $passedArgs ) .
			$mo->encapsulateRaw( $inner )
		) );
	}

	public static function mathFont( $node, $passedArgs, $operatorContent, $name, $mathvariant = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] );
		$args = MMLParsingUtil::getFontArgs( $name, $mathvariant, $passedArgs );
		$state = [];

		// Unicode fixes for the operators
		if ( $mathvariant == Variants::DOUBLESTRUCK ) {
			$state = [ "double-struck-literals" => true ];
		} elseif ( $mathvariant == Variants::CALLIGRAPHIC ) {
			$state = [ "calligraphic" => true ];
		} elseif ( $mathvariant == Variants::BOLDCALLIGRAPHIC ) {
			$state = [ "bold-calligraphic" => true ];
		}

		if ( $node instanceof Fun1nb ) {
			// Only one mrow from Fun1nb !?
			return $mrow->encapsulateRaw( $node->getArg()->renderMML( $args, $state ) );
		}
		return $mrow->encapsulateRaw( $mrow->encapsulateRaw( $node->getArg()->renderMML( $args, $state ) ) );
	}

	public static function mathChoice( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		if ( !$node instanceof Fun4 ) {
			$merror = new MMLmerror();
			return $merror->encapsulateRaw( "Wrong node type in mathChoice" );
		}

		/**
		 * Parametrization for mathchoice:
		 * \mathchoice
		 * {<material for display style>}
		 * {<material for text style>}
		 * {<material for script style>}
		 * {<material for scriptscript style>}
		 */

		if ( isset( $operatorContent["styleargs"] ) ) {
			$styleArgs = $operatorContent["styleargs"];
			$displayStyle = $styleArgs["displaystyle"] ?? "true";
			$scriptLevel = $styleArgs["scriptlevel"] ?? "0";

			if ( $displayStyle == "true" && $scriptLevel == "0" ) {
				// This is displaystyle
				return $node->getArg1()->renderMML( $passedArgs, $operatorContent );
			} elseif ( $displayStyle == "false" && $scriptLevel == "0" ) {
				// This is textstyle
				return $node->getArg2()->renderMML( $passedArgs, $operatorContent );
			} elseif ( $displayStyle == "false" && $scriptLevel == "1" ) {
				// This is scriptstyle
				return $node->getArg3()->renderMML( $passedArgs, $operatorContent );
			} elseif ( $displayStyle == "false" && $scriptLevel == "2" ) {
				// This is scriptscriptstyle
				return $node->getArg4()->renderMML( $passedArgs, $operatorContent );
			}
		}
		// By default render displaystyle
		return $node->getArg1()->renderMML( $passedArgs, $operatorContent );
	}

	public static function makeBig( $node, $passedArgs, $operatorContent, $name, $texClass = null, $size = null ) {
		// Create the em format and shorten commas
		$size *= Misc::P_HEIGHT;
		$sizeShortened = MMLutil::size2em( strval( $size ) );
		$mrowOuter = new MMLmrow( TexClass::ORD, [] );
		$mrow = new MMLmrow( $texClass, [] );
		$passedArgs = array_merge( $passedArgs, [ "maxsize" => $sizeShortened, "minsize" => $sizeShortened ] );
		$mo = new MMLmo( "", $passedArgs );
		// Sieve arg if it is a delimiter (it seems args are not applied here
		$bm = new BaseMethods();
		$argcurrent = trim( $node->getArg() );
		switch ( $argcurrent ) {
			case "\\|":
				$passedArgs = array_merge( $passedArgs, [ "symmetric" => "true" ] );
				break;
			case "\\uparrow":
			case "\\downarrow":
			case "\\Uparrow":
			case "\\Downarrow":
			case "\\updownarrow":
			case "\\Updownarrow":
				$passedArgs = array_merge( [ "fence" => "true" ], $passedArgs, [ "symmetric" => "true" ] );
				break;
			case "\\backslash":
			case "/":
				$passedArgs = array_merge( [ "fence" => "true" ],
					$passedArgs, [ "stretchy" => "true", "symmetric" => "true" ] );
				break;
		}

		if ( in_array( $name, [ "bigl", "Bigl", "biggl", "Biggl" ] ) ) {
			$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::OPEN ] );
		}

		if ( in_array( $name, [ "bigr", "Bigr", "biggr", "Biggr" ] ) ) {
			$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::CLOSE ] );
		}

		$ret = $bm->checkAndParseDelimiter( $node->getArg(), $node, $passedArgs, $operatorContent, true );
		if ( $ret ) {
			return $ret;
		}

		$argPrep = MMLutil::inputPreparation( $node->getArg() );
		return $mrowOuter->encapsulateRaw( $mrow->encapsulateRaw( $mo->encapsulateRaw( $argPrep ) ) );
	}

	public static function machine( $node, $passedArgs, $operatorContent, $name, $type = null ) {
		// this could also be shifted to MhChem.php renderMML for ce
		// For parsing chem (ce) or ??? (pu)
		$mmlMrow = new MMLmrow();
		return $mmlMrow->encapsulateRaw( $node->getArg()->renderMML() );
	}

	public static function namedFn( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		// Determine wether the named function should have an added apply function. The state is defined in
		// parsing of TexArray
		$applyFct = "";
		if ( array_key_exists( "foundNamedFct", $operatorContent ) ) {
			$hasNamedFct = $operatorContent['foundNamedFct'][0];
			$hasValidParameters = $operatorContent["foundNamedFct"][1];
			if ( $hasNamedFct && $hasValidParameters ) {
				$applyFct = MMLParsingUtil::renderApplyFunction();
			}
		}
		if ( $node instanceof Literal ) {
			$mi = new MMLmi();
			return $mi->encapsulateRaw( $name ) . $applyFct;
		}
		$mrow = new MMLmrow( TexClass::ORD, [] ); // tbd remove mathjax specifics
		$msub = new MMLmsub();
		return $msub->encapsulateRaw( $node->getBase()->renderMML() .
			$applyFct .
			$mrow->encapsulateRaw( $node->getDown()->renderMML() ) );
	}

	public static function limits( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		$argsOp = [ "form" => "prefix" ];
		if ( $operatorContent != null && array_key_exists( "styleargs", $operatorContent ) ) {
			if ( $operatorContent["styleargs"]["displaystyle"] === "false" ) {
				$argsOp = [ "movablelimits" => "true" ];
			}
			if ( $node->containsFunc( "\\nolimits" ) ) {
				$argsOp = [ "movablelimits" => "false" ];
			}
		}
		$mrow = new MMLmrow( TexClass::ORD, [] );
		$opParsed = ( $operatorContent != null && $operatorContent["limits"] )
			? $operatorContent["limits"]->renderMML( $argsOp ) : "";

		if ( $node instanceof DQ ) {
			$munder = new MMLmunder();
			return $munder->encapsulateRaw( $opParsed . $mrow->encapsulateRaw( $node->getDown()->renderMML() ) );
		} elseif ( $node instanceof FQ ) {
			$munderOver = new MMLmunderover();
			return $munderOver->encapsulateRaw( $opParsed . $mrow->encapsulateRaw( $node->getDown()->renderMML() )
				. $mrow->encapsulateRaw( $node->getUp()->renderMML() ) );
		}
	}

	public static function setFont( $node, $passedArgs, $operatorContent, $name, $variant = null ) {
		$mrow = new MMLmrow();
		$args = MMLParsingUtil::getFontArgs( $name, $variant, $passedArgs );
		return $mrow->encapsulateRaw( $mrow->encapsulateRaw( $node->getArg()->renderMML( $args ) ) );
	}

	public static function sideset( $node, $passedArgs, $operatorContent, $name ) {
		if ( !array_key_exists( "sideset", $operatorContent ) ) {
			$merror = new MMLmerror();
			return $merror->encapsulateRaw( "Error parsing sideset expression, no succeeding operator found" );
		}

		$mmlMrow = new MMLmrow( TexClass::OP );
		if ( $operatorContent["sideset"] instanceof Literal ) {
			$mmlMultiscripts = new MMLmmultiscripts( "", [ Tag::ALIGN => "left" ] );

			$bm = new BaseMethods();
			$opParsed = $bm->checkAndParseOperator( $operatorContent["sideset"]->getArg(), null, [], [], null );
			$in1 = $node->getArg1()->renderMML();
			$in2 = $node->getArg2()->renderMML();
			return $mmlMrow->encapsulateRaw( $mmlMultiscripts->encapsulateRaw( $opParsed .
				$in2 . "<mprescripts/>" . $in1 ) );
		}

		if ( $operatorContent["sideset"] instanceof FQ ) {
			$mmlMultiscripts = new MMLmmultiscripts( "", [] );
			$mmlMunderOver = new MMLmunderover();
			$mstyle = new MMLmstyle( "", [ "displaystyle" => "true" ] );
			$bm = new BaseMethods();
			if ( count( $operatorContent["sideset"]->getBase()->getArgs() ) == 1 ) {
				$opParsed = $bm->checkAndParseOperator( $operatorContent["sideset"]->getBase()->getArgs()[0],
					null, [ "largeop" => "true", "movablelimits" => "false", "symmetric" => "true" ], [], null );
			} else {
				$merror = new MMLmerror();
				$opParsed = $merror->encapsulateRaw( "Sideset operator parsing not implemented yet" );
			}

			$in1 = $node->getArg1()->renderMML();
			$in2 = $node->getArg2()->renderMML();

			$mrowEnd = new MMLmrow( "", [] );
			$end1 = $mrowEnd->encapsulateRaw( $operatorContent["sideset"]->getDown()->renderMML() );
			$end2 = $mrowEnd->encapsulateRaw( $operatorContent["sideset"]->getUp()->renderMML() );

			return $mmlMrow->encapsulateRaw( $mmlMunderOver->encapsulateRaw( $mstyle->encapsulateRaw(
					$mmlMultiscripts->encapsulateRaw( $opParsed . $in2 . "<mprescripts/>" . $in1 ) )
				. $end1 . $end2 ) );
		}

		$merror = new MMLmerror();
		return $merror->encapsulateRaw( "Error parsing sideset expression, no valid succeeding operator found" );
	}

	public static function spacer( $node, $passedArgs, $operatorContent, $name, $withIn = null, $smth2 = null ) {
		$width  = MMLutil::round2em( $withIn );
		$mspace = new MMLmspace( "", [ "width" => $width ] );
		return $mspace->encapsulate();
	}

	public static function smash( $node, $passedArgs, $operatorContent, $name ) {
		$mpArgs = [];
		$inner = "";
		if ( $node instanceof Fun2sq ) {
			$arg1 = $node->getArg1();
			$arg1i = "";
			if ( $arg1 instanceof Curly ) {
				$arg1i = $arg1->getArg()->render();
			}

			if ( str_contains( $arg1i, "{b}" ) ) {
				$mpArgs = [ "depth" => "0" ];
			}
			if ( str_contains( $arg1i, "{t}" ) ) {
				$mpArgs = [ "height" => "0" ];
			}
			if ( str_contains( $arg1i, "{tb}" ) || str_contains( $arg1i, "{bt}" ) ) {
				$mpArgs = [ "height" => "0", "depth" => "0" ];
			}

			$inner = $node->getArg2()->renderMML() ?? "";
		} elseif ( $node instanceof Fun1 ) {
			// Implicitly assume "tb" as default mode
			$mpArgs = [ "height" => "0", "depth" => "0" ];
			$inner = $node->getArg()->renderMML() ?? "";
		}
		$mrow = new MMLmrow();
		$mpAdded = new MMLmpadded( "", $mpArgs );
		return $mrow->encapsulateRaw( $mpAdded->encapsulateRaw( $inner ) );
	}

	public static function texAtom( $node, $passedArgs, $operatorContent, $name, $texClass = null ) {
		switch ( $name ) {
			case "mathclose":
				$mrow = new MMLmrow();
				$mrow2 = new MMLmrow( $texClass, [] );
				$inner = $node->getArg()->renderMML();
				return $mrow->encapsulateRaw( $mrow2->encapsulateRaw( $inner ) );
			case "mathbin":
				// no break
			case "mathop":
				// no break
			case "mathrel":
				$mrow2 = new MMLmrow( $texClass, [] );
				$inner = $node->getArg()->renderMML();
				return $mrow2->encapsulateRaw( $inner );
			default:
				$mrow = new MMLmrow( TexClass::ORD );
				$mrow2 = new MMLmrow( $texClass, [] );
				$inner = $node->getArg()->renderMML();
				return $mrow->encapsulateRaw( $mrow2->encapsulateRaw( $inner ) );
		}
	}

	public static function intent( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		if ( !$node instanceof Fun2 ) {
			return null;
		}
		// if there is intent annotation add intent to root element
		// match args in row of subargs, unless an element has explicit annotations
		// nested annotations ?
		$arg1 = $node->getArg1();
		if ( !$node->getArg2() instanceof Curly ) {
			return null;
		}
		// tbd refactor intent form and fiddle in mml or tree
		$intentStr = MMLutil::squashLitsToUnitIntent( $node->getArg2() );
		$intentContent = MMLParsingUtil::getIntentContent( $intentStr );
		$intentParams = MMLParsingUtil::getIntentParams( $intentContent );
		// Sometimes the intent has additioargs = {array[3]} nal args in the same string
		$intentArg = MMLParsingUtil::getIntentArgs( $intentStr );
		if ( !$intentContent && !$intentParams && isset( $intentArg ) ) {
			// explicit args annotation parsing in literal
			// return $arg1->renderMML([],["intent-params-expl"=>$intentArg]);
			// alternative just add the arg here
			return $arg1->renderMML( [ "arg" => $intentArg ] );
		}
		$intentContentAtr = [ "intent" => $intentContent ];
		if ( isset( $intentArg ) ) {
			$intentContentAtr["arg"] = $intentArg;
		}
		// tbd refine intent params and operator content merging (does it overwrite ??)
		$intentParamsState = $intentParams ? [ "intent-params" => $intentParams ] : $operatorContent;
		// Here are some edge cases, they might go into renderMML in the related element
		if ( str_contains( $intentContent, "matrix" ) ||
			( $arg1 instanceof Curly && $arg1->getArg()->getArgs()[0] instanceof Matrix ) ) {
			$element = $arg1->getArg()->getArgs()[0];
			$rendered = $element->renderMML( [], $intentParamsState );
			$hackyXML = MMLParsingUtil::forgeIntentToSpecificElement( $rendered,
				$intentContentAtr, "mtable" );
			return $hackyXML;
		} elseif ( $arg1 instanceof Curly && count( $arg1->getArg()->getArgs() ) >= 2 ) {
			// Create a surrounding element which holds the intents
			$mrow = new MMLmrow( "", $intentContentAtr );
			return $mrow->encapsulateRaw( $arg1->renderMML( [], $intentParamsState ) );
		} elseif ( $arg1 instanceof Curly && count( $arg1->getArg()->getArgs() ) >= 1 ) {
			// Forge the intent attribute to the top-level element after MML rendering
			$element = $arg1->getArg()->getArgs()[0];
			$rendered = $element->renderMML( [], $intentParamsState );
			$hackyXML = MMLParsingUtil::forgeIntentToTopElement( $rendered, $intentContentAtr );
			return $hackyXML;
		} else {
			// This is the default case
			return $arg1->renderMML( $intentContentAtr, $intentParamsState );
		}
	}

	public static function hBox( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		switch ( $name ) {
			case "mbox":
				$mo = new MMLmo();
				$mmlMrow = new MMLmrow();
				if ( $operatorContent != null && array_key_exists( "foundOC", $operatorContent ) ) {
					$op = MMLutil::inputPreparation( $operatorContent["foundOC"] );
					$macro = BaseMappings::getNullaryMacro( $op );
					if ( !$macro ) {
						$macro = BaseMappings::getIdentifierByKey( $op );
					}
					$input = $macro[0] ?? $operatorContent["foundOC"];
					return $mmlMrow->encapsulateRaw( $mo->encapsulateRaw( $input ) );
				} else {
					$mmlMrow = new MMLmrow();
					$mtext = new MMLmtext();
					return $mmlMrow->encapsulateRaw( $mtext->encapsulateRaw( "\mbox" ) );
				}
			case "hbox":
				$mmlMrow = new MMLmrow();
				$mstyle = new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ] );
				$mtext = new MMLmtext();
				$inner = $node->getArg() instanceof TexNode ? $node->getArg()->renderMML() : $node->getArg();
				return $mmlMrow->encapsulateRaw( $mstyle->encapsulateRaw( $mtext->encapsulateRaw( $inner ) ) );
			case "text":
				$mmlMrow = new MMLmrow();
				$mtext = new MMLmtext();
				$inner = $node->getArg() instanceof TexNode ? $node->getArg()->renderMML() : $node->getArg();
				return $mmlMrow->encapsulateRaw( $mtext->encapsulateRaw( $inner ) );
			case "textbf":
				// no break
			case "textit":
				// no break
			case "textrm":
				// no break
			case "textsf":
				// no break
			case "texttt":
				$mmlMrow = new MMLmrow();
				$mtext = new MMLmtext( "", MMLParsingUtil::getFontArgs( $name, null, null ) );

				$inner = $node->getArg() instanceof Curly ? $node->getArg()->getArg()->renderMML(
					[], [ "inHBox" => true ] )
					: $node->getArg()->renderMML( [ "fromHBox" => true ] );
				return $mmlMrow->encapsulateRaw( $mtext->encapsulateRaw( $inner ) );
		}

		$merror = new MMLmerror();
		// $node->getArg1()->renderMML() . $node->getArg2()->renderMML()
		return $merror->encapsulateRaw( "undefined hbox" );
	}

	public static function setStyle( $node, $passedArgs, $operatorContent, $name,
									 $smth = null, $smth1 = null, $smth2 = null ) {
		// Just discard setstyle since they are captured in TexArray now}
		return " ";
	}

	public static function not( $node, $passedArgs, $operatorContent, $name, $smth = null,
								$smth1 = null, $smth2 = null ) {
		// This is only tested for \not statement without follow-up parameters
		if ( $node instanceof Literal ) {
			return MMLParsingUtil::createNot();
		} else {
			$mError = new MMLmerror();
			return $mError->encapsulateRaw( "TBD implement not" );
		}
	}

	public static function vbox( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		// This is only example functionality for vbox("ab").
		// TBD: it should be discussed if vbox is supported since it
		// does not seem to be supported by mathjax
		if ( is_string( $node->getArg() ) ) {
			$mmlMover = new MMLmover();
			$mmlmrow = new MMLmrow();
			$arr1 = str_split( $node->getArg() );
			$inner = "";
			foreach ( $arr1 as $char ) {
				$inner .= $mmlmrow->encapsulateRaw( $char );
			}
			return $mmlMover->encapsulateRaw( $inner );
		}
		$mError = new MMLmerror();
		return $mError->encapsulateRaw( "no implemented vbox" );
	}

	public static function sqrt( $node, $passedArgs, $operatorContent, $name ) {
		$mrow = new MMLmrow();

		// There is an additional argument for the root
		if ( $node instanceof Fun2sq ) {
			$mroot = new MMLmroot();

			// In case of an empty curly add an mrow
			$arg2Rendered = $node->getArg2()->renderMML( $passedArgs );
			if ( trim( $arg2Rendered ) === "" ) {
				$arg2Rendered = $mrow->getEmpty();
			}
			return $mrow->encapsulateRaw(
				$mroot->encapsulateRaw(
					$arg2Rendered .
					$mrow->encapsulateRaw(
						$node->getArg1()->renderMML( $passedArgs )
					)
				)
			);
		}
		$msqrt = new MMLmsqrt();
		// Currently this is own implementation from Fun1.php
		return $mrow->encapsulateRaw( // assuming that this is always encapsulated in mrow
			$msqrt->encapsulateRaw(
				$node->getArg()->renderMML( $passedArgs )
			)
		);
	}

	public static function tilde( $node, $passedArgs, $operatorContent, $name ) {
		$mspace = new MMLmspace( "", [ "width" => "0.5em" ] );
		return $mspace->getEmpty();
	}

	public static function xArrow( $node, $passedArgs, $operatorContent, $name, $chr = null, $l = null, $r = null ) {
		$defWidth = "+" . MMLutil::round2em( ( $l + $r ) / 18 );
		$defLspace = MMLutil::round2em( $l / 18 );

		$mover = new MMLmover();
		$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
		$moArrow = new MMLmo( Texclass::REL, [] );
		$char = IntlChar::chr( $chr );

		$mpaddedArgs = [ "height" => "-.2em", "lspace" => $defLspace, "voffset" => "-.2em", "width" => $defWidth ];
		$mpadded = new MMLmpadded( "", $mpaddedArgs );
		$mspace = new MMLmspace( "", [ "depth" => ".25em" ] );
		if ( $node instanceof Fun2sq ) {
			$mmlMrow = new MMLmrow();
			$mmlUnderOver = new MMLmunderover();
			return $mmlMrow->encapsulateRaw( $mmlUnderOver->encapsulateRaw(
				$mstyle->encapsulateRaw( $moArrow->encapsulateRaw( $char ) ) .
				$mpadded->encapsulateRaw(
					$mmlMrow->encapsulateRaw(
						$node->getArg1()->renderMML()
					) .
					$mspace->encapsulate()
				) .
				$mpadded->encapsulateRaw(
					$node->getArg2()->renderMML()
				)
			) );

		}
		return $mover->encapsulateRaw(
			$mstyle->encapsulateRaw( $moArrow->encapsulateRaw( $char ) ) .
			$mpadded->encapsulateRaw(
				$node->getArg()->renderMML() .
				$mspace->encapsulate()
			)
		);
	}
}
