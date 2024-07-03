<?php
/**
 * Copyright (c) 2023 Johannes Stegmüller
 *
 * This file is a port of mhchemParser originally authored by Martin Hensel in javascript/typescript.
 * The original license for this software can be found in the accompanying LICENSE.mhchemParser-ts.txt file.
 */

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use MediaWiki\Extension\Math\WikiTexVC\Mhchem\MhchemRegExp as Reg;
use RuntimeException;

/**
 * Contains all matching regex patterns and match functions for mhchemParser in PHP.
 *
 * corresponds mostly to the 'patterns' array in line ~207 in mhchemParser.js by Martin Hensel
 *
 * @author Johannes Stegmüller
 * @license GPL-2.0-or-later
 */
class MhchemPatterns {

	/** @var array */
	private array $patterns;

	/**
	 * Matching patterns
	 * either regexes or function that return null or {match_:"a", remainder:"bc"}
	 * @return array
	 */
	public function getPatterns(): array {
		return $this->patterns;
	}

	public function findObserveGroups( $input, $begExcl, $begIncl, $endIncl,
								$endExcl = null, $beg2Excl = null, $beg2Incl = null,
								$end2Incl = null, $end2Excl = null, $combine = null ): ?array {
		$match = $this->matchObsGrInner( $input, $begExcl );
		if ( $match === null ) {
			return null;
		}
		$input = substr( $input, strlen( $match ) );
		$match = $this->matchObsGrInner( $input, $begIncl );
		if ( $match === null ) {
			return null;
		}

		if ( $endIncl === "0" ) {
			throw new RuntimeException( "error in condition, check next loc " );
		}
		$e = $this->findObserveGroupsInner( $input, strlen( $match ),
			MhchemUtil::issetJS( $endIncl ) ? $endIncl : $endExcl );
		if ( $e === null ) {
			return null;
		}
		$match1 = substr( $input, 0, ( $endIncl ? $e["endMatchEnd"] : $e["endMatchBegin"] ) );

		if ( !( MhchemUtil::issetJS( $beg2Excl ) || MhchemUtil::issetJS( $beg2Incl ) ) ) {
			return [
				"match_" => $match1,
				"remainder" => substr( $input, $e["endMatchEnd"] )
			];
		} else {
			$group2 = $this->findObserveGroups( substr( $input, $e["endMatchEnd"] ),
				$beg2Excl, $beg2Incl, $end2Incl, $end2Excl );
			if ( $group2 === null ) {
				return null;
			}
			$matchRet = [ $match1, $group2["match_"] ];
			return [
				"match_" => ( $combine ? implode( "", $matchRet ) : $matchRet ),
				"remainder" => $group2["remainder"]
			];
		}
	}

	private function matchObsGrInner( string $input, $pattern ) {
		/**
		 * In javascript this is checking if the incoming pattern is a string,
		 * if not the assumption is that it is of regex type. Since PHP has
		 * strings here.
		 */
		if ( !$pattern instanceof Reg ) {
			// Added this if to catch empty needle for strpos input  in PHP
			if ( !MhchemUtil::issetJS( $pattern ) ) {
				return $pattern;
			}
			if ( strpos( $input, $pattern ) !== 0 ) {
				return null;
			}
			return $pattern;
		} else {
			$matches = [];
			$match = preg_match( $pattern->getRegExp(), $input, $matches );
			if ( !$match ) {
				return null;
			}
			return $matches[0];
		}
	}

	private function findObserveGroupsInner( string $input, $i, $endChars ): ?array {
		$braces = 0;
		while ( $i < strlen( $input ) ) {
			$a = $input[$i];
			$match = $this->matchObsGrInner( substr( $input, $i ), $endChars );
			if ( $match !== null && $braces === 0 ) {
				return [ "endMatchBegin" => $i, "endMatchEnd" => $i + strlen( $match ) ];
			} elseif ( $a === "{" ) {
				$braces++;
			} elseif ( $a === "}" ) {
				if ( $braces === 0 ) {
					// Unexpected character
					throw new RuntimeException(
						"ExtraCloseMissingOpen: Extra close brace or missing open brace" );
				} else {
					$braces--;
				}
			}
			$i++;
		}
		return null;
	}

