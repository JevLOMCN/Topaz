<?php

use MediaWiki\Extension\OATHAuth\OATHAuthServices;
use MediaWiki\MediaWikiServices;
use MediaWiki\Session\SessionManager;

if ( getenv( 'MW_INSTALL_PATH' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class DisableOATHAuthForUser extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Remove all two-factor authentication devices from a specific user' );
		$this->addArg( 'user', 'The username to remove 2FA devices from.' );
		$this->requireExtension( 'OATHAuth' );
	}

	public function execute() {
		$username = $this->getArg( 0 );

		$user = MediaWikiServices::getInstance()->getUserFactory()
			->newFromName( $username );
		if ( $user === null || $user->getId() === 0 ) {
			$this->fatalError( "User $username doesn't exist!" );
		}

		$repo = OATHAuthServices::getInstance()->getUserRepository();
		$oathUser = $repo->findByUser( $user );
		if ( !$oathUser->isTwoFactorAuthEnabled() ) {
			$this->fatalError( "User $username does not have two-factor authentication enabled!" );
		}

		$repo->removeAll( $oathUser, 'Maintenance script', false );
		// Kill all existing sessions.
		// If this request to disable 2FA was social-engineered by an attacker,
		// the legitimate user will hopefully log in again to the wiki, and notice that the second factor
		// is missing or different, and alert the operators.
		SessionManager::singleton()->invalidateSessionsForUser( $user );

		$this->output( "Two-factor authentication disabled for $username.\n" );
	}
}

$maintClass = DisableOATHAuthForUser::class;
require_once RUN_MAINTENANCE_IF_MAIN;
