<?php

namespace MediaWiki\Extension\AbuseFilter\Maintenance;

use Maintenance;

// @codeCoverageIgnoreStart
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";
// @codeCoverageIgnoreEnd

class SearchFilters extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Find all filters matching a regular expression pattern' );
		$this->addOption( 'pattern', 'Regular expression pattern', true, true );

		$this->requireExtension( 'Abuse Filter' );
	}

	/**
	 * @see Maintenance:execute()
	 */
	public function execute() {
		global $wgConf, $wgDBtype;

		if ( $wgDBtype !== 'mysql' ) {
			// Code using exit() cannot be tested (T272241)
			// @codeCoverageIgnoreStart
			$this->fatalError( 'This maintenance script only works with MySQL databases' );
			// @codeCoverageIgnoreEnd
		}

		$this->output( "wiki\tfilter\n" );
		if ( $this->getOption( 'pattern' ) === '' ) {
			// Code using exit() cannot be tested (T272241)
			// @codeCoverageIgnoreStart
			$this->fatalError( 'Pattern cannot be empty' );
			// @codeCoverageIgnoreEnd
		}

		if ( count( $wgConf->wikis ) > 0 ) {
			foreach ( $wgConf->wikis as $dbname ) {
				$this->getMatchingFilters( $dbname );
			}
		} else {
			$this->getMatchingFilters();
		}
	}

	/**
	 * @param string|false $dbname Name of database, or false if the wiki is not part of a wikifarm
	 */
	private function getMatchingFilters( $dbname = false ) {
		$dbr = $this->getDB( DB_REPLICA, [], $dbname );
		$pattern = $dbr->addQuotes( $this->getOption( 'pattern' ) );

		if ( $dbr->tableExists( 'abuse_filter' ) ) {
			$rows = $dbr->select(
				'abuse_filter',
				[ 'dbname' => 'DATABASE()', 'af_id' ],
				[
					"af_pattern RLIKE $pattern"
				]
			);

			foreach ( $rows as $row ) {
				$this->output( $row->dbname . "\t" . $row->af_id . "\n" );
			}
		}
	}
}

$maintClass = SearchFilters::class;
require_once RUN_MAINTENANCE_IF_MAIN;
