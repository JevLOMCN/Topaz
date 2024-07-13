<?php

namespace LoginNotify\Maintenance;

use LoginNotify\Hooks\HookRunner;
use Maintenance;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\User\UserFactory;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

/**
 * A maintenance script to programatically generate successful or failed login attempts for any
 * user, from any given IP address and with any given user-agent string. This script makes testing
 * LoginNotify and its interaction with other extensions (such as CheckUser) much easier for the
 * developers.
 */
class LoginAttempt extends Maintenance {
	/**
	 * Constructor
	 *
	 * Retrieves the arguments from the command line
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Registers a login attempt for a given user' );
		$this->addArg( 'user', 'Target user', true );
		$this->addArg( 'success', 'Whether login attempt was successful (true/false)', false );
		$this->addArg( 'ip', 'IP address of the login attempt', false );
		$this->addArg( 'ua', 'User-agent string of the login attempt', false );
		$this->addArg( 'repetitions', 'How many times the attempt should be made', false );

		$this->requireExtension( 'LoginNotify' );
	}

	/**
	 * Main function
	 *
	 * Registers a failed or successful login attempt for a given user
	 */
	public function execute() {
		global $wgRequest;

		$username = $this->getArg( 0 );
		$success = $this->getArg( 1, false ) === 'true';
		$ip = $this->getArg( 2, '127.0.0.1' );
		$ua = $this->getArg( 3, 'Login attempt by LoginNotify maintenance script' );
		$reps = intval( $this->getArg( 4, 1 ) );

		$wgRequest = new FauxRequest();
		$wgRequest->setIP( $ip );
		$wgRequest->setHeader( 'User-Agent', $ua );

		$user = $this->getServiceContainer()->getUserFactory()
			->newFromName( $username, UserFactory::RIGOR_USABLE );
		if ( !$user || !$user->isRegistered() ) {
			$this->output( "User {$username} does not exist!\n" );
			return;
		}

		$hookRunner = new HookRunner( MediaWikiServices::getInstance()->getHookContainer() );
		for ( $i = 0; $i < $reps; $i++ ) {
			if ( $success ) {
				$res = AuthenticationResponse::newPass( $username );
				$hookRunner->onAuthManagerLoginAuthenticateAudit( $res, $user, $username, [] );
				$this->output( "A successful login attempt was registered!\n" );
			} else {
				$res = AuthenticationResponse::newFail( new RawMessage( 'Well, it failed' ) );
				$hookRunner->onAuthManagerLoginAuthenticateAudit( $res, null, $username, [] );
				$this->output( "A failed login attempt was registered!\n" );
			}
		}
	}
}

$maintClass = LoginAttempt::class;
require_once RUN_MAINTENANCE_IF_MAIN;
