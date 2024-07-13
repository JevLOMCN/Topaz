<?php

// Keep in sync with same script in Flow.

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * Generates Echo autoload info
 */

class GenerateEchoAutoload extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Generates Echo autoload data' );
	}

	public function execute() {
		$base = dirname( __DIR__ );
		$generator = new AutoloadGenerator( $base );
		$dirs = [
			'includes',
			'tests',
			'maintenance',
		];
		foreach ( $dirs as $dir ) {
			$generator->readDir( $base . '/' . $dir );
		}
		foreach ( glob( $base . '/*.php' ) as $file ) {
			$generator->readFile( $file );
		}

		$target = $generator->getTargetFileInfo();

		file_put_contents(
			$target['filename'],
			$generator->getAutoload( basename( __DIR__ ) . '/' . basename( __FILE__ ) )
		);

		echo "Done.\n\n";
	}
}

$maintClass = GenerateEchoAutoload::class;
require_once RUN_MAINTENANCE_IF_MAIN;
