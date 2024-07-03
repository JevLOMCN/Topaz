<?php

namespace MediaWiki\Extension\ConfirmEdit\Auth;

use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Status\Status;
use MediaWiki\User\User;

class CaptchaPreAuthenticationProvider extends AbstractPreAuthenticationProvider {
	/**
	 * @inheritDoc
	 */
	public function getAuthenticationRequests( $action, array $options ) {
		$captcha = Hooks::getInstance();
		$user = User::newFromName( $options['username'] );

		$needed = false;
		switch ( $action ) {
			case AuthManager::ACTION_CREATE:
				$u = $user ?: new User();
				$needed = $captcha->needCreateAccountCaptcha( $u );
				if ( $needed ) {
					$captcha->setAction( 'accountcreate' );
					// This is debug level simply because generally
					// captchas are either always or never triggered on
					// view of create account, so it gets pretty noisy
					LoggerFactory::getInstance( 'captcha' )
						->debug( 'Captcha shown on account creation for {user}', [
							'event' => 'captcha.display',
							'eventType' => 'accountcreation',
							'user' => $u->getName()
						] );
				}
				break;
			case AuthManager::ACTION_LOGIN:
				$loginCounter = $this->getLoginAttemptCounter( $captcha );
				// Captcha is shown on login when there were too many failed attempts from the current IP
				// or using a given username. The latter is a bit awkward because we don't know the
				// username yet. The username from the last successful login is stored in a cookie,
				// but we still must make sure to not lock out other usernames, so after the first
				// failed login attempt using a username that needs a captcha, set a session flag
				// to display a captcha on login from that point on. This will result in confusing
				// error messages if the browser cannot persist the session (because we'll never show
				// a required captcha field), but then login would be impossible anyway so no big deal.

				// If the username ends to be one that does not trigger the captcha, that will
				// result in weird behavior (if the user leaves the captcha field empty, they get
				// a required field error; if they fill it with an invalid answer, it will pass)
				// - again, not a huge deal.
				$session = $this->manager->getRequest()->getSession();
				$userProbablyNeedsCaptcha = $session->get( 'ConfirmEdit:loginCaptchaPerUserTriggered' );
				$suggestedUsername = $session->suggestLoginUsername();
				if (
					$loginCounter->isBadLoginTriggered()
					|| $userProbablyNeedsCaptcha
					|| ( $suggestedUsername && $loginCounter->isBadLoginPerUserTriggered( $suggestedUsername ) )
				) {
					$needed = true;
					$captcha->setAction( 'badlogin' );
					LoggerFactory::getInstance( 'captcha' )
						->info( 'Captcha shown on login by {clientip} for {suggestedUser}', [
							'event' => 'captcha.display',
							'eventType' => 'accountcreation',
							'suggestedUser' => $suggestedUsername,
							'clientip' => $this->manager->getRequest()->getIP()
						] );
					break;
				}
				break;
		}

		if ( $needed ) {
			return [ $captcha->createAuthenticationRequest() ];
		} else {
			return [];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function testForAuthentication( array $reqs ) {
		$captcha = Hooks::getInstance();
		$username = AuthenticationRequest::getUsernameFromRequests( $reqs );
		$loginCounter = $this->getLoginAttemptCounter( $captcha );
		$success = true;
		$isBadLoginPerUserTriggered = $username ?
			$loginCounter->isBadLoginPerUserTriggered( $username ) : false;

		if ( $loginCounter->isBadLoginTriggered() || $isBadLoginPerUserTriggered ) {
			$captcha->setAction( 'badlogin' );
			$captcha->setTrigger( "post-badlogin login '$username'" );
			$success = $this->verifyCaptcha( $captcha, $reqs, new User() );
			$ip = $this->manager->getRequest()->getIP();
			LoggerFactory::getInstance( 'captcha' )->info( 'Captcha submitted on login for {user}', [
				'event' => 'captcha.submit',
				'eventType' => 'login',
				'successful' => $success,
				'user' => $username,
				'clientip' => $ip
			] );
		}

		if ( $isBadLoginPerUserTriggered ) {
			$session = $this->manager->getRequest()->getSession();
			// A captcha is needed to log in with this username, so display it on the next attempt.
			$session->set( 'ConfirmEdit:loginCaptchaPerUserTriggered', true );
		}

		// Make brute force attacks harder by not telling whether the password or the
		// captcha failed.
		return $success ? Status::newGood() : $this->makeError( 'wrongpassword', $captcha );
	}

	/**
	 * @inheritDoc
	 */
	public function testForAccountCreation( $user, $creator, array $reqs ) {
		$captcha = Hooks::getInstance();

		if ( $captcha->needCreateAccountCaptcha( $creator ) ) {
			$username = $user->getName();
			$captcha->setAction( 'accountcreate' );
			$captcha->setTrigger( "new account '$username'" );
			$success = $this->verifyCaptcha( $captcha, $reqs, $user );
			$ip = $this->manager->getRequest()->getIP();
			LoggerFactory::getInstance( 'captcha' )->info(
				'Captcha submitted on account creation for {user}', [
					'event' => 'captcha.submit',
					'eventType' => 'accountcreation',
					'successful' => $success,
					'user' => $username,
					'clientip' => $ip
				]
			);
			if ( !$success ) {
				return $this->makeError( 'captcha-createaccount-fail', $captcha );
			}
		}
		return Status::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function postAuthentication( $user, AuthenticationResponse $response ) {
		$captcha = Hooks::getInstance();
		$loginCounter = $this->getLoginAttemptCounter( $captcha );
		switch ( $response->status ) {
			case AuthenticationResponse::PASS:
			case AuthenticationResponse::RESTART:
				$session = $this->manager->getRequest()->getSession();
				$session->remove( 'ConfirmEdit:loginCaptchaPerUserTriggered' );
				$loginCounter->resetBadLoginCounter( $user ? $user->getName() : null );
				break;
			case AuthenticationResponse::FAIL:
				$loginCounter->increaseBadLoginCounter( $user ? $user->getName() : null );
				break;
		}
	}

	/**
	 * Verify submitted captcha.
	 * Assumes that the user has to pass the capctha (permission checks are caller's responsibility).
	 * @param SimpleCaptcha $captcha
	 * @param AuthenticationRequest[] $reqs
	 * @param User $user
	 * @return bool
	 */
	protected function verifyCaptcha( SimpleCaptcha $captcha, array $reqs, User $user ) {
		/** @var CaptchaAuthenticationRequest $req */
		$req = AuthenticationRequest::getRequestByClass( $reqs,
			CaptchaAuthenticationRequest::class, true );
		if ( !$req ) {
			return false;
		}
		return $captcha->passCaptchaLimited( $req->captchaId, $req->captchaWord, $user );
	}

	/**
	 * @param string $message Message key
	 * @param SimpleCaptcha $captcha
	 * @return Status
	 */
	protected function makeError( $message, SimpleCaptcha $captcha ) {
		$error = $captcha->getError();
		if ( $error ) {
			return Status::newFatal( wfMessage( 'captcha-error', $error ) );
		}
		return Status::newFatal( $message );
	}

	protected function getLoginAttemptCounter( SimpleCaptcha $captcha ): LoginAttemptCounter {
		// Overridable for testing
		return new LoginAttemptCounter( $captcha );
	}
}