	public function __construct() {
		$this->patterns = [
			'empty' => new Reg( "/^$/" ),
			'else' => new Reg( "/^./" ),
			'else2' => new Reg( "/^./" ),
			'space' => new Reg( "/^\s/" ),
			'space A' => new Reg( "/^\s(?=[A-Z\\\\$])/" ),
			'space$' => new Reg( "/^\s$/" ),
			'a-z' => new Reg( "/^[a-z]/" ),
			'x' => new Reg( "/^x/" ),
			'x$' => new Reg( "/^x$/" ),
			'i$' => new Reg( "/^i$/" ),
			'letters' => new Reg(
				"/^(?:[a-zA-Z\x{03B1}-\x{03C9}\x{0391}-\x{03A9}?@]|(?:\\\\(?:alpha|beta|gamma|delta|epsilon"
				. "|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi|rho|sigma|tau|upsilon|phi|chi|psi|omega|Gamma"
				. "|Delta|Theta|Lambda|Xi|Pi|Sigma|Upsilon|Phi|Psi|Omega)(?:\s+|\{\}|(?![a-zA-Z]))))+/u" ),
			'\\greek' => new Reg(
				"/^\\\\(?:alpha|beta|gamma|delta|epsilon|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi"
				. "|rho|sigma|tau|upsilon|phi|chi|psi|omega|Gamma|Delta|Theta|Lambda|Xi|Pi|Sigma|Upsilon|Phi|Psi|Omega)"
				. "(?:\s+|\{\}|(?![a-zA-Z]))/" ),
			'one lowercase latin letter $' => new Reg( "/^(?:([a-z])(?:$|[^a-zA-Z]))$/" ),
			'$one lowercase latin letter$ $' => new Reg( "/^\\\$(?:([a-z])(?:$|[^a-zA-Z]))\\\$$/" ),
			'one lowercase greek letter $' => new Reg(
				"/^(?:\\\$?[\x{003B1}-\x{0003C9}]\\\$?|\\\$?\\\\(?:alpha|beta|gamma|" .
				"delta|epsilon|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi|rho|sigma|tau|upsilon|" .
				"phi|chi|psi|omega)\s*\\\$?)(?:\s+|\{\}|(?![a-zA-Z]))$/u" ),
			'digits' => new Reg( "/^[0-9]+/" ),
			'-9.,9' => new Reg( "/^[+\-]?(?:[0-9]+(?:[,.][0-9]+)?|[0-9]*(?:\.[0-9]+))/" ),
			'-9.,9 no missing 0' => new Reg( "/^[+\-]?[0-9]+(?:[.,][0-9]+)?/" ),
			'(-)(9.,9)(e)(99)' => static function ( $input ) {
				$matches = [];
				$match = preg_match( "/^(\+\-|\+\/\-|\+|\-|\\\\pm\s?)?([0-9]+(?:[,.][0-9]+)?|" .
					"[0-9]*(?:\.[0-9]+))?(\((?:[0-9]+(?:[,.][0-9]+)?|[0-9]*(?:\.[0-9]+))\))?(?:(?:([eE])" .
					"|\s*(\*|x|\\\\times|\x{00D7})\s*10\^)([+\-]?[0-9]+|\{[+\-]?[0-9]+\}))?/u", $input, $matches );
				if ( $match && $matches[0] ) {
					// could also match ""
					return [ "match_" => array_slice( $matches, 1 ),
						"remainder" => substr( $input, strlen( $matches[0] ) ) ];
				}
				return null;
			},
			'(-)(9)^(-9)' => new Reg( "/^(\+\-|\+\/\-|\+|\-|\\\\pm\s?)?([0-9]+(?:[,.][0-9]+)?|"
				. "[0-9]*(?:\.[0-9]+)?)\^([+\-]?[0-9]+|\{[+\-]?[0-9]+\})/" ),
			'state of aggregation $' => function ( $input ) {
				// ... or crystal system
				$a = $this->findObserveGroups( $input, "",
					new Reg( "/^\([a-z]{1,3}(?=[\),])/" ), ")", "" );
				if ( $a && preg_match( "/^($|[\s,;\)\]\}])/", $a["remainder"] ) ) {
					return $a;
				}
				$matches = [];
				$match = preg_match( "/^(?:\((?:\\\\ca\s?)?\\\$[amothc]\\\$\))/", $input, $matches );
				if ( $match ) {
					return [ "match_" => $matches[0], "remainder" => substr( $input, strlen( $matches[0] ) ) ];
				}
				return null;
			},
			'_{(state of aggregation)}$' => new Reg( "/^_\{(\([a-z]{1,3}\))\}/" ),
			'{[(' => new Reg( "/^(?:\\\{|\[|\()/" ),
			')]}' => new Reg( "/^(?:\)|\]|\\\})/" ),
			', ' => new Reg( "/^[,;]\s*/" ),
			',' => new Reg( "/^[,;]/" ),
			'.' => new Reg( "/^[.]/" ),
			'. __* ' => new Reg( "/^([.\x{22C5}\x{00B7}\x{2022}]|[*])\s*/u" ),
			'...' => new Reg( "/^\.\.\.(?=$|[^.])/" ),
			'^{(...)}' => function ( $input ) {
					return $this->findObserveGroups( $input, "^{", "", "", "}" );
			},
			'^($...$)' => function ( $input ) {
					return $this->findObserveGroups( $input, "^", "$", "$", "" );
			},
			'^a' => new Reg( "/^\^([0-9]+|[^\\\_])/u" ),
			'^\\x{}{}' => function ( $input ) {
					return $this->findObserveGroups( $input, "^",
						new Reg( "/^\\\\[a-zA-Z]+\{/" ), "}", "", "",
						"{", "}", "", true );
			},
			'^\\x{}' => function ( $input ) {
					return $this->findObserveGroups( $input, "^",
						new Reg( "/^\\\\[a-zA-Z]+\{/" ), "}", "" );
			},
			'^\\x' => new Reg( "/^\^(\\\\[a-zA-Z]+)\s*/" ),
			'^(-1)' => new Reg( "/^\^(-?\d+)/" ),
			'\'' => new Reg( "/^'/" ),
			'_{(...)}' => function ( $input ) {
				return $this->findObserveGroups( $input, "_{", "", "", "}" );
			},
			'_($...$)' => function ( $input ) {
				return $this->findObserveGroups( $input, "_", "$", "$", "" );
			},
			'_9' => new Reg( "/^_([+\-]?[0-9]+|[^\\\\])/" ),
			'_\\x{}{}' => function ( $input ) {
				return $this->findObserveGroups( $input, "_", new Reg( "/^\\\\[a-zA-Z]+\{/" ), "}",
					"", "", "{", "}", "", true );
			},
			'_\\x{}' => function ( $input ) {
				return $this->findObserveGroups( $input, "_",
					new Reg( "/^\\\\[a-zA-Z]+\{/" ), "}", "" );
			},
			'_\\x' => new Reg( "/^_(\\\\[a-zA-Z]+)\s*/" ),
			'^_' => new Reg( "/^(?:\^(?=_)|\_(?=\^)|[\^_]$)/" ),
			'{}^' => new Reg( "/^\{\}(?=\^)/" ),
			'{}' => new Reg( "/^\{\}/" ),
			'{...}' => function ( $input ) {
				return $this->findObserveGroups( $input, "", "{", "}", "" );
			},
			'{(...)}' => function ( $input ) {
				return $this->findObserveGroups( $input, "{", "", "", "}" );
			},
			'$...$' => function ( $input ) {
				return $this->findObserveGroups( $input, "", "\$", "\$", "" );
			},
			'${(...)}$__$(...)$' => function ( $input ) {
				return $this->findObserveGroups( $input, "\${", "", "", "}\$" )
					?? $this->findObserveGroups( $input, "\$", "", "", "\$" );
			},
			'=<>' => new Reg( "/^[=<>]/" ),
			'#' => new Reg( "/^[#\x{2261}]/u" ),
			'+' => new Reg( "/^\+/" ),
			// -space -, -; -] -/ -$ -state-of-aggregation orig:  "/^-(?=[\s_},;\]/]|$|\([a-z]+\))/"
			'-$' => new Reg( "/^-(?=[\s_},;\]\/]|$|\([a-z]+\))/u" ),
			'-9' => new Reg( "/^-(?=[0-9])/" ),
			'- orbital overlap' => new Reg( "/^-(?=(?:[spd]|sp)(?:$|[\s,;\)\]\}]))/" ),
			'-' => new Reg( "/^-/" ),
			'pm-operator' => new Reg( "/^(?:\\\\pm|\\\$\\\\pm\\\$|\+-|\+\/-)/" ),
			'operator' => new Reg( "/^(?:\+|(?:[\-=<>]|<<|>>|\\\\approx|\\\$\\\\approx\\\$)(?=\s|$|-?[0-9]))/" ),
			'arrowUpDown' => new Reg( "/^(?:v|\(v\)|\^|\(\^\))(?=$|[\s,;\)\]\}])/" ),
			'\\bond{(...)}' => function ( $input ) {
				return $this->findObserveGroups( $input, "\\bond{", "", "", "}" );
			},
			'->' => new Reg( '/^(?:<->|<-->|->|<-|<=>>|<<=>|<=>|[\x{2192}\x{27F6}\x{21CC}])/u' ),
			'CMT' => new Reg( "/^[CMT](?=\[)/" ),
			'[(...)]' => function ( $input ) { return $this->findObserveGroups( $input, "[", "",
				"", "]" );
			},
			'1st-level escape' => new Reg( "/^(&|\\\\\\\\|\\\\hline)\s*/" ),
			// \\x - but output no space before
			'\\,' => new Reg( "/^(?:\\\\[,\ ;:])/" ),
			'\\x{}{}' => function ( $input ) {
				return $this->findObserveGroups( $input, "", new Reg( "/^\\\\[a-zA-Z]+\{/" ), "}",
					"", "", "{", "}", "", true );
			},
			'\\x{}' => function ( $input ) {
				return $this->findObserveGroups( $input, "", new Reg( "/^\\\\[a-zA-Z]+\{/" ), "}",
					"" );
			},
			'\\ca' => new Reg( "/^\\\\ca(?:\s+|(?![a-zA-Z]))/" ),
			'\\x' => new Reg( "/^(?:\\\\[a-zA-Z]+\s*|\\\\[_&{}%])/" ),
			// only those with numbers in front, because the others will be formatted correctly anyway
			'orbital' => new Reg( "/^(?:[0-9]{1,2}[spdfgh]|[0-9]{0,2}sp)(?=$|[^a-zA-Z])/" ),
			'others' => new Reg( "/^[\/~|]/" ),
			'\\frac{(...)}' => function ( $input ) {
					return $this->findObserveGroups( $input, "\\frac{", "",
						"", "}", "{", "", "", "}" );
			},
			'\\overset{(...)}' => function ( $input ) {
					return $this->findObserveGroups( $input, "\\overset{", "",
						"", "}", "{", "", "", "}" );
			},
			'\\underset{(...)}' => function ( $input ) {
					return $this->findObserveGroups( $input, "\\underset{", "",
						"", "}", "{", "", "", "}" );
			},
			'\\underbrace{(...)}' => function ( $input ) {
					return $this->findObserveGroups( $input, "\\underbrace{", "",
						"", "}_", "{", "", "", "}" );
			},
			'\\color{(...)}' => function ( $input ) {
					return $this->findObserveGroups( $input, "\\color{", "", "", "}" );
			},
			'\\color{(...)}{(...)}' => function ( $input ) {
				// ?? instead of ||
				return $this->findObserveGroups( $input, "\\color{", "",
					"", "}", "{", "", "", "}" ) ??
				$this->findObserveGroups( $input, "\\color", "\\", "",
					new Reg( "/^(?=\{)/" ), "{", "", "", "}" );
			},
			'\\ce{(...)}' => function ( $input ) {
				return $this->findObserveGroups( $input, "\\ce{", "", "", "}" );
			},
			'\\pu{(...)}' => function ( $input ) { return $this->findObserveGroups( $input,
				"\\pu{", "", "", "}" );
			},
			'oxidation$' => new Reg( "/^(?:[+-][IVX]+|(?:\\\\pm|\\\$\\\\pm\\\$|\+-|\+\/-)\s*0)$/" ),
			'd-oxidation$' => new Reg( "/^(?:[+-]?[IVX]+|(?:\\\\pm|\\\$\\\\pm\\\$|\+-|\+\/-)\s*0)$/" ),
			'1/2$' => new Reg( "/^[+\-]?(?:[0-9]+|\\\$[a-z]\\\$|[a-z])\/[0-9]+(?:\\\$[a-z]\\\$|[a-z])?$/" ),
			'amount' => function ( $input ) {
				$matches = [];
				// e.g. 2, 0.5, 1/2, -2, n/2, +;  $a$ could be added later in parsing
				$match = preg_match( "/^(?:(?:(?:\([+\-]?[0-9]+\/[0-9]+\)|[+\-]?(?:[0-9]+|\\\$[a-z]\\\$" .
					"|[a-z])\/[0-9]+|[+\-]?[0-9]+[.,][0-9]+|[+\-]?\.[0-9]+|[+\-]?[0-9]+)(?:[a-z](?=\s*[A-Z]))?)" .
					"|[+\-]?[a-z](?=\s*[A-Z])|\+(?!\s))/", $input, $matches );

				if ( $match ) {
					return [ "match_" => $matches[0], "remainder" => substr( $input, strlen( $matches[0] ) ) ];
				}
				$a = $this->findObserveGroups( $input, "", "$", "$", "" );
				// e.g. $2n-1$, $-$
				if ( MhchemUtil::issetJS( $a ) ) {
					$matchesI = [];

					$match = preg_match( "/^\\\$(?:\(?[+\-]?(?:[0-9]*[a-z]?[+\-])" .
						"?[0-9]*[a-z](?:[+\-][0-9]*[a-z]?)?\)?|\+|-)\\\$$/", $a["match_"] ?? "",
						$matchesI );
					if ( $match ) {
						return [ "match_" => $matchesI[0], "remainder" => substr( $input, strlen( $matchesI[0] ) ) ];
					}
				}
				return null;
			},
			'amount2' => function ( $input ) {
				/* @phan-suppress-next-line PhanInfiniteRecursion, PhanUndeclaredInvokeInCallable */
				return $this->patterns['amount']( $input );
			},
			'(KV letters),' => new Reg( "/^(?:[A-Z][a-z]{0,2}|i)(?=,)/" ),
			'formula$' => static function ( $input ) {
				if ( preg_match( "/^\([a-z]+\)$/", $input ) ) {
					// state of aggregation = no formula
					return null;
				}
				$matches = [];
				$match = preg_match( "/^(?:[a-z]|(?:[0-9\ \+\-\,\.\(\)]+[a-z])+[0-9\ \+\-\,\.\(\)]*|"
					. "(?:[a-z][0-9\ \+\-\,\.\(\)]+)+[a-z]?)$/", $input, $matches );
				if ( $match ) {
					return [ "match_" => $matches[0], "remainder" => substr( $input, strlen( $matches[0] ) ) ];
				}
				return null;
			},
			'uprightEntities' => new Reg( "/^(?:pH|pOH|pC|pK|iPr|iBu)(?=$|[^a-zA-Z])/" ),
			'/' => new Reg( "/^\s*(\/)\s*/" ),
			'//' => new Reg( "/^\s*(\/\/)\s*/" ),
			'*' => new Reg( "/^\s*[*.]\s*/" )
		];
	}

