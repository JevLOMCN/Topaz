<?php

namespace MediaWiki\Extension\Notifications\Push;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentity;

class Utils {

	/**
	 * Attempt to get a unique ID for the specified user, accounting for installations both with
	 * and without CentralAuth: Return the user's central ID, if available. If there is no central
	 * user associated with the local user (i.e., centralIdFromLocalUser returns 0), fall back to
	 * returning the local user ID.
	 * @param UserIdentity $user
	 * @return int
	 */
	public static function getPushUserId( UserIdentity $user ): int {
		return MediaWikiServices::getInstance()
			->getCentralIdLookup()
			->centralIdFromLocalUser(
				$user,
				CentralIdLookup::AUDIENCE_RAW
			) ?: $user->getId();
	}

}
