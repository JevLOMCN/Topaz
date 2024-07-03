<?php
/**
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
 *
 * @file
 */

namespace MediaWiki\Minerva\Menu\Main;

use MediaWiki\Minerva\Menu\Definitions;
use MediaWiki\Minerva\Menu\Entries\SingleMenuEntry;
use MediaWiki\Minerva\Menu\Group;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityUtils;

/**
 * Used to build default (available for everyone by default) main menu
 */
final class DefaultMainMenuBuilder implements IMainMenuBuilder {

	/**
	 * @var bool
	 */
	private $showMobileOptions;

	/**
	 * @var bool
	 */
	private $showDonateLink;

	/**
	 * Currently logged in user
	 * @var UserIdentity
	 */
	private $user;

	/**
	 * @var Definitions
	 */
	private $definitions;

	private UserIdentityUtils $userIdentityUtils;

	/**
	 * Initialize the Default Main Menu builder
	 *
	 * @param bool $showMobileOptions Show MobileOptions instead of Preferences
	 * @param bool $showDonateLink whether to show the donate link
	 * @param UserIdentity $user The current user
	 * @param Definitions $definitions A menu items definitions set
	 * @param UserIdentityUtils $userIdentityUtils
	 */
	public function __construct(
		$showMobileOptions,
		$showDonateLink,
		UserIdentity $user,
		Definitions $definitions,
		UserIdentityUtils $userIdentityUtils
	) {
		$this->showMobileOptions = $showMobileOptions;
		$this->showDonateLink = $showDonateLink;
		$this->user = $user;
		$this->definitions = $definitions;
		$this->userIdentityUtils = $userIdentityUtils;
	}

	/**
	 * @inheritDoc
	 */
	public function getDiscoveryGroup( array $navigationTools ): Group {
		return BuilderUtil::getDiscoveryTools( $this->definitions, $navigationTools );
	}

	/**
	 * @inheritDoc
	 */
	public function getDonateGroup(): Group {
		return BuilderUtil::getDonateGroup( $this->definitions, $this->showDonateLink );
	}

	/**
	 * @inheritDoc
	 */
	public function getInteractionToolsGroup(): Group {
		return new Group( 'p-interaction' );
	}

	/**
	 * @inheritDoc
	 */
	public function getSiteLinks(): Group {
		return BuilderUtil::getSiteLinks( $this->definitions );
	}

	/**
	 * Builds the anonymous settings group.
	 *
	 * @inheritDoc
	 */
	public function getSettingsGroup(): Group {
		$group = new Group( 'pt-preferences' );
		// Show settings group for anon and temp users
		$isTemp = $this->userIdentityUtils->isTemp( $this->user );
		if ( $this->showMobileOptions && ( !$this->user->isRegistered() || $isTemp ) ) {
			$this->definitions->insertMobileOptionsItem( $group );
		}
		return $group;
	}

	/**
	 * Builds the personal tools menu item group.
	 *
	 * ... by adding the Watchlist, Settings, and Log{in,out} menu items in the given order.
	 *
	 * @inheritDoc
	 */
	public function getPersonalToolsGroup( array $personalTools ): Group {
		$group = new Group( 'p-personal' );
		$excludeKeyList = [ 'betafeatures', 'mytalk', 'sandbox' ];

		// For anonymous users exclude all links except login.
		if ( !$this->user->isRegistered() ) {
			$excludeKeyList = array_diff(
				array_keys( $personalTools ),
				[ 'login' ]
			);
		}
		$isTemp = $this->userIdentityUtils->isTemp( $this->user );
		if ( $isTemp ) {
			$excludeKeyList[] = 'mycontris';
		}
		foreach ( $personalTools as $key => $item ) {
			$href = $item['href'] ?? null;
			if ( $href && !in_array( $key, $excludeKeyList ) ) {
				// Substitute preference if $showMobileOptions is set.
				if ( $this->showMobileOptions && $key === 'preferences' ) {
					$this->definitions->insertMobileOptionsItem( $group );
				} else {
					$icon = $item['icon'] ?? null;
					$entry = SingleMenuEntry::create(
						$key,
						$item['text'],
						$href,
						$item['class'] ?? '',
						$icon
					);

					$entry->trackClicks( $key );
					$group->insertEntry( $entry );
				}
			}
		}
		return $group;
	}
}
