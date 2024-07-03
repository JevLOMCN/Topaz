<?php

namespace Wikimedia\WikiPEG;

use InvalidArgumentException;

class DefaultTracer implements Tracer {
	private $indentLevel = 0;

	public function trace( $event ) {
		switch ( $event['type'] ) {
		case 'rule.enter':
			$this->log( $event );
			$this->indentLevel++;
			break;

		case 'rule.match':
			$this->indentLevel--;
			$this->log( $event );
			break;

		case 'rule.fail':
			$this->indentLevel--;
			$this->log( $event );
			break;

		default:
			throw new InvalidArgumentException( "Invalid event type {$event['type']}" );
		}
	}

	private function log( $event ) {
		print str_pad(
			'' . $event['location'],
			20
		)
			. str_pad( $event['type'], 10 ) . ' '
			. str_repeat( ' ', $this->indentLevel ) . $event['rule']
			. $this->formatArgs( $event['args'] ?? null )
			. "\n";
	}

	private function formatArgs( $argMap ) {
		if ( !$argMap ) {
			return '';
		}

		$argParts = [];
		foreach ( $argMap as $argName => $argValue ) {
			if ( $argName === '$silence' ) {
				continue;
			}
			if ( $argName === '$boolParams' ) {
				$argParts[] = '0x' . base_convert( $argValue, 10, 16 );
			} else {
				$displayName = str_replace( '$param_', '', $argName );
				if ( $displayName[0] === '&' ) {
					$displayName = substr( $displayName, 1 );
					$ref = '&';
				} else {
					$ref = '';
				}
				$argParts[] = "$displayName=$ref" .
						   json_encode( $argValue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}
		if ( $argParts ) {
			return '<' . implode( ', ', $argParts ) . '>';
		} else {
			return '';
		}
	}
}
