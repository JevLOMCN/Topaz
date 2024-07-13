<?php
/**
 * Utilities for ResourceLoader modules used by EditCheck.
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\VisualEditor\EditCheck;

use MessageLocalizer;

class ResourceLoaderData {
	protected static array $defaults = [
		'addReference' => [
			'minimumCharacters' => 50,
			'beforePunctuation' => false,
			// TODO: when we have multiple edit checks this will likely be a generic block:
			// account: loggedin, loggedout, anything non-truthy means allow either
			'account' => false,
			'maximumEditcount' => 100,
			'ignoreSections' => [],
			'ignoreLeadSection' => false,
		],
	];

	/**
	 * Return configuration data for edit checks, fetched from an on-wiki JSON message
	 *
	 * @param MessageLocalizer $context
	 * @return array Configuration data for edit checks
	 */
	public static function getConfig( MessageLocalizer $context ) {
		$raw_config = json_decode( $context->msg( 'editcheck-config.json' )->inContentLanguage()->plain(), true );

		return array_replace_recursive( self::$defaults, $raw_config ?? [] );
	}
}
