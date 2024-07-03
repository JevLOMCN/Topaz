<?php
/**
 * Copyright (c) 2023 Johannes Stegmüller
 *
 * This file is a port of mhchemParser originally authored by Martin Hensel in javascript/typescript.
 * The original license for this software can be found in the accompanying LICENSE.mhchemParser-ts.txt file.
 */

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use MediaWiki\Extension\Math\WikiTexVC\MHChem\MhchemUtil as MU;
use RuntimeException;

/**
 * Takes MhchemParser output and convert it to TeX
 *
 * Functionality is the same as mhchemTexify class at ~line 1505 in mhchemParser.js
 * in mhchemParser by Martin Hensel.
 *
 * @author Johannes Stegmüller
 * @license GPL-2.0-or-later
 */
class MhchemTexify {

	/** @var bool optimize the output TeX for WikiTexVC */
	private bool $optimizeForTexVC;

	/**
	 * Takes MhchemParser output and convert it to TeX
	 * @param bool $optimizeForTexVC optimizes the output for WikiTexVC grammar by
	 * wrapping dimensions for some TeX commands in curly brackets.
	 */
	public function __construct( bool $optimizeForTexVC = false ) {
		$this->optimizeForTexVC = $optimizeForTexVC;
	}

	public function go( $input, $addOuterBraces ): string {
		if ( !MhchemUtil::issetJS( $input ) ) {
			return "";
		}
		$res = "";
		$cee = false;
		for ( $i = 0; $i < count( $input ); $i++ ) {
			$inputI = $input[$i];

			if ( is_string( $inputI ) ) {
				$res .= $inputI;
			} else {
				$res .= self::go2( $inputI );
				if ( $inputI["type_"] === '1st-level escape' ) {
					$cee = true;
				}
			}
		}
		if ( $addOuterBraces && !$cee && $res ) {
			$res = "{" . $res . "}";
		}
		return $res;
	}

	private function goInner( $input ): string {
		return self::go( $input, false );
	}

	private function strReplaceFirst( $search, $replace, $subject ): string {
		return implode( $replace, explode( $search, $subject, 2 ) );
	}

