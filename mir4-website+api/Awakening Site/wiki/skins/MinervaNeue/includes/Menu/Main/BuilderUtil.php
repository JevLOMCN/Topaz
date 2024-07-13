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
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

/**
 * Group generators shared between menu builders.
 *
 * @package MediaWiki\Minerva\Menu\Main
 */
final class BuilderUtil {
	/**
	 * Prepares donate group if available
	 * @param Definitions $definitions A menu items definitions set
	 * @param bool $includeDonateLink whether to include it or not.
	 * @return Group
	 */
	public static function getDonateGroup( Definitions $definitions, $includeDonateLink ): Group {
		$group = new Group( 'p-donation' );
		if ( $includeDonateLink ) {
			$definitions->insertDonateItem( $group );
		}
		return $group;
	}

	/**
	 * Prepares a list of links that have the purpose of discovery in the main navigation menu
	 * @param Definitions $definitions A menu items definitions set
	 * @param array $navigationTools
	 * @return Group
	 */
	public static function getDiscoveryTools(
		Definitions $definitions,
		array $navigationTools
	): Group {
		$group = new Group( 'p-navigation' );

		$entryDefinitions = [
			'n-mainpage-description' => [
				'name' => 'home',
				'text' => $definitions->msg( 'mobile-frontend-home-button' ),
				'icon' => 'home',
				'class' => '',
				'href' => Title::newMainPage()->getLocalURL(),
			],
			'n-randompage' => [
				'name' => 'random',
				'text' => $definitions->msg( 'mobile-frontend-random-button' ),
				'icon' => 'die',
				'class' => '',
				'href' => SpecialPage::getTitleFor( 'Randompage' )->getLocalURL(),
			],
		];
		// Run through navigation tools and update if needed.
		foreach ( $navigationTools as $item ) {
			$id = $item['id'] ?? null;
			if ( $id && isset( $entryDefinitions[ $id ] ) ) {
				foreach ( [ 'icon', 'class', 'href', 'msg' ] as $overridableKey ) {
					$override = $item[ $overridableKey ] ?? null;
					if ( $override ) {
						$entryDefinitions[$id][$overridableKey] = $override;
					}
				}
			}
		}
		// Build the menu
		foreach ( $entryDefinitions as $definition ) {
			$msgKey = $definition['msg'] ?? null;
			$text = null;
			if ( $msgKey ) {
				$msg = $definitions->msg( $msgKey );
				$text = $msg->exists() ? $msg->text() : null;
			}
			if ( !$text ) {
				$text = $definition['text'];
			}

			$entry = SingleMenuEntry::create(
				$definition['name'],
				$text,
				$definition['href'],
				$definition['class'],
				$definition['icon']
			);
			$group->insertEntry( $entry );
		}
		$definitions->insertNearbyIfSupported( $group );
		return $group;
	}

	/**
	 * Like <code>SkinMinerva#getDiscoveryTools</code> and <code>#getPersonalTools</code>, create
	 * a group of configuration-related menu items. Currently, only the Settings menu item is in the
	 * group.
	 * @param Definitions $definitions A menu items definitions set
	 * @param bool $showMobileOptions Show MobileOptions instead of Preferences
	 * @return Group
	 */
	public static function getConfigurationTools(
		Definitions $definitions, $showMobileOptions
	): Group {
		$group = new Group( 'pt-preferences' );

		$showMobileOptions ?
			$definitions->insertMobileOptionsItem( $group ) :
			$definitions->insertPreferencesItem( $group );

		return $group;
	}

	/**
	 * Returns an array of sitelinks to add into the main menu footer.
	 * @param Definitions $definitions A menu items definitions set
	 * @return Group Collection of site links
	 */
	public static function getSiteLinks( Definitions $definitions ): Group {
		$group = new Group( 'p-minerva-sitelinks' );

		$definitions->insertAboutItem( $group );
		$definitions->insertDisclaimersItem( $group );
		return $group;
	}
}
