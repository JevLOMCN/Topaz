<?php

namespace LoginNotify\Maintenance;

use LoginNotify\LoginNotify;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class PurgeSeen extends \Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Purge expired user IP address information stored by LoginNotify' );
	}

	public function execute() {
		$loginNotify = LoginNotify::getInstance();
		$minId = $loginNotify->getMinExpiredId();
		for ( ; $minId !== null; $this->waitForReplication() ) {
			$minId = $loginNotify->purgeSeen( $minId );
		}
	}
}

$maintClass = PurgeSeen::class;
require_once RUN_MAINTENANCE_IF_MAIN;
