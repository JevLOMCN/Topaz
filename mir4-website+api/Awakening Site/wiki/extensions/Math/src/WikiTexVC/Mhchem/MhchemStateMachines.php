<?php
/**
 * Copyright (c) 2023 Johannes Stegmüller
 *
 * This file is a port of mhchemParser originally authored by Martin Hensel in javascript/typescript.
 * The original license for this software can be found in the accompanying LICENSE.mhchemParser-ts.txt file.
 */

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use Closure;
use MediaWiki\Extension\Math\WikiTexVC\MHChem\MhchemUtil as MU;
use RuntimeException;

/**
 * Contains all state machines (~l.506) and genericActions (~l.465) as well as the mhchemCreateTransitions (~l.47)
 * function.
 * These can be found in the mentioned lines in mhchemParser.js by Martin Hensel.
 *
 * Notes:
 * PhanParamTooMany and PhanParamTooFew warnings are suppressed in some cases.
 * These are known false positive warnings for closure call from array with constant keys.
 * https://github.com/phan/phan/issues/4579
 *
 * @author Johannes Stegmüller
 * @license GPL-2.0-or-later
 */
class MhchemStateMachines {

	/** @var array|array[] */
	public array $stateMachines;

	/**
	 * @var array|Closure[]
	 */
	private array $genericActions;

	/** @var MhchemParser */
	private MhchemParser $mhchemParser;

	private static function mhchemCreateTransitions( $o ): array {
		$transitions = [];
		// 1. Collect all states
		foreach ( $o as $pattern => $d1 ) {
			foreach ( $d1 as $state => $d2 ) {
				$stateArray = preg_split( "/\|/", strval( $state ), -1, PREG_SPLIT_NO_EMPTY );
					$o[$pattern][$state]["stateArray"] = $stateArray;
				for ( $i = 0; $i < count( $stateArray ); $i++ ) {
					$transitions[$stateArray[$i]] = [];
				}
			}
		}

		// 2. Fill states
		foreach ( $o as $pattern => $d1 ) {
			foreach ( $d1 as $d2 ) {
				$stateArray = $d2["stateArray"] ?? [];

				for ( $i = 0; $i < count( $stateArray ); $i++ ) {
					// 2a. Normalize actions into array:  'text=' ==> [{type_:'text='}]
					$p = $d2;
					if ( is_string( $p["action_"] ) ) {
						$p["action_"] = [ $p["action_"] ];
					}
					$p["action_"] = array_merge( [], $p["action_"] );

					foreach ( $p["action_"] as $key => $action ) {
						if ( is_string( $action ) ) {
							$p["action_"][$key] = [ "type_" => $p["action_"][$key] ];
						}
					}

					// 2.b Multi-insert
					$patternArray = preg_split( "/\|/", strval( $pattern ), -1, PREG_SPLIT_NO_EMPTY );
					for ( $j = 0; $j < count( $patternArray ); $j++ ) {
						if ( $stateArray[$i] === '*' ) {
							// insert into all
							foreach ( $transitions as $t => $dEmpty ) {
								$transitions[$t][] = [ "pattern" => $patternArray[$j], "task" => $p ];
							}
						} else {
							$transitions[$stateArray[$i]][] = [ "pattern" => $patternArray[$j], "task" => $p ];
						}
					}
				}
			}
		}

		return $transitions;
	}

	/**
	 * @return array
	 */
	public function getGenericActions(): array {
		return $this->genericActions;
	}

