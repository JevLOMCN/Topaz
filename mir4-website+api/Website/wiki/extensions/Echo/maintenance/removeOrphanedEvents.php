<?php
/**
 * Remove rows from echo_event that don't have corresponding rows in echo_notification or echo_email_batch.
 *
 * @ingroup Maintenance
 */

use MediaWiki\Extension\Notifications\DbFactory;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * Maintenance script that removes orphaned event rows
 *
 * @ingroup Maintenance
 */
class RemoveOrphanedEvents extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( "Remove rows from echo_event and echo_target_page that don't have corresponding " .
			"rows in echo_notification or echo_email_batch" );

		$this->setBatchSize( 500 );

		$this->requireExtension( 'Echo' );
	}

	public function getUpdateKey() {
		return __CLASS__;
	}

	public function doDBUpdates() {
		$startId = 0;
		$dbFactory = DbFactory::newFromDefault();
		$dbr = $dbFactory->getEchoDb( DB_REPLICA );
		$maxId = (int)$dbr->newSelectQueryBuilder()
			->select( 'MAX(event_id)' )
			->from( 'echo_event' )
			->fetchField();
		$eventsProcessedTotal = 0;
		$targetsProcessedTotal = 0;
		while ( $startId < $maxId ) {
			$startId += $this->getBatchSize() * 1000;
			[ $eventsProcessed, $targetsProcessed ] = $this->doMajorBatch( $startId );
			$eventsProcessedTotal += $eventsProcessed;
			$targetsProcessedTotal += $targetsProcessed;
		}
		$this->output( "In total, deleted $eventsProcessedTotal orphaned events and " .
			"$targetsProcessedTotal target_page rows.\n" );

		return true;
	}

	private function doMajorBatch( $maxId ) {
		$dbFactory = DbFactory::newFromDefault();
		$dbw = $dbFactory->getEchoDb( DB_PRIMARY );
		$dbr = $dbFactory->getEchoDb( DB_REPLICA );
		$iterator = new BatchRowIterator(
			$dbr,
			[ 'echo_event', 'echo_notification', 'echo_email_batch' ],
			'event_id',
			$this->getBatchSize()
		);
		$iterator->addJoinConditions( [
			'echo_notification' => [ 'LEFT JOIN', 'notification_event=event_id' ],
			'echo_email_batch' => [ 'LEFT JOIN', 'eeb_event_id=event_id' ],
		] );
		$iterator->addConditions( [
			'notification_user' => null,
			'eeb_user_id' => null,
			'event_id < ' . $maxId
		] );
		$iterator->setCaller( __METHOD__ );

		$this->output( "Removing orphaned echo_event rows with max event_id of $maxId...\n" );

		$eventsProcessed = 0;
		$targetsProcessed = 0;
		foreach ( $iterator as $batch ) {
			$ids = [];
			foreach ( $batch as $row ) {
				$ids[] = $row->event_id;
			}
			$dbw->delete( 'echo_event', [ 'event_id' => $ids ], __METHOD__ );
			$eventsProcessed += $dbw->affectedRows();
			$dbw->delete( 'echo_target_page', [ 'etp_event' => $ids ], __METHOD__ );
			$targetsProcessed += $dbw->affectedRows();
			$this->output( "Deleted $eventsProcessed orphaned events and $targetsProcessed target_page rows.\n" );
			$this->waitForReplication();
		}

		$this->output( "Removing any remaining orphaned echo_target_page rows with max etp_event of $maxId...\n" );
		$iterator = new BatchRowIterator(
			$dbr,
			[ 'echo_target_page', 'echo_event' ],
			'etp_event',
			$this->getBatchSize()
		);
		$iterator->addJoinConditions( [ 'echo_event' => [ 'LEFT JOIN', 'event_id=etp_event' ] ] );
		$iterator->addConditions(
			[
				'event_type' => null,
				'etp_event < ' . $maxId
			]
		);
		$iterator->addOptions( [ 'DISTINCT' ] );
		$iterator->setCaller( __METHOD__ );

		$processed = 0;
		foreach ( $iterator as $batch ) {
			$ids = [];
			foreach ( $batch as $row ) {
				$ids[] = $row->etp_event;
			}
			$dbw->delete( 'echo_target_page', [ 'etp_event' => $ids ], __METHOD__ );
			$processed += $dbw->affectedRows();
			$this->output( "Deleted $processed orphaned target_page rows.\n" );
			$this->waitForReplication();
		}

		return [ $eventsProcessed, $targetsProcessed + $processed ];
	}
}

$maintClass = RemoveOrphanedEvents::class;
require_once RUN_MAINTENANCE_IF_MAIN;
