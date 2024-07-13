<?php
/**
 * DiscussionTools tag hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use MediaWiki\Config\ConfigException;

class RegistrationHooks {
	public static function onRegistration(): void {
		// Use globals instead of Config. Accessing it so early blows up unrelated extensions (T255704).
		global $wgLocaltimezone, $wgFragmentMode;
		// HACK: Do not run these tests on CI as the globals are not configured.
		if ( getenv( 'ZUUL_PROJECT' ) ) {
			return;
		}
		// If $wgLocaltimezone isn't hard-coded, it is evaluated from the system
		// timezone. On some systems this isn't guaranteed to be static, for example
		// on Debian, GMT can get converted to UTC, instead of Europe/London.
		// Timestamp parsing assumes that the timezone never changes.
		if ( !$wgLocaltimezone ) {
			throw new ConfigException( 'DiscussionTools requires $wgLocaltimezone to be set' );
		}
		// If $wgFragmentMode is set to use 'legacy' encoding, determining the IDs of our thread
		// headings is harder, especially since the implementation is different in Parsoid.
		if ( !isset( $wgFragmentMode[0] ) || $wgFragmentMode[0] !== 'html5' ) {
			throw new ConfigException( 'DiscussionTools requires $wgFragmentMode to be set to ' .
				"[ 'html5', 'legacy' ] or [ 'html5' ]" );
		}
	}
}
