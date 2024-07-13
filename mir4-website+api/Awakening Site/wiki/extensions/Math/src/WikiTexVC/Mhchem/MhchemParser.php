<?php
/**
 * Copyright (c) 2023 Johannes Stegmüller
 *
 * This file is a port of mhchemParser originally authored by Martin Hensel in javascript/typescript.
 * The original license for this software can be found in the accompanying LICENSE.mhchemParser-ts.txt file.
 */

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Port of mhchemParser v4.2.2 by Martin Hensel (https://github.com/mhchem/mhchemParser)
 * from typescript/javascript to PHP.
 *
 * This class contains the go (¸l.89 in mhchemParser.js)
 * and the toTex function (l.39 of mhchemParser.js)
 *
 * For usage of mhchemParser in PHP instantiate this class and call toTex-Function.
 *
 * @author Johannes Stegmüller
 * @license GPL-2.0-or-later
 */
class MhchemParser {
	/** @var MhchemPatterns */
	private MhchemPatterns $mhchemPatterns;

	/** @var MhchemStateMachines */
	private MhchemStateMachines $mhchemStateMachines;

	/** @var LoggerInterface */
	private $logger;
	/** @var int */
	private int $debugIndex;

	/**
	 * Instantiate Mhchemparser, required for usage of "toTex" functionality
	 * @param bool $doLogging debug log internal state changes and input output for each state
	 */
	public function __construct( bool $doLogging = false ) {
		$this->mhchemPatterns = new MhchemPatterns();
		$this->mhchemStateMachines = new MhchemStateMachines( $this );
		$this->debugIndex = 0;
		if ( $doLogging ) {
			$this->logger = LoggerFactory::getInstance( 'Math' );
		}
	}

	public function getPatterns(): MhchemPatterns {
		return $this->mhchemPatterns;
	}

	/**
	 * @param string $input input formula in tex eventually containing chemical environments or physical units
	 * @param string $type currently ce or pu (physical units)
	 * @param bool $optimizeMhchemForTexVC optimize the output of mhchem for usage in WikiTexVC, usually extra curlies
	 * surrounding parameters which specify dimensions
	 * @return string
	 */
	public function toTex( $input, $type, bool $optimizeMhchemForTexVC = false ): string {
		$parsed = $this->go( $input, $type );
		$mhchemTexifiy = new MhchemTexify( $optimizeMhchemForTexVC );
		return $mhchemTexifiy->go( $parsed, $type !== "tex" );
	}

	public function go( $input, $stateMachine ): array {
		if ( !MhchemUtil::issetJS( $input ) ) {
			return [];
		}

		if ( !MhchemUtil::issetJS( $stateMachine ) ) {
			$stateMachine = 'ce';
		}

		$state = '0';
		$buffer = [];

		$buffer['parenthesisLevel'] = 0;

		if ( $input != null ) {
			$input = preg_replace( "/\n/", "", $input );
			$input = preg_replace( "/[\x{2212}\x{2013}\x{2014}\x{2010}]/u", "-", $input );
			$input = preg_replace( "/[\x{2026}]/u", "...", $input );

		}

		// Looks through _mhchemParser.transitions, to execute a matching action
		// (recursive)actions
		$lastInput = "";
		$watchdog = 10;
		$output = [];
		while ( true ) {
			if ( $lastInput !== $input ) {
				$watchdog = 10;
				$lastInput = $input;
			} else {
				$watchdog--;
			}

			// Find actions in transition table
			$machine = $this->mhchemStateMachines->stateMachines[$stateMachine];
			$t = $machine["transitions"][$state] ?? $machine["transitions"]['*'];

			for ( $i = 0; $i < count( $t ); $i++ ) {
				$matches = $this->mhchemPatterns->match( $t[$i]["pattern"], $input ?? "" );

				if ( $matches ) {
					if ( $this->logger ) {
						$this->logger->debug( "\n Match at: " . $i . "\tPattern: " . $t[$i]["pattern"] .
								"\t State-machine: " . $stateMachine );
					}

					// Execute actions
					$task = $t[$i]["task"];
					for ( $iA = 0; $iA < count( $task["action_"] ); $iA++ ) {
						$this->debugIndex++;

						$o = null;

						// Find and execute action
						if ( array_key_exists( $task["action_"][$iA]["type_"], $machine["actions"] ) ) {
							$option = $task["action_"][$iA]["option"] ?? null; // tbd, setting null ok ?
							if ( $this->logger ) {
								$this->logger->debug( "\n action: \t" . $task["action_"][$iA]["type_"] );
							}
							$o = $machine["actions"][$task["action_"][$iA]["type_"]]
								( $buffer, $matches["match_"], $option );
						} elseif ( array_key_exists( $task["action_"][$iA]["type_"],
									$this->mhchemStateMachines->getGenericActions() ) ) {
							$option = $task["action_"][$iA]["option"] ?? null;
							if ( $this->logger ) {
								$this->logger->debug( "\n action: \t" . $task["action_"][$iA]["type_"] );
							}
							$o = $this->mhchemStateMachines->getGenericActions()
								[$task["action_"][$iA]["type_"]]( $buffer, $matches["match_"], $option );
						} else {
							// Unexpected character
							throw new RuntimeException( "MhchemBugA: mhchem bug A. Please report. ("
								. $task->action_[$iA]->type_ . ")" );
						}

						// Add output
						MhchemUtil::concatArray( $output, $o );

						if ( $this->logger ) {
							$this->logger->debug( "\n State: " . $state );
							$this->logger->debug( "\n Buffer: " . json_encode( $buffer ) );
							$this->logger->debug( "\n Input: " . $input );
							$this->logger->debug( "\n Output: " . json_encode( $output ) );
							$this->logger->debug( "\n" );
						}

					}

					// Set next state,
					// Shorten input,
					// Continue with next character concatArray
					//   (= apply only one transition per position)
					$state = $task["nextState"] ?? $state;

					if ( $input != null && strlen( $input ) > 0 ) {
						if ( !array_key_exists( "revisit", $task ) ) {
							$input = $matches["remainder"];
						}
						if ( !array_key_exists( "toContinue", $task ) ) {
							// this breaks the two for loops
							break 1;
						}
					} else {
						return $output;
					}
				}
			}

			// Prevent infinite loop
			if ( $watchdog <= 0 ) {
				// Unexpected character
				throw new RunTimeException( "MhchemBugU: mhchem-PHP bug U. Please report." );
			}
		}
	}
}
