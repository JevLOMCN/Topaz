<?php

namespace MediaWiki\Extension\AbuseFilter\Maintenance;

// @codeCoverageIgnoreStart
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";
// @codeCoverageIgnoreEnd

use LoggedUpdateMaintenance;
use ManualLogEntry;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseFilter;
use MediaWiki\User\User;

/**
 * @codeCoverageIgnore
 * No need to test old single-use script.
 */
class AddMissingLoggingEntries extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Add missing logging entries for abusefilter-modify T54919' );
		$this->addOption( 'dry-run', 'Perform a dry run' );
		$this->addOption( 'verbose', 'Print a list of affected afh_id' );
		$this->requireExtension( 'Abuse Filter' );
	}

	/**
	 * @inheritDoc
	 */
	public function getUpdateKey() {
		return 'AddMissingLoggingEntries';
	}

	/**
	 * @inheritDoc
	 */
	public function doDBUpdates() {
		$dryRun = $this->hasOption( 'dry-run' );
		$logParams = [];
		$afhRows = [];
		$db = $this->getDB( DB_REPLICA, 'vslow' );

		$logParamsConcat = $db->buildConcat( [ 'afh_id', $db->addQuotes( "\n" ) ] );
		$legacyParamsLike = $db->buildLike( $logParamsConcat, $db->anyString() );
		// Non-legacy entries are a serialized array with 'newId' and 'historyId' keys
		$newLogParamsLike = $db->buildLike( $db->anyString(), 'historyId', $db->anyString() );
		$actorQuery = AbuseFilterServices::getActorMigration()->getJoin( 'afh_user' );
		// Find all entries in abuse_filter_history without logging entry of same timestamp
		$afhResult = $db->select(
			[ 'abuse_filter_history', 'logging' ] + $actorQuery['tables'],
			[ 'afh_id', 'afh_filter', 'afh_timestamp', 'afh_deleted' ] + $actorQuery['fields'],
			[
				'log_id IS NULL',
				"NOT log_params $newLogParamsLike"
			],
			__METHOD__,
			[],
			[ 'logging' => [
				'LEFT JOIN',
				"afh_timestamp = log_timestamp AND log_params $legacyParamsLike AND log_type = 'abusefilter'"
			] ] + $actorQuery['joins']
		);

		// Because the timestamp matches aren't exact (sometimes a couple of
		// seconds off), we need to check all our results and ignore those that
		// do actually have log entries
		foreach ( $afhResult as $row ) {
			$logParams[] = $row->afh_id . "\n" . $row->afh_filter;
			$afhRows[$row->afh_id] = $row;
		}

		if ( !count( $afhRows ) ) {
			$this->output( "Nothing to do.\n" );
			return !$dryRun;
		}

		$logResult = $this->getDB( DB_REPLICA )->selectFieldValues(
			'logging',
			'log_params',
			[ 'log_type' => 'abusefilter', 'log_params' => $logParams ],
			__METHOD__
		);

		foreach ( $logResult as $params ) {
			// id . "\n" . filter
			$afhId = explode( "\n", $params, 2 )[0];
			// Forget this row had any issues - it just has a different timestamp in the log
			unset( $afhRows[$afhId] );
		}

		if ( !count( $afhRows ) ) {
			$this->output( "Nothing to do.\n" );
			return !$dryRun;
		}

		if ( $dryRun ) {
			$msg = count( $afhRows ) . " rows to insert.";
			if ( $this->hasOption( 'verbose' ) ) {
				$msg .= " Affected IDs (afh_id):\n" . implode( ', ', array_keys( $afhRows ) );
			}
			$this->output( "$msg\n" );
			return false;
		}

		$dbw = $this->getDB( DB_PRIMARY );

		$count = 0;
		foreach ( $afhRows as $row ) {
			if ( $count % 100 === 0 ) {
				$this->waitForReplication();
			}
			$user = User::newFromAnyId(
				$row->afh_user ?? null,
				$row->afh_user_text ?? null,
				$row->afh_actor ?? null
			);

			if ( $user === null ) {
				// This isn't supposed to happen.
				continue;
			}

			// This copies the code in FilterStore
			$logEntry = new ManualLogEntry( 'abusefilter', 'modify' );
			$logEntry->setPerformer( $user );
			$logEntry->setTarget( SpecialAbuseFilter::getTitleForSubpage( $row->afh_filter ) );
			// Use the new format!
			$logEntry->setParameters( [
				'historyId' => $row->afh_id,
				'newId' => $row->afh_filter
			] );
			$logEntry->setTimestamp( $row->afh_timestamp );
			$logEntry->insert( $dbw );

			$count++;
		}

		$this->output( "Inserted $count rows.\n" );
		return true;
	}
}

$maintClass = AddMissingLoggingEntries::class;
require_once RUN_MAINTENANCE_IF_MAIN;
