<?php
/**
 * Copyright (C) 2022 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\Extension\OATHAuth\Notifications;

use ExtensionRegistry;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\OATHAuth\OATHUser;
use MediaWiki\SpecialPage\SpecialPage;

/**
 * Manages logic for configuring and sending out notifications with Echo
 */
class Manager {

	/**
	 * Whether Echo is installed and can be used
	 *
	 * @return bool
	 */
	private static function isEnabled(): bool {
		return ExtensionRegistry::getInstance()->isLoaded( 'Echo' );
	}

	/**
	 * Send a notification that 2FA has been disabled
	 *
	 * @param OATHUser $oUser
	 * @param bool $self Whether they disabled it themselves
	 */
	public static function notifyDisabled( OATHUser $oUser, bool $self ) {
		if ( !self::isEnabled() ) {
			return;
		}
		Event::create( [
			// message used: notification-header-oathauth-disable
			'type' => 'oathauth-disable',
			'title' => SpecialPage::getTitleFor( 'Preferences' ),
			'agent' => $oUser->getUser(),
			'extra' => [
				'self' => $self,
				'activeDevices' => count( $oUser->getKeys() ),
			]
		] );
	}

	/**
	 * Send a notification that 2FA has been enabled
	 *
	 * @param OATHUser $oUser
	 */
	public static function notifyEnabled( OATHUser $oUser ) {
		if ( !self::isEnabled() ) {
			return;
		}
		Event::create( [
			// message used: notification-header-oathauth-enable
			'type' => 'oathauth-enable',
			'title' => SpecialPage::getTitleFor( 'Preferences' ),
			'agent' => $oUser->getUser(),
			'extra' => [
				'activeDevices' => count( $oUser->getKeys() ),
			],
		] );
	}
}