	private function go2( $buf ): string {
		switch ( $buf["type_"] ) {
			case 'chemfive':
				$res = "";
				$b5 = [
					"a" => self::goInner( $buf["a"] ),
					"b" => self::goInner( $buf["b"] ),
					"p" => self::goInner( $buf["p"] ),
					"o" => self::goInner( $buf["o"] ),
					"q" => self::goInner( $buf["q"] ),
					"d" => self::goInner( $buf["d"] )
				];
				if ( MU::issetJS( $b5["a"] ) ) {
					if ( preg_match( "/^[+\-]/", $b5["a"] ) ) {
						$b5["a"] = "{" . $b5["a"] . "}";
					}
					$res .= $b5["a"] . "\\,";
				}
				if ( MU::issetJS( $b5["b"] ) || MU::issetJS( $b5["p"] ) ) {
					$res .= "{\\vphantom{A}}";
					$res .= "^{\\hphantom{" . ( $b5["b"] ) . "}}_{\\hphantom{" . ( $b5["p"] ) . "}}";
					$res .= !$this->optimizeForTexVC ? "\\mkern-1.5mu" : "\\mkern{-1.5mu}";
					$res .= "{\\vphantom{A}}";
					$res .= "^{\\smash[t]{\\vphantom{2}}\\llap{" . ( $b5["b"] ) . "}}";
					$res .= "_{\\vphantom{2}\\llap{\\smash[t]{" . ( $b5["p"] ) . "}}}";
				}

				if ( MU::issetJS( $b5["o"] ) ) {
					if ( preg_match( "/^[+\-]/", $b5["o"] ) ) {
						$b5["o"] = "{" . $b5["o"] . "}";
					}
					$res .= $b5["o"];
				}
				if ( isset( $buf["dType"] ) && $buf["dType"] === 'kv' ) {
					if ( MU::issetJS( $b5["d"] ) || MU::issetJS( $b5["q"] ) ) {
						$res .= "{\\vphantom{A}}";
					}
					if ( MU::issetJS( $b5["d"] ) ) {
						$res .= "^{" . $b5["d"] . "}";
					}
					if ( MU::issetJS( $b5["q"] ) ) {
						$res .= "_{\\smash[t]{" . $b5["q"] . "}}";
					}
				} elseif ( MU::issetJS( $buf["dType"] ?? null ) && $buf["dType"] === 'oxidation' ) {
					if ( MU::issetJS( $b5["d"] ) ) {
						$res .= "{\\vphantom{A}}";
						$res .= "^{" . $b5["d"] . "}";
					}
					if ( MU::issetJS( $b5["q"] ) ) {
						$res .= "{\\vphantom{A}}";
						$res .= "_{\\smash[t]{" . $b5["q"] . "}}";
					}
				} else {
					if ( MU::issetJS( $b5["q"] ) ) {
						$res .= "{\\vphantom{A}}";
						$res .= "_{\\smash[t]{" . $b5["q"] . "}}";
					}
					if ( MU::issetJS( $b5["d"] ) ) {
						$res .= "{\\vphantom{A}}";
						$res .= "^{" . $b5["d"] . "}";
					}
				}
				break;
			case 'roman numeral':
			case 'rm':
				$res = "\\mathrm{" . $buf["p1"] . "}";
				break;
			case 'text':
				if ( preg_match( "/[\^_]/", $buf["p1"] ) ) {
					$buf["p1"] = self::strReplaceFirst( "-", "\\text{-}",
						self::strReplaceFirst( " ", "~", $buf["p1"] ) );
					$res = "\\mathrm{" . $buf["p1"] . "}";
				} else {
					$res = "\\text{" . $buf["p1"] . "}";
				}
				break;
			case 'state of aggregation':
				$res = ( !$this->optimizeForTexVC ? "\\mskip2mu " : "\\mskip{2mu} " ) . self::goInner( $buf["p1"] );
				break;
			case 'state of aggregation subscript':
				$res = ( !$this->optimizeForTexVC ? "\\mskip1mu " : "\\mskip{1mu} " ) . self::goInner( $buf["p1"] );
				break;
			case 'bond':
				$res = self::getBond( $buf["kind_"] );
				if ( !$res ) {
					throw new RuntimeException( "MhchemErrorBond: mhchem Error. Unknown bond type ("
						. $buf["kind_"] . ")" );
				}
				break;
			case 'frac':
				$c = "\\frac{" . $buf["p1"] . "}{" . $buf["p2"] . "}";
				$res = "\\mathchoice{\\textstyle" . $c . "}{" . $c . "}{" . $c . "}{" . $c . "}";
				break;
			case 'pu-frac':
				$d = "\\frac{" . self::goInner( $buf["p1"] ) . "}{" . self::goInner( $buf["p2"] ) . "}";
				$res = "\\mathchoice{\\textstyle" . $d . "}{" . $d . "}{" . $d . "}{" . $d . "}";
				break;
			case '1st-level escape':
			case 'tex-math':
				$res = $buf["p1"] . " ";
				break;
			case 'frac-ce':
				$res = "\\frac{" . self::goInner( $buf["p1"] ) . "}{" . self::goInner( $buf["p2"] ) . "}";
				break;
			case 'overset':
				$res = "\\overset{" . self::goInner( $buf["p1"] ) . "}{" . self::goInner( $buf["p2"] ) . "}";
				break;
			case 'underset':
				$res = "\\underset{" . self::goInner( $buf["p1"] ) . "}{" . self::goInner( $buf["p2"] ) . "}";
				break;
			case 'underbrace':
				$res = "\\underbrace{" . self::goInner( $buf["p1"] ) . "}_{" . self::goInner( $buf["p2"] ) . "}";
				break;
			case 'color':
				$res = "{\\color{" . $buf["color1"] . "}{" . self::goInner( $buf["color2"] ) . "}}";
				break;
			case 'color0':
				$res = "\\color{" . $buf["color"] . "}";
				break;
			case 'arrow':
				$b6 = [
					"rd" => self::goInner( $buf["rd"] ),
					"rq" => self::goInner( $buf["rq"] )
				];
				$arrow = self::getArrow( $buf["r"] );
				if ( MU::issetJS( $b6["rd"] ) || MU::issetJS( $b6["rq"] ) ) {
					if ( $buf["r"] === "<=>" || $buf["r"] === "<=>>" || $buf["r"] === "<<=>" || $buf["r"] === "<-->" ) {
						$arrow = "\\long" . $arrow;
						if ( MU::issetJS( $b6["rd"] ) ) {
							$arrow = "\\overset{" . $b6["rd"] . "}{" . $arrow . "}";
						}
						if ( MU::issetJS( $b6["rq"] ) ) {
							if ( $buf["r"] === "<-->" ) {
								$arrow = !$this->optimizeForTexVC ?
									"\\underset{\\lower2mu{" . $b6["rq"] . "}}{" . $arrow . "}"
									: "\\underset{\\lower{2mu}{" . $b6["rq"] . "}}{" . $arrow . "}";
							} else {
								$arrow = !$this->optimizeForTexVC ?
									"\\underset{\\lower6mu{" . $b6["rq"] . "}}{" . $arrow . "}"
									: "\\underset{\\lower{6mu}{" . $b6["rq"] . "}}{" . $arrow . "}";
							}
						}
						$arrow = " {}\\mathrel{" . $arrow . "}{} ";
					} else {
						if ( MU::issetJS( $b6["rq"] ) ) {
							$arrow .= "[{" . $b6["rq"] . "}]";
						}
						$arrow .= "{" . $b6["rd"] . "}";
						$arrow = " {}\\mathrel{\\x" . $arrow . "}{} ";
					}
				} else {
					$arrow = " {}\\mathrel{\\long" . $arrow . "}{} ";
				}
				$res = $arrow;
				break;
			case 'operator':
				$res = self::getOperator( $buf["kind_"] );
				break;
			default:
				$res = null;
		}
		if ( isset( $res ) ) {
			return $res;
		}

		switch ( $buf["type_"] ) {
			case 'space':
				$res = " ";
				break;
			case 'tinySkip':
				$res = !$this->optimizeForTexVC ? '\\mkern2mu' : '\\mkern{2mu}';
				break;
			case 'pu-space-1':
			case 'entitySkip':
				$res = "~";
				break;
			case 'pu-space-2':
				$res = !$this->optimizeForTexVC ? "\\mkern3mu " : "\\mkern{3mu} ";
				break;
			case '1000 separator':
				$res = !$this->optimizeForTexVC ? "\\mkern2mu " : "\\mkern{2mu} ";
				break;
			case 'commaDecimal':
				$res = "{,}";
				break;
			case 'comma enumeration L':
				$res = "{" . $buf["p1"] . "}" . ( !$this->optimizeForTexVC ? "\\mkern6mu " : "\\mkern{6mu} " );
				break;
			case 'comma enumeration M':
				$res = "{" . $buf["p1"] . "}" . ( !$this->optimizeForTexVC ? "\\mkern3mu " : "\\mkern{3mu} " );
				break;
			case 'comma enumeration S':
				$res = "{" . $buf["p1"] . "}" . ( !$this->optimizeForTexVC ? "\\mkern1mu " : "\\mkern{1mu} " );
				break;
			case 'hyphen':
				$res = "\\text{-}";
				break;
			case 'addition compound':
				$res = "\\,{\\cdot}\\,";
				break;
			case 'electron dot':
				$res = !$this->optimizeForTexVC ?
					"\\mkern1mu \\bullet\\mkern1mu " : "\\mkern{1mu} \\bullet\\mkern{1mu} ";
				break;
			case 'KV x':
				$res = "{\\times}";
				break;
			case 'prime':
				$res = "\\prime ";
				break;
			case 'cdot':
				$res = "\\cdot ";
				break;
			case 'tight cdot':
				$res = !$this->optimizeForTexVC ? "\\mkern1mu{\\cdot}\\mkern1mu " : "\\mkern{1mu}{\\cdot}\\mkern{1mu} ";
				break;
			case 'times':
				$res = "\\times ";
				break;
			case 'circa':
				$res = "{\\sim}";
				break;
			case '^':
				$res = "uparrow";
				break;
			case 'v':
				$res = "downarrow";
				break;
			case 'ellipsis':
				$res = "\\ldots ";
				break;
			case '/':
				$res = "/";
				break;
			case ' / ':
				$res = "\\,/\\,";
				break;
			default:
				throw new RuntimeException( "MhchemBugT: mhchem bug T. Please report." );
		}
		return $res;
	}

