<?php

/**
 * Maintenance script that migrates the linter_params field value to the new tag and template fields
 * note: This should be run once the tag and template write functionality in linter
 *   recordLintJob has been enabled by setting LinterWriteTagAndTemplateColumnsStage true.
 */

use MediaWiki\Linter\Database;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class MigrateTagTemplate extends LoggedUpdateMaintenance {

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'Linter' );
		$this->addDescription(
			'Copy the tag and template data from the params field in the linter table'
		);
		$this->addOption(
			'sleep',
			'Sleep time (in seconds) between every batch. Default: 1 seconds',
			false,
			true
		);
		$this->setBatchSize( 1000 );
	}

	/**
	 * The Linter migrate linter_params to linter_tag and linter_template script can take a day to run on --wiki enwiki
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		$config = $this->getConfig();
		$enableMigrateTagAndTemplateStage = $config->get( 'LinterWriteTagAndTemplateColumnsStage' );
		if ( !$enableMigrateTagAndTemplateStage ) {
			$this->output( "LinterWriteTagAndTemplateColumnsStage config value is false, code disabled\n" );
			return false;
		}

		$this->output( "Running linter migrate linter_params to tag and template function, this may take a while\n" );

		$batchSize = $this->getBatchSize();
		$sleep = (int)$this->getOption( 'sleep', 1 );

		$dbw = self::getDB( DB_PRIMARY );
		if ( !$dbw->fieldExists( 'linter', 'linter_template', __METHOD__ ) ) {
			$this->output( "Run update.php to add linter_tag and linter_template fields to the linter table.\n" );
			return false;
		}

		$this->output( "Migrating the linter_params field to the linter_tag and linter_template fields...\n" );

		$updated = Database::migrateTemplateAndTagInfo( $batchSize, $sleep, false );

		$this->output(
			"Completed migration of linter_params data in the linter table, $updated rows updated.\n"
		);

		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'migrate linter table linter_params data to the linter_tag and linter_template fields';
	}
}

$maintClass = MigrateTagTemplate::class;
require_once RUN_MAINTENANCE_IF_MAIN;
