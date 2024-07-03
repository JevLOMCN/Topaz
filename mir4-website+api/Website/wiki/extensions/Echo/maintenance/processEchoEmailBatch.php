<?php

use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\EmailBatch;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * A maintenance script that processes email digest
 */
class ProcessEchoEmailBatch extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Process email digest" );

		$this->addOption(
			"ignoreConfiguredSchedule",
			"Send all pending notifications immediately even if configured to be weekly or daily.",
			false, false, "i" );

		$this->setBatchSize( 300 );
		$this->requireExtension( 'Echo' );
	}

	public function execute() {
		$lbFactory = DbFactory::newFromDefault();
		$ignoreConfiguredSchedule = $this->getOption( "ignoreConfiguredSchedule", 0 );

		$this->output( "Started processing... \n" );

		$startUserId = 0;
		$batchSize = $this->getBatchSize();
		$count = $batchSize;

		while ( $count === $batchSize ) {
			$count = 0;

			$res = EmailBatch::getUsersToNotify( $startUserId, $batchSize );

			$updated = false;
			foreach ( $res as $row ) {
				$userId = intval( $row->eeb_user_id );
				if ( $userId && $userId > $startUserId ) {
					$emailBatch = EmailBatch::newFromUserId( $userId, !$ignoreConfiguredSchedule );
					if ( $emailBatch ) {
						$this->output( "processing user_Id " . $userId . " \n" );
						$emailBatch->process();
					}
					$startUserId = $userId;
					$updated = true;
				}
				$count++;
			}
			$this->waitForReplication();

			// double check to make sure that the id is updated
			if ( !$updated ) {
				break;
			}
		}

		$this->output( "Completed \n" );
	}
}

$maintClass = ProcessEchoEmailBatch::class;
require_once RUN_MAINTENANCE_IF_MAIN;