	/**
	 * Matching function
	 * e.g. match("a", input) will look for the regexp called "a" and see if it matches
	 * returns null or {match_:"a", remainder:"bc"}
	 * @param string $m key for fetching a pattern
	 * @param string $input string to check
	 * @return array|mixed|null information about the match
	 */
	public function match( string $m, string $input ) {
		$pattern = $this->patterns[$m] ?? null;
		if ( !$pattern ) {
			// Trying to use non-existing pattern
			throw new RuntimeException( "MhchemBugP: mhchem bug P. Please report. (" . $m . ")" );
		} elseif ( $pattern instanceof Reg ) {
			$matches = [];
			$match = preg_match( $pattern->getRegExp(), $input, $matches );
			if ( $match ) {
				if ( count( $matches ) > 2 ) {
					return [
						"match_" => array_slice( $matches, 1 ),
						"remainder" => substr( $input, strlen( $matches[0] ) )
					];

				} else {
					return [
						"match_" => MhchemUtil::issetJS( $matches[1] ?? null ) ? $matches[1] : $matches[0],
						"remainder" => substr( $input, strlen( $matches[0] ) )
					];
				}
			}
			return null;
		} elseif ( is_callable( $pattern ) ) {
			// $pattern cannot be an instance of MhchemRegExp here, which causes this warning.
			/* @phan-suppress-next-line PhanUndeclaredInvokeInCallable */
			return $this->patterns[$m]( $input );
		} else {
			return null;
		}
	}
}
