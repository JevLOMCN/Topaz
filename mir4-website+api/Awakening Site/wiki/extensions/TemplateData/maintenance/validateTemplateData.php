<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\Extension\TemplateData\TemplateDataBlob;
use MediaWiki\Title\Title;

/**
 * @license GPL-2.0-or-later
 */
class ValidateTemplateData extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Checks all TemplateData JSON pages are valid and outputs the name of invalid rows' );

		$this->setBatchSize( 500 );
		$this->requireExtension( 'TemplateData' );
	}

	public function execute() {
		$db = $this->getDB( DB_REPLICA );

		$lastId = 0;
		$rowsChecked = 0;
		$badRows = 0;
		$this->output( "Pages with invalid Template Data:\n" );
		do {
			$res = $db->newSelectQueryBuilder()
				->from( 'page_props' )
				->join( 'page', null, 'pp_page=page_id' )
				->fields( [ 'pp_page', 'pp_value', 'page_namespace', 'page_title' ] )
				->where( [
					$db->expr( 'pp_page', '>', $lastId ),
					'pp_propname' => 'templatedata'
				] )
				->orderBy( 'pp_page' )
				->limit( $this->getBatchSize() )
				->caller( __METHOD__ )
				->fetchResultSet();

			$count = $res->numRows();

			foreach ( $res as $row ) {
				$tdb = TemplateDataBlob::newFromDatabase( $db, $row->pp_value );
				$status = $tdb->getStatus();
				if ( !$status->isOK() ) {
					$this->output(
						Title::newFromRow( $row )->getPrefixedText() . "\n"
					);
					$badRows++;
				}
				$lastId = $row->pp_page;
				$rowsChecked++;
			}
		} while ( $count !== 0 );

		$this->output( "\nDone!\n" );
		$this->output( "Rows checked: {$rowsChecked}\n" );
		$this->output( "Bad rows: {$badRows}\n" );
	}

}

$maintClass = ValidateTemplateData::class;
require_once RUN_MAINTENANCE_IF_MAIN;
