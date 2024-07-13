<?php
/**
 * DiscussionTools installer hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class InstallerHooks implements
	LoadExtensionSchemaUpdatesHook
{
	/**
	 * Implements the LoadExtensionSchemaUpdates hook, to create database tables when
	 * update.php runs
	 *
	 * @param DatabaseUpdater $updater
	 * @return bool|void
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$base = dirname( __DIR__ );
		$type = $updater->getDB()->getType();

		$updater->addExtensionTable(
			'discussiontools_subscription',
			"$base/../sql/$type/discussiontools_subscription.sql"
		);
		$updater->addExtensionTable(
			'discussiontools_items',
			"$base/../sql/$type/discussiontools_persistent.sql"
		);
	}
}
