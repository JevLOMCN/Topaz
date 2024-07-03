<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentity;
use MediaWiki\Utils\MWTimestamp;
use Wikimedia\Rdbms\IDatabase;

/**
 * Manages what wikis a user has unread notifications on
 */
class UnreadWikis {

	private const DEFAULT_TS = '00000000000000';
	private const DEFAULT_TS_DB = '00010101010101';

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var DbFactory
	 */
	private $dbFactory;

	/**
	 * @param int $id Central user id
	 */
	public function __construct( $id ) {
		$this->id = $id;
		$this->dbFactory = DbFactory::newFromDefault();
	}

	/**
	 * Use the user id provided by the CentralIdLookup
	 *
	 * @param UserIdentity $user
	 * @return UnreadWikis|false
	 */
	public static function newFromUser( UserIdentity $user ) {
		$id = MediaWikiServices::getInstance()
			->getCentralIdLookup()
			->centralIdFromLocalUser( $user, CentralIdLookup::AUDIENCE_RAW );
		if ( !$id ) {
			return false;
		}

		return new self( $id );
	}

	/**
	 * @param int $index DB_* constant
	 *
	 * @return bool|IDatabase
	 */
	private function getDB( $index ) {
		return $this->dbFactory->getSharedDb( $index );
	}

	/**
	 * @return array[][]
	 */
	public function getUnreadCounts() {
		$dbr = $this->getDB( DB_REPLICA );
		if ( $dbr === false ) {
			return [];
		}

		$rows = $dbr->select(
			'echo_unread_wikis',
			[
				'euw_wiki',
				'euw_alerts', 'euw_alerts_ts',
				'euw_messages', 'euw_messages_ts',
			],
			[ 'euw_user' => $this->id ],
			__METHOD__
		);

		$wikis = [];
		foreach ( $rows as $row ) {
			if ( !$row->euw_alerts && !$row->euw_messages ) {
				// This shouldn't happen, but lets be safe...
				continue;
			}
			$wikis[$row->euw_wiki] = [
				AttributeManager::ALERT => [
					'count' => $row->euw_alerts,
					'ts' => wfTimestamp( TS_MW, $row->euw_alerts_ts ) === static::DEFAULT_TS_DB ?
						static::DEFAULT_TS : wfTimestamp( TS_MW, $row->euw_alerts_ts ),
				],
				AttributeManager::MESSAGE => [
					'count' => $row->euw_messages,
					'ts' => wfTimestamp( TS_MW, $row->euw_messages_ts ) === static::DEFAULT_TS_DB ?
						static::DEFAULT_TS : wfTimestamp( TS_MW, $row->euw_messages_ts ),
				],
			];
		}

		return $wikis;
	}

	/**
	 * @param string $wiki Wiki code
	 * @param int $alertCount Number of alerts
	 * @param MWTimestamp|bool $alertTime Timestamp of most recent unread alert, or
	 *   false meaning no timestamp because there are no unread alerts.
	 * @param int $msgCount Number of messages
	 * @param MWTimestamp|bool $msgTime Timestamp of most recent message, or
	 *   false meaning no timestamp because there are no unread messages.
	 */
	public function updateCount( $wiki, $alertCount, $alertTime, $msgCount, $msgTime ) {
		$dbw = $this->getDB( DB_PRIMARY );
		if ( $dbw === false || $dbw->isReadOnly() ) {
			return;
		}

		$conditions = [
			'euw_user' => $this->id,
			'euw_wiki' => $wiki,
		];

		if ( $alertCount || $msgCount ) {
			$values = [
				'euw_alerts' => $alertCount,
				'euw_alerts_ts' => $dbw->timestamp(
					$alertTime
						? $alertTime->getTimestamp( TS_MW )
						: static::DEFAULT_TS_DB
				),
				'euw_messages' => $msgCount,
				'euw_messages_ts' => $dbw->timestamp(
					$msgTime
						? $msgTime->getTimestamp( TS_MW )
						: static::DEFAULT_TS_DB
				),
			];

			// when there is unread alert(s) and/or message(s), upsert the row
			$dbw->newInsertQueryBuilder()
				->insertInto( 'echo_unread_wikis' )
				->row( $conditions + $values )
				->onDuplicateKeyUpdate()
				->uniqueIndexFields( [ 'euw_user', 'euw_wiki' ] )
				->set( $values )
				->caller( __METHOD__ )
				->execute();
		} else {
			// No unread notifications, delete the row
			$dbw->delete(
				'echo_unread_wikis',
				$conditions,
				__METHOD__
			);
		}
	}
}
