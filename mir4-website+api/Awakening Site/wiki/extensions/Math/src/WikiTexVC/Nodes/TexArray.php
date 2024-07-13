<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMappings;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsup;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class TexArray extends TexNode {

	public function __construct( ...$args ) {
		$nargs = [];

		foreach ( $args as &$arg ) {
			if ( $arg !== null ) {
				array_push( $nargs, $arg );
			}
		}

		self::checkInput( $nargs );
		parent::__construct( ...$nargs );
	}

	public function checkForStyleArgs( $node ) {
		if ( $node instanceof Literal ) {
			$name = trim( $node->getArg() );
			switch ( $name ) {
				case "\\displaystyle":
					return [ "displaystyle" => "true", "scriptlevel" => "0" ];
				case "\\scriptstyle":
					return [ "displaystyle" => "false", "scriptlevel" => "1" ];
				case "\\scriptscriptstyle":
					return [ "displaystyle" => "false", "scriptlevel" => "2" ];
				case "\\textstyle":
					return [ "displaystyle" => "false", "scriptlevel" => "0" ];
			}
		}
		return null;
	}

	/**
	 * Checks if an TexNode of Literal contains color information (color, pagecolor)
	 * and returns info how to continue with the parsing.
	 * @param TexNode $node node to check if it contains color info
	 * @return array index 0: (bool) was color element found, index 1: (string) specified color
	 */
	public function checkForColor( TexNode $node ) {
		if ( $node instanceof Literal ) {
			$name = trim( $node->getArg() );
			if ( str_contains( $name, "\\color" ) ) {
				$foundOperatorContent = MMLutil::initalParseLiteralExpression( $node->getArg() );
				if ( !$foundOperatorContent ) {
					// discarding color elements which not specify color
					return [ true, null ];
				} else {
					return [ true, $foundOperatorContent[2][0] ];
				}
			} elseif ( str_contains( $name, "\\pagecolor" ) ) {
				return [ true, null ];
			}
		}
		return [ false, null ];
	}

	public function checkForColorDefinition( TexNode $node ): ?array {
		if ( $node instanceof Literal ) {
			$name = trim( $node->getArg() );
			if ( str_contains( $name, "\\definecolor" ) ) {
				return MMLParsingUtil::parseDefineColorExpression( $node->getArg() );
			}
		}
		return null;
	}

	/**
	 * Checks two sequential nodes in TexArray if they contain information on sideset expressions.
	 * @param TexNode $currentNode first node in array to check (for sideset expression)
	 * @param TexNode|null $nextNode second node in array to check (for succeeding operator)
	 * @return TexNode|null the succeeding operator for further Parsing or null if sideset not found or invalid
	 */
	public function checkForSideset( TexNode $currentNode, ?TexNode $nextNode ): ?TexNode {
		if ( !( $currentNode instanceof Fun2nb && $currentNode->getFname() == "\\sideset" ) ) {
			return null;
		}
		if ( $nextNode instanceof Literal ) {
			return $nextNode;
		}
		if ( $nextNode instanceof FQ ) {
			return $nextNode;
		}
		return null;
	}

	public function checkForLimits( $currentNode, $nextNode ) {
		// Preceding 'lim' in example: "\\lim_{x \\to 2}"
		if ( ( $currentNode instanceof DQ || $currentNode instanceof FQ )
			&& $currentNode->containsFunc( "\\lim" ) ) {

			if ( $currentNode->getBase() instanceof TexArray ) {
				return [ $currentNode->getBase()->getArgs()[0], false ];
			} else {
				return [ $currentNode->getBase(), false ];
			}
		}

		/** Find cases which have preceding Literals with nullary_macro-type operators i.e.:
		 * "\iint\limits_D \, dx\,dy"
		 */
		$tu = TexUtil::getInstance();

		// Check whether the current node is a possible preceding literal
		if ( !( $currentNode instanceof Literal
			&& ( $tu->nullary_macro( trim( $currentNode->getArg() ) )
			|| trim( $currentNode->getArg() ) == "\\lim" ) ) ) {
			return [ null, false ];
		}

		// Check whether the next node is a possible limits construct
		if ( !( ( $nextNode instanceof DQ || $nextNode instanceof FQ )
			&& $nextNode->getBase() instanceof Literal
			&& ( $nextNode->containsFunc( "\\limits" ) || $nextNode->containsFunc( "\\nolimits" ) )
			) ) {
			return [ null, false ];

		}
		return [ $currentNode, true ];
	}

	public function checkForNot( $currentNode ): bool {
		if ( $currentNode instanceof Literal && trim( $currentNode->getArg() ) == "\\not" ) {
			return true;
		}
		return false;
	}

	public function checkForDerivatives( $iStart, $args ): int {
		$ctr = 0;
		for ( $i = $iStart, $count = count( $this->args ); $i < $count; $i++ ) {
			$followUp = $args[$i];
			if ( $followUp instanceof Literal && $followUp->getArg() === "'" ) {
				$ctr++;
			} else {
				break;
			}
		}

		return $ctr;
	}

	public function checkForNamedFctArgs( $currentNode, $nextNode ) {
		// Check if current node is named function
		$hasNamedFct = false;
		if ( $currentNode instanceof TexArray && count( $currentNode->args ) == 2 ) {
			$tu = TexUtil::getInstance();
			$currentNodeContent = $currentNode->getArgs()[0];
			if ( $currentNodeContent instanceof Literal &&
				$tu->latex_function_names( $currentNodeContent->getArg() ) ) {
				$hasNamedFct = true;
			}
		}

		// Check if there is a valid argument as next parameter
		$hasValidParameters = false;
		if ( !$hasNamedFct ) {
			return [ $hasNamedFct, $hasValidParameters ];
		}

		if ( $nextNode ) {
			$hasValidParameters = true;
		}

		return [ $hasNamedFct, $hasValidParameters ];
	}

	public function renderMML( $arguments = [], $state = [] ) {
		// Everything here is for parsing displaystyle, probably refactored to WikiTexVC grammar later
		$fullRenderedArray = "";
		$mmlStyles = [];
		$currentColor = null;

		for ( $i = 0, $count = count( $this->args ); $i < $count; $i++ ) {
			$current = $this->args[$i];
			if ( isset( $this->args[$i + 1] ) ) {
				$next = $this->args[$i + 1];
			} else {
				$next = null;
			}
			// Check for sideset
			$foundSideset = $this->checkForSideset( $current, $next );
			if ( $foundSideset ) {
				$state["sideset"] = $foundSideset;
				// Skipping the succeeding Literal
				$i++;
			}

			// Check for limits
			$foundLimits = $this->checkForLimits( $current, $next );
			if ( $foundLimits[0] ) {
				$state["limits"] = $foundLimits[0];
				if ( $foundLimits[1] ) {
					continue;
				}
			}

			// Check for Not
			$foundNot = $this->checkForNot( $current );
			if ( $foundNot ) {
				$state["not"] = true;
				continue;
			}

			// Check for derivatives
			$foundDeriv = $this->checkForDerivatives( $i + 1, $this->args );
			if ( $foundDeriv > 0 ) {
				// skip the next indices which are derivative characters
				$i += $foundDeriv;
				$state["deriv"] = $foundDeriv;
			}

			// Check if there is a new color definition and add it to state
			$foundColorDef = $this->checkForColorDefinition( $current );
			if ( $foundColorDef ) {
				$state["colorDefinitions"][$foundColorDef["name"]] = $foundColorDef;
				continue;
			}
			// Pass preceding color info to state
			$foundColor = $this->checkForColor( $current );
			if ( $foundColor[0] ) {
				$currentColor = $foundColor[1];
				// Skipping the color element itself for rendering
				continue;
			}
			$styleArguments = $this->checkForStyleArgs( $current );

			$foundNamedFct = $this->checkForNamedFctArgs( $current, $next );
			if ( $foundNamedFct[0] ) {
				$state["foundNamedFct"] = $foundNamedFct;
			}

			if ( $styleArguments ) {
				$state["styleargs"] = $styleArguments;
				if ( $next instanceof Curly ) {
					// Wrap with style-tags when the next element is a Curly which determines start and end tag.
					$mmlStyle = new MMLmstyle( "", $styleArguments );
					$fullRenderedArray .= $mmlStyle->getStart();
					$fullRenderedArray .= $this->createMMLwithContext( $currentColor, $next, $state, $arguments );
					$fullRenderedArray .= $mmlStyle->getEnd();
					$mmlStyle = null;
					unset( $state["styleargs"] );
					$i++;
				} else {
					// Start the style indicator in cases like \textstyle abc
					$mmlStyle = new MMLmstyle( "", $styleArguments );
					$fullRenderedArray .= $mmlStyle->getStart();
					$mmlStyles[] = $mmlStyle->getEnd();

				}
			} else {
				$fullRenderedArray .= $this->createMMLwithContext( $currentColor, $current, $state, $arguments );
			}

			if ( array_key_exists( "not", $state ) ) {
				unset( $state["not"] );
			}
			if ( array_key_exists( "limits", $state ) ) {
				unset( $state["limits"] );
			}
			if ( array_key_exists( "deriv", $state ) ) {
				unset( $state["deriv"] );
			}
		}

		foreach ( array_reverse( $mmlStyles ) as $mmlStyleEnd ) {
			$fullRenderedArray .= $mmlStyleEnd;
		}

		return $fullRenderedArray;
	}

	private function createMMLwithContext( $currentColor, $currentNode, $state, $arguments ) {
		if ( $currentColor ) {
			if ( array_key_exists( "colorDefinitions", $state )
				&& is_array( $state["colorDefinitions"] )
				&& array_key_exists( $currentColor, $state["colorDefinitions"] ?? [] )
				&& is_array( $state["colorDefinitions"][$currentColor] )
				&& array_key_exists( "hex", $state["colorDefinitions"][$currentColor] )
			   ) {
				$displayedColor = $state["colorDefinitions"][$currentColor]["hex"];

			} else {
				$resColor = BaseMappings::getColorByKey( $currentColor );
				$displayedColor = $resColor ? $resColor[0] : $currentColor;
			}
			$mmlStyleColor = new MMLmstyle( "", [ "mathcolor" => $displayedColor ] );
			$ret = $mmlStyleColor->encapsulateRaw( $currentNode->renderMML( $arguments, $state ) );
		} else {
			$ret = $currentNode->renderMML( $arguments, $state );
		}

		return $this->addDerivativesContext( $state, $ret );
	}

	/**
	 * If derivative was recognized, add the corresponding derivative math operator
	 * to the mml and wrap with msup element.
	 * @param array $state state indicator which indicates derivative
	 * @param string $mml mathml input
	 * @return string mml with additional mml-elements for derivatives
	 */
	public function addDerivativesContext( $state, string $mml ): string {
		if ( array_key_exists( "deriv", $state ) && $state["deriv"] > 0 ) {
			$msup = new MMLmsup();
			$moDeriv = new MMLmo();

			if ( $state["deriv"] == 1 ) {
				$derInfo = "&#x2032;";
			} elseif ( $state["deriv"] == 2 ) {
				$derInfo = "&#x2033;";
			} elseif ( $state["deriv"] == 3 ) {
				$derInfo = "&#x2034;";
			} elseif ( $state["deriv"] == 4 ) {
				$derInfo = "&#x2057;";
			} else {
				$derInfo = str_repeat( "&#x2032;", $state["deriv"] );
			}

			$mml = $msup->encapsulateRaw( $mml . $moDeriv->encapsulateRaw( $derInfo ) );
		}
		return $mml;
	}

	public function inCurlies() {
		if ( isset( $this->args[0] ) && count( $this->args ) == 1 ) {
			return $this->args[0]->inCurlies();
		} else {
			return '{' . $this->render() . '}';
		}
	}

	public function extractSubscripts() {
		$y = [];

		foreach ( $this->args as $x ) {
			$y = array_merge( $y, $x->extractSubscripts() );
		}
		if ( isset( $this->args[0] ) && ( count( $this->args ) == count( $y ) ) ) {
			return implode( '', $y );
		}
		return [];
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = $this->args;
		}
		$list = parent::extractIdentifiers( $args );
		$outpos = 0;
		$offset = 0;
		$int = 0;

		for ( $inpos = 0; $inpos < count( $list ); $inpos++ ) {
			$outpos = $inpos - $offset;
			switch ( $list[$inpos] ) {
				case '\'':
					$list[$outpos - 1] .= '\'';
					$offset++;
					break;
				case '\\int':
					$int++;
					$offset++;
					break;
				case '\\mathrm{d}':
				case 'd':
					if ( $int ) {
						$int--;
						$offset++;
						break;
					}
				// no break
				default:
					if ( isset( $list[0] ) ) {
						$list[$outpos] = $list[$inpos];
					}
			}
		}
		return array_slice( $list, 0, count( $list ) - $offset );
	}

	public function getModIdent() {
		$y = [];

		foreach ( $this->args as $x ) {
			$y = array_merge( $y, $x->getModIdent() );
		}

		if ( isset( $this->args[0] ) && ( count( $this->args ) == count( $y ) ) ) {
			return implode( "", $y );
		}
		return [];
	}

	public function push( ...$elements ) {
		self::checkInput( $elements );

		array_push( $this->args, ...$elements );
	}

	public function pop() {
		array_splice( $this->args, 0, 1 );
	}

	/**
	 * @return TexNode|string|null first value
	 */
	public function first() {
		if ( isset( $this->args[0] ) ) {
			return $this->args[0];
		} else {
			return null;
		}
	}

	/**
	 * @return TexNode|string|null second value
	 */
	public function second() {
		if ( isset( $this->args[1] ) ) {
			return $this->args[1];
		} else {
			return null;
		}
	}

	public function unshift( ...$elements ) {
		array_unshift( $this->args, ...$elements );
	}

	/**
	 * @throws InvalidArgumentException if args not of correct type
	 * @param TexNode[] $args input args
	 * @return void
	 */
	private static function checkInput( $args ): void {
		foreach ( $args as $arg ) {
			if ( !( $arg instanceof TexNode ) ) {
				throw new InvalidArgumentException( 'Wrong input type specified in input elements.' );
			}
		}
	}

}
