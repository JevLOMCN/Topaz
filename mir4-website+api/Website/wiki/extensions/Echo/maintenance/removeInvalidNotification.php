<?php
/**
 * Remove invalid events from echo_event and echo_notification
 *
 * @ingroup Maintenance
 */

use MediaWiki\Extension\Notifications\DbFactory;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * Maintenance script that removes invalid notifications
 *
 * @ingroup Maintenance
 */
class RemoveInvalidNotification extends Maintenance {

	/** @var string[] */
	protected $invalidEventType = [ 'article-linked' ];

	public function __construct() {
		parent::__construct();

		$this->addDescription( "Removes invalid notifications from the database." );
		$this->setBatchSize( 500 );
		$this->requireExtension( 'Echo' );
	}

	public function execute() {
		$lbFactory = DbFactory::newFromDefault();
		if ( !$this->invalidEventType ) {
			$this->output( "There is nothing to process\n" );

			return;
		}

		$dbw = $lbFactory->getEchoDb( DB_PRIMARY );
		$dbr = $lbFactory->getEchoDb( DB_REPLICA );

		$batchSize = $this->getBatchSize();
		$count = $batchSize;

		while ( $count == $batchSize ) {
			$res = $dbr->select(
				[ 'echo_event' ],
				[ 'event_id' ],
				[
					'event_type' => $this->invalidEventType,
				],
				__METHOD__,
				[ 'LIMIT' => $batchSize ]
			);

			$event = [];
			$count = 0;
			foreach ( $res as $row ) {
				// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
				if ( !in_array( $row->event_id, $event ) ) {
					$event[] = $row->event_id;
				}
				$count++;
			}

			if ( $event ) {
				$this->beginTransaction( $dbw, __METHOD__ );

				$dbw->delete(
					'echo_event',
					[ 'event_id' => $event ],
					__METHOD__
				);
				$dbw->delete(
					'echo_notification',
					[ 'notification_event' => $event ],
					__METHOD__
				);

				$this->commitTransaction( $dbw, __METHOD__ );

				$this->output( "processing " . count( $event ) . " invalid events\n" );
				$this->waitForReplication();
			}

			// Cleanup is not necessary for
			// 1. echo_email_batch, invalid notification is removed during the cron
		}
	}
}

$maintClass = RemoveInvalidNotification::class;
require_once RUN_MAINTENANCE_IF_MAIN;
