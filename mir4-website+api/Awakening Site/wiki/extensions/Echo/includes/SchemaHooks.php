<?php

namespace MediaWiki\Extension\Notifications;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class SchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		global $wgEchoCluster;
		if ( $wgEchoCluster ) {
			// DatabaseUpdater does not support other databases, so skip
			return;
		}

		$db = $updater->getDB();
		$dbType = $db->getType();

		$dir = dirname( __DIR__ ) . '/sql';

		$updater->addExtensionTable( 'echo_event', "$dir/$dbType/tables-generated.sql" );

		// 1.35
		$updater->addExtensionTable( 'echo_push_provider', "$dir/echo_push_provider.sql" );
		$updater->addExtensionTable( 'echo_push_subscription', "$dir/echo_push_subscription.sql" );

		// 1.36
		$updater->addExtensionTable( 'echo_push_topic', "$dir/echo_push_topic.sql" );

		// 1.39
		if ( $dbType === 'mysql' && $db->tableExists( 'echo_push_subscription', __METHOD__ ) ) {
			// Split into single steps to support updates from some releases as well - T322143
			$updater->renameExtensionIndex(
				'echo_push_subscription',
				'echo_push_subscription_user_id',
				'eps_user',
				"$dir/$dbType/patch-echo_push_subscription-rename-index-eps_user.sql",
				false
			);
			$updater->dropExtensionIndex(
				'echo_push_subscription',
				'echo_push_subscription_token',
				"$dir/$dbType/patch-echo_push_subscription-drop-index-eps_token.sql"
			);
			$updater->addExtensionIndex(
				'echo_push_subscription',
				'eps_token',
				"$dir/$dbType/patch-echo_push_subscription-create-index-eps_token.sql"
			);
			$updater->addExtensionField(
				'echo_push_subscription',
				'eps_topic',
				"$dir/$dbType/patch-echo_push_subscription-add-column-eps_topic.sql"
			);

			$res = $db->query( 'SHOW CREATE TABLE ' . $db->tableName( 'echo_push_subscription' ), __METHOD__ );
			$row = $res ? $res->fetchRow() : false;
			$statement = $row ? $row[1] : '';
			if ( str_contains( $statement, $db->addIdentifierQuotes( 'echo_push_subscription_ibfk_1' ) ) ) {
				$updater->modifyExtensionTable(
					'echo_push_subscription',
					"$dir/$dbType/patch-echo_push_subscription-drop-foreign-keys_1.sql"
				);
			}
			if ( str_contains( $statement, $db->addIdentifierQuotes( 'echo_push_subscription_ibfk_2' ) ) ) {
				$updater->modifyExtensionTable(
					'echo_push_subscription',
					"$dir/$dbType/patch-echo_push_subscription-drop-foreign-keys_2.sql"
				);
			}
		}
		if ( $dbType === 'sqlite' ) {
			$updater->addExtensionIndex( 'echo_push_subscription', 'eps_user',
				"$dir/$dbType/patch-cleanup-push_subscription-foreign-keys-indexes.sql" );
		}

		global $wgEchoSharedTrackingCluster, $wgEchoSharedTrackingDB;
		// Following tables should only be created if both cluster and database are false.
		// Otherwise, they are not created in the place they are accesses, because
		// DatabaseUpdater does not support other databases other than the main wiki schema.
		if ( $wgEchoSharedTrackingCluster === false && $wgEchoSharedTrackingDB === false ) {
			$updater->addExtensionTable( 'echo_unread_wikis', "$dir/$dbType/tables-sharedtracking-generated.sql" );
		}
	}

}
