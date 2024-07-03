<?php

namespace MediaWiki\Extension\TitleBlacklist;

use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthManager;
use MediaWiki\User\User;
use RequestContext;
use StatusValue;

class TitleBlacklistPreAuthenticationProvider extends AbstractPreAuthenticationProvider {

	/** @var bool */
	protected $blockAutoAccountCreation;

	public function __construct( $params = [] ) {
		global $wgTitleBlacklistBlockAutoAccountCreation;

		$params += [
			'blockAutoAccountCreation' => $wgTitleBlacklistBlockAutoAccountCreation
		];

		$this->blockAutoAccountCreation = (bool)$params['blockAutoAccountCreation'];
	}

	public function getAuthenticationRequests( $action, array $options ) {
		$needOverrideOption = false;
		switch ( $action ) {
			case AuthManager::ACTION_CREATE:
				$user = User::newFromName( $options['username'] ) ?: new User();
				$needOverrideOption = TitleBlacklist::userCanOverride( $user, 'new-account' );
				break;
		}

		return $needOverrideOption ? [ new TitleBlacklistAuthenticationRequest() ] : [];
	}

	public function testForAccountCreation( $user, $creator, array $reqs ) {
		/** @var TitleBlacklistAuthenticationRequest $req */
		$req = AuthenticationRequest::getRequestByClass( $reqs,
			TitleBlacklistAuthenticationRequest::class );
		// For phan check, to ensure that $req is instance of \TitleBlacklistAuthenticationRequest
		// or null
		if ( $req instanceof TitleBlacklistAuthenticationRequest ) {
			$override = $req->ignoreTitleBlacklist;
		} else {
			$override = false;
		}

		return Hooks::testUserName( $user->getName(), $creator, $override, true );
	}

	public function testUserForCreation( $user, $autocreate, array $options = [] ) {
		$sv = StatusValue::newGood();
		$creator = RequestContext::getMain()->getUser();

		if ( ( !$autocreate && empty( $options['creating'] ) ) || $this->blockAutoAccountCreation ) {
			$sv->merge( Hooks::testUserName(
				$user->getName(), $creator, true, (bool)$autocreate
			) );
		}
		return $sv;
	}
}
