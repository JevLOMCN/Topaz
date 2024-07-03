<?php

namespace MediaWiki\Skins\Vector;

use MediaWiki\Extension\CentralAuth\Hooks\CentralAuthIsUIReloadRecommendedHook;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;

/**
 * @package Vector
 * @internal
 */
class CentralAuthHooks implements CentralAuthIsUIReloadRecommendedHook {

	private UserOptionsLookup $userOptionsLookup;

	public function __construct( UserOptionsLookup $userOptionsLookup ) {
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function onCentralAuthIsUIReloadRecommended( User $user, bool &$recommendReload ) {
		if (
			$this->userOptionsLookup->getDefaultOption( 'skin', $user ) ===
			Constants::SKIN_NAME_MODERN
		) {
			// Vector 2022 does not support updating the UI without reloading the page (T345112)
			$recommendReload = true;
		}
	}

}
