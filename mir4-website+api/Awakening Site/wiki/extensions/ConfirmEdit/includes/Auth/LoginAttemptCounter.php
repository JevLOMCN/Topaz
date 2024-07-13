<?php

namespace MediaWiki\Extension\ConfirmEdit\Auth;

use BagOStuff;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserNameUtils;
use ObjectCache;
use User;

/**
 * Helper to count login attempts per IP and per username.
 *
 * @internal
 */
class LoginAttemptCounter {
	private SimpleCaptcha $captcha;

	public function __construct( SimpleCaptcha $captcha ) {
		$this->captcha = $captcha;
	}

	/**
	 * Increase bad login counter after a failed login.
	 * The user might be required to solve a captcha if the count is high.
	 * @param string $username
	 * TODO use Throttler
	 */
	public function increaseBadLoginCounter( $username ) {
		global $wgCaptchaBadLoginExpiration, $wgCaptchaBadLoginPerUserExpiration;

		$cache = ObjectCache::getLocalClusterInstance();

		if ( $this->captcha->triggersCaptcha( CaptchaTriggers::BAD_LOGIN ) ) {
			$key = $this->badLoginKey( $cache );
			$cache->incrWithInit( $key, $wgCaptchaBadLoginExpiration );
		}

		if ( $this->captcha->triggersCaptcha( CaptchaTriggers::BAD_LOGIN_PER_USER ) && $username ) {
			$key = $this->badLoginPerUserKey( $username, $cache );
			$cache->incrWithInit( $key, $wgCaptchaBadLoginPerUserExpiration );
		}
	}

	/**
	 * Reset bad login counter after a successful login.
	 * @param string $username
	 */
	public function resetBadLoginCounter( $username ) {
		if ( $this->captcha->triggersCaptcha( CaptchaTriggers::BAD_LOGIN_PER_USER ) && $username ) {
			$cache = ObjectCache::getLocalClusterInstance();
			$cache->delete( $this->badLoginPerUserKey( $username, $cache ) );
		}
	}

	/**
	 * Check if a bad login has already been registered for this
	 * IP address. If so, require a captcha.
	 * @return bool
	 */
	public function isBadLoginTriggered() {
		global $wgCaptchaBadLoginAttempts;

		$cache = ObjectCache::getLocalClusterInstance();
		return $this->captcha->triggersCaptcha( CaptchaTriggers::BAD_LOGIN )
			&& (int)$cache->get( $this->badLoginKey( $cache ) ) >= $wgCaptchaBadLoginAttempts;
	}

	/**
	 * Is the per-user captcha triggered?
	 *
	 * @param User|string $u User object, or name
	 * @return bool
	 */
	public function isBadLoginPerUserTriggered( $u ) {
		global $wgCaptchaBadLoginPerUserAttempts;

		$cache = ObjectCache::getLocalClusterInstance();

		if ( is_object( $u ) ) {
			$u = $u->getName();
		}
		$badLoginPerUserKey = $this->badLoginPerUserKey( $u, $cache );
		return $this->captcha->triggersCaptcha( CaptchaTriggers::BAD_LOGIN_PER_USER )
			&& (int)$cache->get( $badLoginPerUserKey ) >= $wgCaptchaBadLoginPerUserAttempts;
	}

	/**
	 * Internal cache key for badlogin checks.
	 * @param BagOStuff $cache
	 * @return string
	 */
	private function badLoginKey( BagOStuff $cache ) {
		global $wgRequest;
		$ip = $wgRequest->getIP();

		return $cache->makeGlobalKey( 'captcha', 'badlogin', 'ip', $ip );
	}

	/**
	 * Cache key for badloginPerUser checks.
	 * @param string $username
	 * @param BagOStuff $cache
	 * @return string
	 */
	private function badLoginPerUserKey( $username, BagOStuff $cache ) {
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();
		$username = $userNameUtils->getCanonical( $username, UserNameUtils::RIGOR_USABLE ) ?: $username;

		return $cache->makeGlobalKey(
			'captcha', 'badlogin', 'user', md5( $username )
		);
	}
}
