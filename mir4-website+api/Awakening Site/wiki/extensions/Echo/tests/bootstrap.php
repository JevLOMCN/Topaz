<?php
/**
 * Find the correct path to /tests/phpunit/bootstrap.php in core
 *
 * Takes MW_INSTALL_PATH environment variable into account. This is used by the
 * test suite defined in mfe.suite.xml for MobileFrontend phpunit testing.
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	if ( realpath( '../..' ) ) {
		$IP = realpath( '../..' );
	} else {
		$IP = dirname( dirname( dirname( __DIR__ ) ) );
	}
}

require_once $IP . "/tests/phpunit/bootstrap.php";