	private function getArrow( $a ): string {
		switch ( $a ) {
			case "\u2192":
			case "\u27F6":
			case "->":
				return "rightarrow";
			case "<-":
				return "leftarrow";
			case "<->":
				return "leftrightarrow";
			case "<-->":
				return "leftrightarrows";
			case "\u21CC":
			case "<=>":
				return "rightleftharpoons";
			case "<=>>":
				return "Rightleftharpoons";
			case "<<=>":
				return "Leftrightharpoons";
			default:
				throw new RuntimeException( "MhchemBugT: mhchem bug T. Please report." );
		}
	}

	private function getBond( $a ): string {
		switch ( $a ) {
			case "1":
			case "-":
				return "{-}";
			case "2":
			case "=":
				return "{=}";
			case "3":
			case "#":
				return "{\\equiv}";
			case "~":
				return "{\\tripledash}";
			case "~-":
				return !$this->optimizeForTexVC ? "{\\rlap{\\lower.1em{-}}\\raise.1em{\\tripledash}}"
					: "{\\rlap{\\lower{.1em}{-}}\\raise{.1em}{\\tripledash}}";
			case "~--":
			case "~=":
				return !$this->optimizeForTexVC ? "{\\rlap{\\lower.2em{-}}\\rlap{\\raise.2em{\\tripledash}}-}"
					: "{\\rlap{\\lower{.2em}{-}}\\rlap{\\raise{.2em}{\\tripledash}}-}";
			case "-~-":
				return !$this->optimizeForTexVC ? "{\\rlap{\\lower.2em{-}}\\rlap{\\raise.2em{-}}\\tripledash}"
					: "{\\rlap{\\lower{.2em}{-}}\\rlap{\\raise{.2em}{-}}\\tripledash}";
			case "...":
				return "{{\\cdot}{\\cdot}{\\cdot}}";
			case "....":
				return "{{\\cdot}{\\cdot}{\\cdot}{\\cdot}}";
			case "->":
				return "{\\rightarrow}";
			case "<-":
				return "{\\leftarrow}";
			case "<":
				return "{<}";
			case ">":
				return "{>}";
			default:
				throw new RuntimeException( "MhchemBugT: mhchem bug T. Please report." );
		}
	}

	private function getOperator( $a ): string {
		switch ( $a ) {
			case "+":
				return " {}+{} ";
			case "-":
				return " {}-{} ";
			case "=":
				return " {}={} ";
			case "<":
				return " {}<{} ";
			case ">":
				return " {}>{} ";
			case "<<":
				return " {}\\ll{} ";
			case ">>":
				return " {}\\gg{} ";
			case "\\pm":
				return " {}\\pm{} ";
			case "$\\approx$":
			case "\\approx":
				return " {}\\approx{} ";
			case "(v)":
			case "v":
				return " \\downarrow{} ";
			case "(^)":
			case "^":
				return " \\uparrow{} ";
			default:
				throw new RuntimeException( "MhchemBugT: mhchem bug T. Please report." );
		}
	}

}