	/**
	 * Initialize arrays for genericActions and StateMachines with mhchemCreateTransitions.
	 * @param-taint $mhchemParser none
	 */
	public function __construct( MhchemParser $mhchemParser ) {
		$this->mhchemParser = $mhchemParser;
		$this->genericActions = [
			'a=' => static function ( &$buffer, $m ) {
				$buffer["a"] = ( $buffer["a"] ?? "" ) . $m;
				return null;
			},
			'b=' => static function ( &$buffer, $m ) {
				$buffer["b"] = ( $buffer["b"] ?? "" ) . $m;
				return null;
			},
			'p=' => static function ( &$buffer, $m ) {
				$buffer["p"] = ( $buffer["p"] ?? "" ) . $m;
				return null;
			},
			'o=' => static function ( &$buffer, $m ) {
				$buffer["o"] = ( $buffer["o"] ?? "" ) . $m;
				return null;
			},
			'o=+p1' => static function ( &$buffer, $m, $a ) {
				$buffer["o"] = ( $buffer["o"] ?? "" ) . $a;
				return null;
			},
			'q=' => static function ( &$buffer, $m ) {
				$buffer["q"] = ( $buffer["q"] ?? "" ) . $m;
				return null;
			},
			'd=' => static function ( &$buffer, $m ) {
				$buffer["d"] = ( $buffer["d"] ?? "" ) . $m;
				return null;
			},
			'rm=' => static function ( &$buffer, $m ) {
				$buffer["rm"] = ( $buffer["rm"] ?? "" ) . $m;
				return null;
			},
			'text=' => static function ( &$buffer, $m ) {
				$buffer["text_"] = ( $buffer["text_"] ?? "" ) . $m;
				return null;
			},
			'insert' => static function ( &$_buffer, $_m, string $a ) {
				return [ "type_" => $a ];
			},
			'insert+p1' => static function ( &$_buffer, $m, $a ) {
				return [ "type_" => $a, "p1" => $m ];
			},
			'insert+p1+p2' => static function ( &$_buffer, $m, $a ) {
				return [ "type_" => $a, "p1" => $m[0], "p2" => $m[1] ];
			},
			'copy' => static function ( &$_buffer, $m ) {
				return $m;
			},
			'write' => static function ( &$_buffer, $_m, string $a ) {
				return $a;
			},
			'rm' => static function ( &$_buffer, $m ) {
				return [ "type_" => 'rm', "p1" => $m ];
			},
			'text' => function ( &$_buffer, $m ) {
				return $this->mhchemParser->go( $m, 'text' );
			},
			'tex-math' => function ( &$_buffer, $m ) {
				return $this->mhchemParser->go( $m, 'tex-math' );
			},
			'tex-math tight' => function ( &$_buffer, $m ) {
				return $this->mhchemParser->go( $m, 'tex-math tight' );
			},
			'bond' => static function ( &$_buffer, $m, $k ) {
				// ?? ok ?
				return [ "type_" => 'bond', "kind_" => $k ?? $m ];
			},
			'color0-output' => static function ( &$_buffer, $m ) {
				return [ "type_" => 'color0', "color" => $m ];
			},
			'ce' => function ( &$_buffer, $m ) {
				return $this->mhchemParser->go( $m, 'ce' );
			},
			'pu' => function ( &$_buffer, $m ) {
				return $this->mhchemParser->go( $m, 'pu' );
			},
			'1/2' => static function ( &$_buffer, $m ) {
				$ret = [];
				if ( preg_match( "/^[+\-]/", $m ) ) {
					$ret[] = substr( $m, 0, 1 );
					$m = substr( $m, 1 );
				}
				$matches = [];
				$n = preg_match( "/^([0-9]+|\\\$[a-z]\\\$|[a-z])\/([0-9]+)(\\\$[a-z]\\\$|[a-z])?$/",
					$m, $matches );
				if ( !$n || count( $matches ) < 3 ) {
					throw new RuntimeException( "No Result by regex in '1/2' genericAction" );
				}
				$matches[1] = preg_replace( "/\\\$/", "", $matches[1] );

				$ret[] = [ "type_" => 'frac', "p1" => $matches[1], "p2" => $matches[2] ];

				if ( isset( $matches[3] ) ) {
					$matches[3] = preg_replace( "/\\\$/", "", $matches[3] );
					$ret[] = [ "type_" => 'tex-math', "p1" => $matches[3] ];
				}
				return $ret;
			},
			'9,9' => function ( &$_buffer, $m ) {
				return $this->mhchemParser->go( $m, '9,9' );
			}
		];

		$this->stateMachines = [
			"tex" => [
				"transitions" => self::mhchemCreateTransitions( [
					"empty" => [ "0" => [ "action_" => [ "copy" ] ] ],
					"\\ce{(...)}" => [ "0" => [ "action_" => [ [ "type_" => "write", "option" => "{" ],
						"ce", [ "type_" => "write", "option" => "}" ] ] ] ],
					"\\pu{(...)}" => [ "0" => [ "action_" => [ [ "type_" => "write", "option" => "{" ],
						"pu", [ "type_" => "write", "option" => "}" ] ] ] ],
					"else" => [ "0" => [ "action_" => [ "copy" ] ] ]
				] ),
				"actions" => []
			],
			"ce" => [
				"transitions" => self::mhchemCreateTransitions( [
					"empty" => [ "*" => [ "action_" => "output" ] ],
					"else" => [ "0|1|2" => [ "action_" => "beginsWithBond=false",
						"revisit" => true, "toContinue" => true ] ],
					"oxidation$" => [ "0" => [ "action_" => 'oxidation-output' ] ],
					"CMT" => [ 'r' => [ "action_" => "rdt=", "nextState" => "rt" ],
						"rd" => [ "action_" => "rqt=", "nextState" => "rdt" ] ],
					"arrowUpDown" => [ '0|1|2|as' =>
						[ "action_" => [ 'sb=false', 'output', 'operator' ], "nextState" => '1' ] ],
					"uprightEntities" => [ "0|1|2" => [ "action_" => [ 'o=', 'output' ], "nextState" => "1" ] ],
					"orbital" => [ "0|1|2|3" => [ "action_" => "o=", "nextState" => "o" ] ],
					"->" => [ "0|1|2|3" => [ "action_" => "r=", "nextState" => "r" ],
						"a|as" => [ "action_" => [ 'output', 'r=' ], "nextState" => "r" ],
						"*" => [ "action_" => [ 'output', 'r=' ], "nextState" => "r" ] ],
					"+" => [
						"o" => [ "action_" => "d= kv", "nextState" => "d" ],
						"d|D" => [ "action_" => 'd=', "nextState" => 'd' ],
						"q" => [ "action_" => 'd=', "nextState" => 'qd' ],
						"qd|qD" => [ "action_" => 'd=', "nextState" => 'qd' ],
						"dq" => [ "action_" => [ 'output', 'd=' ], "nextState" => 'd' ],
						"3" => [ "action_" => [ 'sb=false', 'output', 'operator' ], "nextState" => '0' ],
					],
					"amount" => [ "0|2" => [ "action_" => "a=", "nextState" => "a" ] ],
					"pm-operator" => [ "0|1|2|a|as" => [ "action_" => [ 'sb=false', 'output',
						[ "type_" => 'operator', "option" => '\\pm' ] ], "nextState" => '0' ] ],
					"operator" => [ "0|1|2|a|as" =>
						[ "action_" => [ 'sb=false', 'output', 'operator' ], "nextState" => '0' ] ],
					"-$" => [
						"o|q" => [ "action_" => [ 'charge or bond', 'output' ], "nextState" => 'qd' ],
						"d" => [ "action_" => 'd=', "nextState" => 'd' ],
						"D" => [ "action_" => [ 'output', [ "type_" => "bond",
							"option" => "-" ] ], "nextState" => '3' ],
						"q" => [ "action_" => 'd=', "nextState" => 'qd' ],
						"qd" => [ "action_" => 'd=', "nextState" => 'qd' ],
						"qD|dq" => [ "action_" => [ 'output',
							[ "type_" => "bond", "option" => "-" ] ], "nextState" => '3' ]
					],
					"-9" => [ "3|o" => [ "action_" =>
						[ 'output', [ "type_" => "insert", "option" => "hyphen" ] ], "nextState" => '3' ] ],
					'- orbital overlap' => [
						'o' => [ "action_" => [ 'output',
							[ "type_" => 'insert', "option" => 'hyphen' ] ], "nextState" => '2' ],
						'd' => [ "action_" => [ 'output',
							[ "type_" => 'insert', "option" => 'hyphen' ] ], "nextState" => '2' ] ],
					'-' => [
						'0|1|2' => [ "action_" => [ [ "type_" => 'output', "option" => 1 ],
							'beginsWithBond=true', [ "type_" => 'bond', "option" => "-" ] ], "nextState" => '3' ],
						'3' => [ "action_" => [ [ "type_" => 'bond', "option" => "-" ] ] ],
						'a' => [ "action_" => [ 'output',
							[ "type_" => 'insert', "option" => 'hyphen' ] ], "nextState" => '2' ],
						'as' => [ "action_" => [ [ "type_" => 'output', "option" => 2 ],
							[ "type_" => 'bond', "option" => "-" ] ], "nextState" => '3' ],
						'b' => [ "action_" => 'b=' ],
						'o' => [ "action_" => [ [ "type_" => '- after o/d', "option" => false ] ], "nextState" => '2' ],
						'q' => [ "action_" => [ [ "type_" => '- after o/d', "option" => false ] ], "nextState" => '2' ],
						'd|qd|dq' => [ "action_" =>
							[ [ "type_" => '- after o/d', "option" => true ] ], "nextState" => '2' ],
						'D|qD|p' => [ "action_" =>
							[ 'output', [ "type_" => 'bond', "option" => "-" ] ], "nextState" => '3' ] ],
					'amount2' => [
						'1|3' => [ "action_" => 'a=', "nextState" => 'a' ] ],
					'letters' => [
						'0|1|2|3|a|as|b|p|bp|o' => [ "action_" => 'o=', "nextState" => 'o' ],
						'q|dq' => [ "action_" => [ 'output', 'o=' ], "nextState" => 'o' ],
						'd|D|qd|qD' => [ "action_" => 'o after d', "nextState" => 'o' ] ],
					'digits' => [
						'o' => [ "action_" => 'q=', "nextState" => 'q' ],
						'd|D' => [ "action_" => 'q=', "nextState" => 'dq' ],
						'q' => [ "action_" => [ 'output', 'o=' ], "nextState" => 'o' ],
						'a' => [ "action_" => 'o=', "nextState" => 'o' ] ],
					'space A' => [
						'b|p|bp' => [ "action_" => [] ] ],
					'space' => [
						'a' => [ "action_" => [], "nextState" => 'as' ],
						'0' => [ "action_" => 'sb=false' ],
						'1|2' => [ "action_" => 'sb=true' ],
						'r|rt|rd|rdt|rdq' => [ "action_" => 'output', "nextState" => '0' ],
						'*' => [ "action_" => [ 'output', 'sb=true' ], "nextState" => '1' ] ],
					'1st-level escape' => [
						'1|2' => [ "action_" => [ 'output',
							[ "type_" => 'insert+p1', "option" => '1st-level escape' ] ] ],
						'*' => [ "action_" => [ 'output',
							[ "type_" => 'insert+p1', "option" => '1st-level escape' ] ], "nextState" => '0' ] ],
					'[(...)]' => [
						'r|rt' => [ "action_" => 'rd=', "nextState" => 'rd' ],
						'rd|rdt' => [ "action_" => 'rq=', "nextState" => 'rdq' ] ],
					'...' => [
						'o|d|D|dq|qd|qD' => [ "action_" =>
							[ 'output', [ "type_" => 'bond', "option" => "..." ] ], "nextState" => '3' ],
						'*' => [ "action_" => [ [ "type_" => 'output', "option" => 1 ],
							[ "type_" => 'insert', "option" => 'ellipsis' ] ], "nextState" => '1' ] ],
					'. __* ' => [
						'*' => [ "action_" => [ 'output',
							[ "type_" => 'insert', "option" => 'addition compound' ] ], "nextState" => '1' ] ],
					'state of aggregation $' => [
						'*' => [ "action_" => [ 'output', 'state of aggregation' ], "nextState" => '1' ] ],
					'{[(' => [
						'a|as|o' => [ "action_" => [ 'o=', 'output', 'parenthesisLevel++' ], "nextState" => '2' ],
						'0|1|2|3' => [ "action_" => [ 'o=', 'output', 'parenthesisLevel++' ], "nextState" => '2' ],
						'*' => [ "action_" =>
							[ 'output', 'o=', 'output', 'parenthesisLevel++' ], "nextState" => '2' ] ],
					')]}' => [
						'0|1|2|3|b|p|bp|o' => [ "action_" => [ 'o=', 'parenthesisLevel--' ], "nextState" => 'o' ],
						'a|as|d|D|q|qd|qD|dq' =>
							[ "action_" => [ 'output', 'o=', 'parenthesisLevel--' ], "nextState" => 'o' ] ],
					', ' => [
						'*' => [ "action_" => [ 'output', 'comma' ], "nextState" => '0' ] ],
					'^_' => [
						'*' => [ "action_" => [] ] ],
					'^{(...)}|^($...$)' => [
						'0|1|2|as' => [ "action_" => 'b=', "nextState" => 'b' ],
						'p' => [ "action_" => 'b=', "nextState" => 'bp' ],
						'3|o' => [ "action_" => 'd= kv', "nextState" => 'D' ],
						'q' => [ "action_" => 'd=', "nextState" => 'qD' ],
						'd|D|qd|qD|dq' => [ "action_" => [ 'output', 'd=' ], "nextState" => 'D' ] ],
					'^a|^\\x{}{}|^\\x{}|^\\x|\'' => [
						'0|1|2|as' => [ "action_" => 'b=', "nextState" => 'b' ],
						'p' => [ "action_" => 'b=', "nextState" => 'bp' ],
						'3|o' => [ "action_" => 'd= kv', "nextState" => 'd' ],
						'q' => [ "action_" => 'd=', "nextState" => 'qd' ],
						'd|qd|D|qD' => [ "action_" => 'd=' ],
						'dq' => [ "action_" => [ 'output', 'd=' ], "nextState" => 'd' ] ],
					'_{(state of aggregation)}$' => [
						'd|D|q|qd|qD|dq' => [ "action_" => [ 'output', 'q=' ], "nextState" => 'q' ] ],
					'_{(...)}|_($...$)|_9|_\\x{}{}|_\\x{}|_\\x' => [
						'0|1|2|as' => [ "action_" => 'p=', "nextState" => 'p' ],
						'b' => [ "action_" => 'p=', "nextState" => 'bp' ],
						'3|o' => [ "action_" => 'q=', "nextState" => 'q' ],
						'd|D' => [ "action_" => 'q=', "nextState" => 'dq' ],
						'q|qd|qD|dq' => [ "action_" => [ 'output', 'q=' ], "nextState" => 'q' ] ],
					'=<>' => [
						'0|1|2|3|a|as|o|q|d|D|qd|qD|dq' => [ "action_" =>
							[ [ "type_" => 'output', "option" => 2 ], 'bond' ], "nextState" => '3' ] ],
					'#' => [
						'0|1|2|3|a|as|o' => [ "action_" => [ [ "type_" => 'output', "option" => 2 ],
							[ "type_" => 'bond', "option" => "#" ] ], "nextState" => '3' ] ],
					'{}^' => [
						'*' => [ "action_" => [ [ "type_" => 'output', "option" => 1 ],
							[ "type_" => 'insert', "option" => 'tinySkip' ] ], "nextState" => '1' ] ],
					'{}' => [
						'*' => [ "action_" => [ [ "type_" => 'output', "option" => 1 ] ], "nextState" => '1' ] ],
					'{...}' => [
						'0|1|2|3|a|as|b|p|bp' => [ "action_" => 'o=', "nextState" => 'o' ],
						'o|d|D|q|qd|qD|dq' => [ "action_" => [ 'output', 'o=' ], "nextState" => 'o' ] ],
					'$...$' => [
						'a' => [ "action_" => 'a=' ],
						'0|1|2|3|as|b|p|bp|o' => [ "action_" => 'o=', "nextState" => 'o' ],
						'as|o' => [ "action_" => 'o=' ],
						'q|d|D|qd|qD|dq' => [ "action_" => [ 'output', 'o=' ], "nextState" => 'o' ] ],
					'\\bond{(...)}' => [
						'*' => [ "action_" => [
							[ "type_" => 'output', "option" => 2 ], 'bond' ], "nextState" => "3" ] ],
					'\\frac{(...)}' => [
						'*' => [ "action_" => [
							[ "type_" => 'output', "option" => 1 ], 'frac-output' ], "nextState" => '3' ] ],
					'\\overset{(...)}' => [
						'*' => [ "action_" => [
							[ "type_" => 'output', "option" => 2 ], 'overset-output' ], "nextState" => '3' ] ],
					'\\underset{(...)}' => [
						'*' => [ "action_" => [
							[ "type_" => 'output', "option" => 2 ], 'underset-output' ], "nextState" => '3' ] ],
					'\\underbrace{(...)}' => [
						'*' => [ "action_" => [
							[ "type_" => 'output', "option" => 2 ], 'underbrace-output' ], "nextState" => '3' ] ],
					'\\color{(...)}{(...)}' => [
						'*' => [ "action_" => [
							[ "type_" => 'output', "option" => 2 ], 'color-output' ], "nextState" => '3' ] ],
					'\\color{(...)}' => [
						'*' => [ "action_" => [ [ "type_" => 'output', "option" => 2 ], 'color0-output' ] ] ],
					'\\ce{(...)}' => [
						'*' => [ "action_" => [ [ "type_" => 'output', "option" => 2 ], 'ce' ], "nextState" => '3' ] ],
					'\\,' => [
						'*' => [ "action_" => [
							[ "type_" => 'output', "option" => 1 ], 'copy' ], "nextState" => '1' ] ],
					'\\pu{(...)}' => [
						'*' => [ "action_" => [ 'output', [ "type_" => 'write', "option" => "{" ],
							'pu', [ "type_" => 'write', "option" => "}" ] ], "nextState" => '3' ] ],
					'\\x{}{}|\\x{}|\\x' =>
						[ '0|1|2|3|a|as|b|p|bp|o|c0' => [ "action_" => [ 'o=', 'output' ], "nextState" => '3' ],
						'*' => [ "action_" => [ 'output', 'o=', 'output' ], "nextState" => '3' ] ],
					'others' => [ '*' => [ "action_" =>
						[ [ "type_" => 'output', "option" => 1 ], 'copy' ], "nextState" => '3' ] ],
					'else2' => [ 'a' => [ "action_" => 'a to o', "nextState" => 'o', "revisit" => true ],
						'as' => [ "action_" => [ 'output', 'sb=true' ], "nextState" => '1', "revisit" => true ],
						'r|rt|rd|rdt|rdq' => [ "action_" => [ 'output' ], "nextState" => '0', "revisit" => true ],
						'*' => [ "action_" => [ 'output', 'copy' ], "nextState" => '3' ] ]
				] ),
				"actions" => [
					'o after d' => function ( &$buffer, $m ) {
						if ( preg_match( "/^[1-9][0-9]*$/", $buffer['d'] ?? "" ) ) {
							$tmp = $buffer["d"];
							$buffer["d"] = null;
							$ret = $this->stateMachines["ce"]["actions"]["output"]( $buffer, null, null );
							$ret[] = [ "type_" => "tinySkip" ];
							$buffer["b"] = $tmp;
						} else {
							$ret = $this->stateMachines["ce"]["actions"]["output"]( $buffer, null, null );
						}

						/** @phan-suppress-next-line PhanParamTooFew */
						$this->genericActions['o=']( $buffer, $m );
						return $ret;
					},
					'd= kv' => static function ( &$buffer, $m ) {
						$buffer["d"] = $m;
						$buffer["dType"] = "kv";
						return null;
					},
					'charge or bond' => function ( &$buffer, $m ) {
						if ( MhchemUtil::issetJS( $buffer['beginsWithBond'] ?? null ) ) {
							$ret = [];
							$im = $this->stateMachines["ce"]["actions"]["output"]( $buffer, null, null );
							MhchemUtil::concatArray( $ret, $im );

							/** @phan-suppress-next-line PhanParamTooMany */
							MhchemUtil::concatArray( $ret, $this->genericActions['bond']( $buffer, $m, "-" ) );
							return $ret;
						} else {
							$buffer["d"] = $m;
							return null;
						}
					},
					'- after o/d' => function ( &$buffer, $m, $isAfterD ) {
						$c1 = $this->mhchemParser->getPatterns()->match( 'orbital', $buffer["o"] ?? "" );
						$c2 = $this->mhchemParser->getPatterns()->match( 'one lowercase greek letter $',
							$buffer["o"] ?? "" );
						$c3 = $this->mhchemParser->getPatterns()->match( 'one lowercase latin letter $',
							$buffer["o"] ?? "" );
						$c4 = $this->mhchemParser->getPatterns()->match( '$one lowercase latin letter$ $',
							$buffer["o"] ?? "" );
						$hyphenFollows = $m === "-" && ( ( isset( $c1["remainder"] ) && $c1["remainder"] === "" )
								|| isset( $c2 ) || isset( $c3 ) || isset( $c4 ) );
						if ( $hyphenFollows && !isset( $buffer["a"] ) && !isset( $buffer["b"] )
							&& !isset( $buffer["p"] ) && !isset( $buffer["d"] )
							&& !isset( $buffer["q"] ) && !$c1 && $c3 ) {
							$buffer["o"] = '$' . $buffer["o"] . '$';
						}
						$ret = [];
						if ( $hyphenFollows ) {
							$im = $this->stateMachines["ce"]["actions"]["output"]( $buffer, null, null );
							MhchemUtil::concatArray( $ret, $im );
							$ret[] = [ "type_" => 'hyphen' ];
						} else {
							$c1 = $this->mhchemParser->getPatterns()->match( 'digits', $buffer["d"] ?? "" );
							if ( $isAfterD && isset( $c1["remainder"] ) && $c1["remainder"] === '' ) {
								/** @phan-suppress-next-line PhanParamTooFew */
								MhchemUtil::concatArray( $ret, $this->genericActions['d=']( $buffer, $m ) );
								$im = $this->stateMachines["ce"]["actions"]["output"]( $buffer, null, null );
								MhchemUtil::concatArray( $ret, $im );
							} else {
								$im = $this->stateMachines["ce"]["actions"]["output"]( $buffer, null, null );
								MhchemUtil::concatArray( $ret, $im );
								/** @phan-suppress-next-line PhanParamTooMany */
								MhchemUtil::concatArray( $ret, $this->genericActions['bond']( $buffer, $m, "-" ) );
							}
						}
						return $ret;
					},
					'a to o' => static function ( &$buffer ) {
						$buffer["o"] = $buffer["a"];
						$buffer["a"] = null;
						return null;
					},
					'sb=true' => static function ( &$buffer ) {
						$buffer["sb"] = true;
						return null;
					},
					'sb=false' => static function ( &$buffer ) {
						$buffer["sb"] = false;
						return null;
					},
					'beginsWithBond=true' => static function ( &$buffer ) {
						$buffer['beginsWithBond'] = true;
						return null;
					},
					'beginsWithBond=false' => static function ( &$buffer ) {
						$buffer['beginsWithBond'] = false;
						return null;
					},
					'parenthesisLevel++' => static function ( &$buffer ) {
						$buffer['parenthesisLevel']++;
						return null;
					},
					'parenthesisLevel--' => static function ( &$buffer ) {
						$buffer['parenthesisLevel']--;
						return null;
					},
					'state of aggregation' => function ( $_buffer, $m ) {
						return [ "type_" => 'state of aggregation',
							"p1" => $this->mhchemParser->go( $m, 'o' ) ];
					},
					'comma' => static function ( $buffer, $m ) {
						// $a = preg_replace('/\s*$/', '', $m); tbd: final check if using rtrim is ok
						$a = rtrim( $m );
						$withSpace = ( $a !== $m );
						if ( $withSpace && $buffer['parenthesisLevel'] === 0 ) {
							return [ "type_" => 'comma enumeration L', "p1" => $a ];
						} else {
							return [ "type_" => 'comma enumeration M', "p1" => $a ];
						}
					},
					'output' => function ( &$buffer, $_m, $entityFollows ) {
						if ( !isset( $buffer["r"] ) ) {
							$ret = [];
							if ( !isset( $buffer["a"] ) && !isset( $buffer["b"] ) && !isset( $buffer["p"] )
								&& !isset( $buffer["o"] ) && !isset( $buffer["q"] )
								&& !isset( $buffer["d"] ) && !$entityFollows ) {
								// do nothing.
							} else {
								if ( MhchemUtil::issetJS( $buffer["sb"] ?? null ) ) {
									$ret[] = [ "type_" => 'entitySkip' ];
								}
								if ( !isset( $buffer["o"] ) && !isset( $buffer["q"] ) && !isset( $buffer["d"] )
									&& !isset( $buffer["b"] ) && !isset( $buffer["p"] ) && $entityFollows !== 2 ) {
									$buffer["o"] = $buffer["a"] ?? null;
									$buffer["a"] = null;
								} elseif ( !isset( $buffer["o"] ) && !isset( $buffer["q"] ) && !isset( $buffer["d"] )
									&& ( isset( $buffer["b"] ) || isset( $buffer["p"] ) ) ) {
									$buffer["o"] = $buffer["a"] ?? null;
									$buffer["d"] = $buffer["b"] ?? null;
									$buffer["q"] = $buffer["p"] ?? null;
									$buffer["a"] = $buffer["b"] = $buffer["p"] = null;
								} else {
									if ( isset( $buffer["o"] ) && isset( $buffer["dType"] ) && $buffer["dType"] === 'kv'
										&& $this->mhchemParser->getPatterns()->match( 'd-oxidation$',
											$buffer["d"] ?? "" ) ) {
										$buffer["dType"] = 'oxidation';
									} elseif ( isset( $buffer["o"] ) && isset( $buffer["dType"] )
										&& $buffer["dType"] === 'kv' && !isset( $buffer["q"] ) ) {
										$buffer["dType"] = null;
									}
								}

								$retIm = [
									"type_" => 'chemfive',
									"a" => $this->mhchemParser->go( $buffer["a"] ?? null, 'a' ),
									"b" => $this->mhchemParser->go( $buffer["b"] ?? null, 'bd' ),
									"p" => $this->mhchemParser->go( $buffer["p"] ?? null, 'pq' ),
									"o" => $this->mhchemParser->go( $buffer["o"] ?? null, 'o' ),
									"q" => $this->mhchemParser->go( $buffer["q"] ?? null, 'pq' ),
									"d" => $this->mhchemParser->go( $buffer["d"] ?? null,
										( isset( $buffer["dType"] )
										&& $buffer["dType"] === 'oxidation' ? 'oxidation' : 'bd' ) ),
								];

								if ( isset( $buffer["dType"] ) ) {
									$retIm["dType"] = $buffer["dType"];
								}

								$ret[] = $retIm;

							}
						} else {
							if ( isset( $buffer["rdt"] ) && $buffer["rdt"] === 'M' ) {
								$rd = $this->mhchemParser->go( $buffer["rd"], 'tex-math' );
							} elseif ( isset( $buffer["rdt"] ) && $buffer["rdt"] === 'T' ) {
								// tbd double array ok ?
								$rd = [ [ "type_" => 'text', "p1" => $buffer["rd"] ?? "" ] ];
							} else {
								$rd = $this->mhchemParser->go( $buffer["rd"] ?? null, 'ce' );
							}

							if ( isset( $buffer["rqt"] ) && $buffer["rqt"] === 'M' ) {
								$rq = $this->mhchemParser->go( $buffer["rq"], 'tex-math' );
							} elseif ( isset( $buffer["rqt"] ) && $buffer["rqt"] === 'T' ) {
								$rq = [ [ "type_" => 'text', "p1" => $buffer["rq"] ?? "" ] ];
							} else {
								$rq = $this->mhchemParser->go( $buffer["rq"] ?? null, 'ce' );
							}
							$ret = [
								"type_" => 'arrow',
								"r" => $buffer["r"],
								"rd" => $rd,
								"rq" => $rq
							];
						}
						foreach ( $buffer as $key => $value ) {
							if ( $key !== 'parenthesisLevel' && $key !== 'beginsWithBond' ) {
								unset( $buffer[$key] );
							}
						}
						return $ret;
					},
					'oxidation-output' => function ( $_buffer, $m ) {
						$ret = [ "{" ];
						MhchemUtil::concatArray( $ret, $this->mhchemParser->go( $m, 'oxidation' ) );
						$ret[] = "}";
						return $ret;
					},
					'frac-output' => function ( $_buffer, $m ) {
						return [ "type_" => 'frac-ce',
								"p1" => $this->mhchemParser->go( $m[0] ?? null, 'ce' ),
								"p2" => $this->mhchemParser->go( $m[1] ?? null, 'ce' ) ];
					},
					'overset-output' => function ( $_buffer, $m ) {
						return [ "type_" => 'overset',
								"p1" => $this->mhchemParser->go( $m[0] ?? null, 'ce' ),
								"p2" => $this->mhchemParser->go( $m[1] ?? null, 'ce' ) ];
					},
					'underset-output' => function ( $_buffer, $m ) {
						return [ "type_" => 'underset',
								"p1" => $this->mhchemParser->go( $m[0] ?? null, 'ce' ),
								"p2" => $this->mhchemParser->go( $m[1] ?? null, 'ce' ) ];
					},
					'underbrace-output' => function ( $_buffer, $m ) {
						return [ "type_" => 'underbrace',
								"p1" => $this->mhchemParser->go( $m[0] ?? null, 'ce' ),
								"p2" => $this->mhchemParser->go( $m[1] ?? null, 'ce' ) ];
					},
					'color-output' => function ( $_buffer, $m ) {
						return [ "type_" => 'color', "color1" => $m[0] ?? null,
								"color2" => $this->mhchemParser->go( $m[1] ?? null, 'ce' ) ];
					},
					'r=' => static function ( &$buffer, $m ) {
						$buffer["r"] = $m;
						return null;
					},
					'rdt=' => static function ( &$buffer, $m ) {
						$buffer["rdt"] = $m;
						return null;
					},
					'rd=' => static function ( &$buffer, $m ) {
						$buffer["rd"] = $m;
						return null;
					},
					'rqt=' => static function ( &$buffer, $m ) {
						$buffer["rqt"] = $m;
						return null;
					},
					'rq=' => static function ( &$buffer, $m ) {
						$buffer["rq"] = $m;
						return null;
					},
					'operator' => static function ( &$_buffer, $m, $p1 ) {
						return [ "type_" => 'operator', "kind_" => ( $p1 ?: $m ) ];
					},
				],
			],
			'a' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => [] ]
					],
					'1/2$' => [
						'0' => [ "action_" => '1/2' ]
					],
					'else' => [
						'0' => [ "action_" => [], "nextState" => '1', "revisit" => true ]
					],
					'${(...)}$__$(...)$' => [
						'*' => [ "action_" => 'tex-math tight', "nextState" => '1' ]
					],
					',' => [
						'*' => [ "action_" => [ [ "type_" => 'insert', "option" => 'commaDecimal' ] ] ]
					],
					'else2' => [
						'*' => [ "action_" => 'copy' ]
					]
				] ),
				"actions" => []
			],
			'o' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => [] ]
					],
					'1/2$' => [
						'0' => [ "action_" => '1/2' ]
					],
					'else' => [
						'0' => [ "action_" => [], "nextState" => '1', "revisit" => true ]
					],
					'letters' => [
						'*' => [ "action_" => 'rm' ]
					],
					'\\ca' => [
						'*' => [ "action_" => [ [ "type_" => 'insert', "option" => 'circa' ] ] ]
					],
					'\\pu{(...)}' => [
						'*' => [ "action_" => [ [ "type_" => 'write', "option" => "{" ],
							'pu', [ "type_" => 'write', "option" => "}" ] ] ]
					],
					'\\x{}{}|\\x{}|\\x' => [
						'*' => [ "action_" => 'copy' ]
					],
					'${(...)}$__$(...)$' => [
						'*' => [ "action_" => 'tex-math' ]
					],
					'{(...)}' => [
						'*' => [ "action_" => [ [ "type_" => 'write', "option" => "{" ],
							'text', [ "type_" => 'write', "option" => "}" ] ] ]
					],
					'else2' => [
						'*' => [ "action_" => 'copy' ]
					]
				] ),
				"actions" => []
			],
			'text' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => 'output' ]
					],
					'{...}' => [
						'*' => [ "action_" => 'text=' ]
					],
					'${(...)}$__$(...)$' => [
						'*' => [ "action_" => 'tex-math' ]
					],
					'\\greek' => [
						'*' => [ "action_" => [ 'output', 'rm' ] ]
					],
					'\\pu{(...)}' => [
						'*' => [ "action_" => [ 'output', [ "type_" => 'write', "option" => "{" ],
							'pu', [ "type_" => 'write', "option" => "}" ] ] ]
					],
					'\\,|\\x{}{}|\\x{}|\\x' => [
						'*' => [ "action_" => [ 'output', 'copy' ] ]
					],
					'else' => [
						'*' => [ "action_" => 'text=' ]
					],
				] ),
				"actions" => [
					'output' => static function ( &$buffer ) {
						if ( isset( $buffer["text_"] ) ) {
							$ret = [ "type_" => 'text', "p1" => $buffer["text_"] ];
							foreach ( $buffer as $key => $value ) {
								unset( $buffer[$key] );
							}
							return $ret;
						}
						return null;
					}
				]
			],
			'pq' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => [] ]
					],
					'state of aggregation $' => [
						'*' => [ "action_" => 'state of aggregation' ]
					],
					'i$' => [
						'0' => [ "action_" => [], "nextState" => '!f', "revisit" => true ]
					],
					'(KV letters),' => [
						'0' => [ "action_" => 'rm', "nextState" => '0' ]
					],
					'formula$' => [
						'0' => [ "action_" => [], "nextState" => 'f', "revisit" => true ]
					],
					'1/2$' => [
						'0' => [ "action_" => '1/2' ]
					],
					'else' => [
						'0' => [ "action_" => [], "nextState" => '!f', "revisit" => true ]
					],
					'${(...)}$__$(...)$' => [
						'*' => [ "action_" => 'tex-math' ]
					],
					'{(...)}' => [
						'*' => [ "action_" => 'text' ]
					],
					'a-z' => [
						'f' => [ "action_" => 'tex-math' ]
					],
					'letters' => [
						'*' => [ "action_" => 'rm' ]
					],
					'-9.,9' => [
						'*' => [ "action_" => '9,9' ]
					],
					',' => [
						'*' => [ "action_" => [ [ "type_" => 'insert+p1', "option" => 'comma enumeration S' ] ] ]
					],
					'\\color{(...)}{(...)}' => [
						'*' => [ "action_" => 'color-output' ]
					],
					'\\color{(...)}' => [
						'*' => [ "action_" => 'color0-output' ]
					],
					'\\ce{(...)}' => [
						'*' => [ "action_" => 'ce' ]
					],
					'\\pu{(...)}' => [
						'*' => [ "action_" => [ [ "type_" => 'write', "option" => "{" ],
							'pu', [ "type_" => 'write', "option" => "}" ] ] ]
					],
					'\\,|\\x{}{}|\\x{}|\\x' => [
						'*' => [ "action_" => 'copy' ]
					],
					'else2' => [
						'*' => [ "action_" => 'copy' ]
					]
				] ),
				"actions" => [
					'state of aggregation' => function ( $_buffer, $m ) {
						return [ "type_" => 'state of aggregation subscript',
							"p1" => $this->mhchemParser->go( $m, 'o' ) ];
					},
					'color-output' => function ( $_buffer, $m ) {
						return [ "type_" => 'color', "color1" => $m[0] ?? null,
							"color2" => $this->mhchemParser->go( $m[1] ?? null, 'pq' ) ];
					}
				]
			],
			'bd' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => [] ]
					],
					'x$' => [
						'0' => [ "action_" => [], "nextState" => '!f', "revisit" => true ]
					],
					'formula$' => [
						'0' => [ "action_" => [], "nextState" => 'f', "revisit" => true ]
					],
					'else' => [
						'0' => [ "action_" => [], "nextState" => '!f', "revisit" => true ]
					],
					'-9.,9 no missing 0' => [
						'*' => [ "action_" => '9,9' ]
					],
					'.' => [
						'*' => [ "action_" => [ [ "type_" => 'insert', "option" => 'electron dot' ] ] ]
					],
					'a-z' => [
						'f' => [ "action_" => 'tex-math' ]
					],
					'x' => [
						'*' => [ "action_" => [ [ "type_" => 'insert', "option" => 'KV x' ] ] ]
					],
					'letters' => [
						'*' => [ "action_" => 'rm' ]
					],
					'\'' => [
						'*' => [ "action_" => [ [ "type_" => 'insert', "option" => 'prime' ] ] ]
					],
					'${(...)}$__$(...)$' => [
						'*' => [ "action_" => 'tex-math' ]
					],
					'{(...)}' => [
						'*' => [ "action_" => 'text' ]
					],
					'\\color{(...)}{(...)}' => [
						'*' => [ "action_" => 'color-output' ]
					],
					'\\color{(...)}' => [
						'*' => [ "action_" => 'color0-output' ]
					],
					'\\ce{(...)}' => [
						'*' => [ "action_" => 'ce' ]
					],
					'\\pu{(...)}' => [
						'*' => [ "action_" => [ [ "type_" => 'write', "option" => "{" ],
							'pu', [ "type_" => 'write', "option" => "}" ] ] ]
					],
					'\\,|\\x{}{}|\\x{}|\\x' => [
						'*' => [ "action_" => 'copy' ]
					],
					'else2' => [
						'*' => [ "action_" => 'copy' ]
					]
				] ),
				"actions" => [
					'color-output' => function ( $_buffer, $m ) {
						return [ "type_" => 'color', "color1" => $m[0] ?? null,
								"color2" => $this->mhchemParser->go( $m[1] ?? null, 'bd' ) ];
					}
				]
			],
			'oxidation' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => 'roman-numeral' ]
					],
					'pm-operator' => [
						'*' => [ "action_" => [ [ "type_" => 'o=+p1', "option" => '\\pm' ] ] ]
					],
					'else' => [
						'*' => [ "action_" => 'o=' ]
					]
				] ),
				"actions" => [
					'roman-numeral' => static function ( $buffer ) {
						return [ "type_" => 'roman numeral', "p1" => $buffer["o"] ?? "" ];
					}
				]
			],
			'tex-math' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => 'output' ]
					],
					'\\ce{(...)}' => [
						'*' => [ "action_" => [ 'output', 'ce' ] ]
					],
					'\\pu{(...)}' => [
						'*' => [ "action_" => [ 'output', [ "type_" => 'write', "option" => "{" ],
							'pu', [ "type_" => 'write', "option" => "}" ] ] ]
					],
					'{...}|\\,|\\x{}{}|\\x{}|\\x' => [
						'*' => [ "action_" => 'o=' ]
					],
					'else' => [
						'*' => [ "action_" => 'o=' ]
					]
				] ),
				"actions" => [
					'output' => static function ( &$buffer ) {
						if ( isset( $buffer["o"] ) ) {
							$ret = [ "type_" => 'tex-math', "p1" => $buffer["o"] ];
							foreach ( $buffer as $key => $value ) {
								unset( $buffer[$key] );
							}
							return $ret;
						}
						return null;
					}
				]
			],
			'tex-math tight' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => 'output' ]
					],
					'\\ce{(...)}' => [
						'*' => [ "action_" => [ 'output', 'ce' ] ]
					],
					'\\pu{(...)}' => [
						'*' => [ "action_" => [ 'output', [ "type_" => 'write', "option" => "{" ],
							'pu', [ "type_" => 'write', "option" => "}" ] ] ]
					],
					'{...}|\\,|\\x{}{}|\\x{}|\\x' => [
						'*' => [ "action_" => 'o=' ]
					],
					'-|+' => [
						'*' => [ "action_" => 'tight operator' ]
					],
					'else' => [
						'*' => [ "action_" => 'o=' ]
					]
				] ),
				"actions" => [
					'tight operator' => static function ( &$buffer, $m ) {
						$buffer["o"] = ( $buffer["o"] ?? "" ) . "{" . $m . "}";
						return null;
					},
					'output' => static function ( &$buffer ) {
						if ( $buffer["o"] ) {
							$ret = [ "type_" => 'tex-math', "p1" => $buffer["o"] ];
							foreach ( $buffer as $key => $value ) {
								unset( $buffer[$key] );
							}
							return $ret;
						}
						return null;
					}
				]
			],
			'9,9' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => [] ]
					],
					',' => [
						'*' => [ "action_" => 'comma' ]
					],
					'else' => [
						'*' => [ "action_" => 'copy' ]
					]
				] ),
				"actions" => [
					'comma' => static function () {
						return [ "type_" => 'commaDecimal' ];
					}
				]
			],
			'pu' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => 'output' ]
					],
					'space$' => [
						'*' => [ "action_" => [ 'output', 'space' ] ]
					],
					'{[(|)]}' => [
						'0|a' => [ "action_" => 'copy' ]
					],
					'(-)(9)^(-9)' => [
						'0' => [ "action_" => 'number^', "nextState" => 'a' ]
					],
					'(-)(9.,9)(e)(99)' => [
						'0' => [ "action_" => 'enumber', "nextState" => 'a' ]
					],
					'space' => [
						'0|a' => [ "action_" => [] ]
					],
					'pm-operator' => [
						'0|a' => [ "action_" => [ [ "type_" => 'operator', "option" => '\\pm' ] ], "nextState" => '0' ]
					],
					'operator' => [
						'0|a' => [ "action_" => 'copy', "nextState" => '0' ]
					],
					'//' => [
						'd' => [ "action_" => 'o=', "nextState" => '/' ]
					],
					'/' => [
						'd' => [ "action_" => 'o=', "nextState" => '/' ]
					],
					'{...}|else' => [
						'0|d' => [ "action_" => 'd=', "nextState" => 'd' ],
						'a' => [ "action_" => [ 'space', 'd=' ], "nextState" => 'd' ],
						'/|q' => [ "action_" => 'q=', "nextState" => 'q' ]
					]
				] ),
				"actions" => [
					'enumber' => function ( $_buffer, $m ) {
						$ret = [];
						if ( MU::issetJS( $m[0] ?? null ) && ( $m[0] === "+-" || $m[0] === "+/-" ) ) {
							$ret[] = "\\pm ";
						} elseif ( MU::issetJS( $m[0] ?? null ) ) {
							$ret[] = $m[0];
						}
						if ( MU::issetJS( $m[1] ?? null ) ) {
							MhchemUtil::concatArray( $ret, $this->mhchemParser->go( $m[1], 'pu-9,9' ) );

							if ( MU::issetJS( ( $m[2] ?? null ) ) ) {
								if ( preg_match( "/[,.]/", $m[2] ) ) {
									MhchemUtil::concatArray( $ret,
										$this->mhchemParser->go( $m[2], 'pu-9,9' ) );
								} else {
									$ret[] = $m[2];
								}
							}

							if ( MU::issetJS( $m[3] ?? null ) || MU::issetJS( $m[4] ?? null ) ) {
								if ( $m[3] === "e" || $m[4] === "*" ) {
									$ret[] = [ "type_" => 'cdot' ];
								} else {
									$ret[] = [ "type_" => 'times' ];
								}
							}
						}

						if ( MU::issetJS( $m[5] ?? null ) ) {
							$ret[] = "10^{" . $m[5] . "}";
						}

						return $ret;
					},
					'number^' => function ( $_buffer, $m ) {
						$ret = [];
						if ( isset( $m[0] ) && ( $m[0] === "+-" || $m[0] === "+/-" ) ) {
							$ret[] = "\\pm ";
						} elseif ( isset( $m[0] ) ) {
							$ret[] = $m[0];
						}
						MhchemUtil::concatArray( $ret,
							$this->mhchemParser->go( $m[1] ?? null, 'pu-9,9' ) );
						$ret[] = "^{" . ( $m[2] ?? "" ) . "}";
						return $ret;
					},
					'operator' => static function ( $_buffer, $m, $p1 ) {
						return [ "type_" => 'operator', "kind_" => $p1 ?? $m ];
					},
					'space' => static function () {
						return [ "type_" => 'pu-space-1' ];
					},
					'output' => function ( &$buffer ) {
						$md = $this->mhchemParser->getPatterns()->match( '{(...)}', $buffer["d"] ?? "" );
						if ( $md && $md["remainder"] === '' ) {
							$buffer["d"] = $md["match_"];
						}
						$mq = $this->mhchemParser->getPatterns()->match( '{(...)}', $buffer["q"] ?? "" );
						if ( $mq && $mq["remainder"] === '' ) {
							$buffer["q"] = $mq["match_"];
						}
						if ( isset( $buffer["d"] ) ) {
							// tbd: g modifiers necessary in regexes ?
							$buffer["d"] = preg_replace( '/\x{00B0}C|\^oC|\^{o}C/u',
								"{}^{\\circ}C", $buffer["d"] );
							$buffer["d"] = preg_replace( '/\x{00B0}F|\^oF|\^{o}F/u',
								"{}^{\\circ}C", $buffer["d"] );
						}
						if ( isset( $buffer["q"] ) ) {
							$buffer["q"] = preg_replace( "/\x{00B0}C|\^oC|\^{o}C/u",
								"{}^{\\circ}C", $buffer["q"] );
							$buffer["q"] = preg_replace( "/\x{00B0}F|\^oF|\^{o}F/u",
								"{}^{\\circ}F", $buffer["q"] );
							$b5 = [
								"d" => $this->mhchemParser->go( $buffer["d"] ?? "", 'pu' ),
								"q" => $this->mhchemParser->go( $buffer["q"], 'pu' )
							];
							if ( $buffer["o"] === '//' ) {
								$ret = [ "type_" => 'pu-frac', "p1" => $b5["d"], "p2" => $b5["q"] ];
							} else {
								$ret = $b5["d"];
								if ( count( $b5["d"] ) > 1 || count( $b5["q"] ) > 1 ) {
									$ret[] = [ "type_" => ' / ' ];
								} else {
									$ret[] = [ "type_" => '/' ];
								}
								MhchemUtil::concatArray( $ret, $b5["q"] );
							}
						} else {
							$ret = $this->mhchemParser->go( $buffer["d"] ?? null, 'pu-2' );
						}

						foreach ( $buffer as $key => $value ) {
							unset( $buffer[$key] );
						}
						return $ret;
					}
				],
			],
			'pu-2' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'*' => [ "action_" => 'output' ],
					],
					'*' => [
						'*' => [ "action_" => [ 'output', 'cdot' ], "nextState" => '0' ],
					],
					'\\x' => [ '*' => [ "action_" => 'rm=' ] ],
					'space' => [ '*' => [ "action_" => [ 'output', 'space' ], "nextState" => '0' ] ],

					'^{(...)}|^(-1)' => [ '1' => [ "action_" => '^(-1)' ] ],
					'-9.,9' => [
						'0' => [ "action_" => 'rm=', "nextState" => '0' ],
						'1' => [ "action_" => '^(-1)', "nextState" => '0' ]
					],
					'{...}|else' => [ '*' => [ "action_" => 'rm=', "nextState" => '1' ] ]
				] ),
				"actions" => [
					'cdot' => static function () {
						return [ "type_" => 'tight cdot' ];
					},
					'^(-1)' => static function ( &$buffer, $m ) {
						$buffer["rm"] .= "^{" . $m . "}";
						return null;
					},
					'space' => static function () {
						return [ "type_" => 'pu-space-2' ];
					},

					'output' => function ( &$buffer ){
						$ret = [];
						if ( MU::issetJS( $buffer["rm"] ) ) {
							$mrm = $this->mhchemParser->getPatterns()->match( '{(...)}', $buffer["rm"] ?? "" );
							if ( isset( $mrm["remainder"] ) && $mrm["remainder"] === '' ) {
								$ret = $this->mhchemParser->go( $mrm["match_"], 'pu' );
							} else {
								$ret = [ "type_" => 'rm', "p1" => $buffer["rm"] ];
							}
						}
						foreach ( $buffer as $key => $value ) {
							unset( $buffer[$key] );
						}
						return $ret;
					}
				]
			],
			'pu-9,9' => [
				"transitions" => static::mhchemCreateTransitions( [
					'empty' => [
						'0' => [ "action_" => 'output-0' ],
						'o' => [ "action_" => 'output-o' ]
					],
					',' => [
						'0' => [ "action_" => [ 'output-0', 'comma' ], "nextState" => 'o' ]
					],
					'.' => [
						'0' => [ "action_" => [ 'output-0', 'copy' ], "nextState" => 'o' ]
					],
					'else' => [ '*' => [ "action_" => 'text=' ] ]
				] ),
				"actions" => [
					'comma' => static function () {
						return [ "type_" => 'commaDecimal' ];
					},
					'output-0' => static function ( &$buffer ) {
						$ret = [];
						$buffer["text_"] ??= "";
						if ( strlen( $buffer["text_"] ) > 4 ) {
							$a = strlen( $buffer["text_"] ) % 3;
							if ( $a === 0 ) {
								$a = 3;
							}
							for ( $i = strlen( $buffer["text_"] ) - 3; $i > 0; $i -= 3 ) {
								$ret[] = substr( $buffer["text_"], $i, 3 );
								$ret[] = [ "type_" => '1000 separator' ];
							}
							$ret[] = substr( $buffer["text_"], 0, $a );
							$ret = array_reverse( $ret );
						} else {
							$ret[] = $buffer["text_"];
						}
						foreach ( $buffer as $key => $value ) {
							unset( $buffer[$key] );
						}
						return $ret;
					},
					'output-o' => static function ( &$buffer ) {
						$ret = [];
						$buffer["text_"] ??= "";
						if ( strlen( $buffer["text_"] ) > 4 ) {
							$a = strlen( $buffer["text_"] ) - 3;
							for ( $i = 0; $i < $a; $i += 3 ) {
								$ret[] = substr( $buffer["text_"], $i, 3 );
								$ret[] = [ "type_" => '1000 separator' ];
							}
							$ret[] = substr( $buffer["text_"], $i );
						} else {
							$ret[] = $buffer["text_"];
						}
						foreach ( $buffer as $key => $value ) {
							unset( $buffer[$key] );
						}
						return $ret;
					}
				]
			]

		];
	}

}
