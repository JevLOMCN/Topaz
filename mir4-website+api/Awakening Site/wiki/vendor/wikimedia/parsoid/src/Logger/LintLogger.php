<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Logger;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Utils\Timing;
use Wikimedia\Parsoid\Utils\TokenUtils;

/**
 * Logger backend for linter.
 * This backend filters out logging messages with Logtype "lint/*" and
 * logs them (console, external service).
 */
class LintLogger {

	/** @var Env */
	private $env;

	public function __construct( Env $env ) {
		$this->env = $env;
	}

	/**
	 * Convert DSR offsets in collected lints
	 *
	 * Linter offsets should always be ucs2 if the lint viewer is client-side JavaScript.
	 * But, added conversion args in case callers wants some other conversion for other
	 * use cases.
	 *
	 * @param Env $env
	 * @param array &$lints
	 * @param string $from
	 * @param string $to
	 */
	public static function convertDSROffsets(
		Env $env, array &$lints, string $from = 'byte', string $to = 'ucs2'
	): void {
		$metrics = $env->getSiteConfig()->metrics();
		$timer = null;
		if ( $metrics ) {
			$timer = Timing::start( $metrics );
		}

		// Accumulate offsets + convert widths to pseudo-offsets
		$offsets = [];
		foreach ( $lints as &$lint ) {
			$dsr = &$lint['dsr'];
			$offsets[] = &$dsr[0];
			$offsets[] = &$dsr[1];

			// dsr[2] is a width. Convert it to an offset pointer.
			if ( ( $dsr[2] ?? 0 ) > 1 ) { // widths 0,1,null are fine
				$dsr[2] = $dsr[0] + $dsr[2];
				$offsets[] = &$dsr[2];
			}

			// dsr[3] is a width. Convert it to an offset pointer.
			if ( ( $dsr[3] ?? 0 ) > 1 ) { // widths 0,1,null are fine
				$dsr[3] = $dsr[1] - $dsr[3];
				$offsets[] = &$dsr[3];
			}
		}

		TokenUtils::convertOffsets( $env->topFrame->getSrcText(), $from, $to, $offsets );

		// Undo the conversions of dsr[2], dsr[3]
		foreach ( $lints as &$lint ) {
			$dsr = &$lint['dsr'];
			if ( ( $dsr[2] ?? 0 ) > 1 ) { // widths 0,1,null are fine
				$dsr[2] -= $dsr[0];
			}
			if ( ( $dsr[3] ?? 0 ) > 1 ) { // widths 0,1,null are fine
				$dsr[3] = $dsr[1] - $dsr[3];
			}
		}

		if ( $metrics ) {
			$timer->end( "lint.offsetconversion" );
		}
	}

	/**
	 *
	 */
	public function logLintOutput() {
		$env = $this->env;

		// We only want to send to the MW API if this was a request to parse
		// the full page.
		if ( !$env->logLinterData ) {
			return;
		}

		// Filter only enabled linting types
		$enabledBuffer = array_filter( $env->getLints(), static function ( $item ) use ( $env ) {
			return $env->getSiteConfig()->linting( $item['type'] );
		} );

		// Convert offsets to ucs2
		$offsetType = $env->getCurrentOffsetType();
		if ( $offsetType !== 'ucs2' ) {
			self::convertDSROffsets( $env, $enabledBuffer, $offsetType, 'ucs2' );
		}

		$env->getDataAccess()->logLinterData( $env->getPageConfig(), $enabledBuffer );
	}

}
