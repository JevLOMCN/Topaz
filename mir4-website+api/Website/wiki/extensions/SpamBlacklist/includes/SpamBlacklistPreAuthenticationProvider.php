<?php

namespace MediaWiki\Extension\SpamBlacklist;

use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use StatusValue;

class SpamBlacklistPreAuthenticationProvider extends AbstractPreAuthenticationProvider {
	public function testForAccountCreation( $user, $creator, array $reqs ) {
		$blacklist = BaseBlacklist::getEmailBlacklist();
		if ( $blacklist->checkUser( $user ) ) {
			return StatusValue::newGood();
		}

		return StatusValue::newFatal( 'spam-blacklisted-email-signup' );
	}
}
