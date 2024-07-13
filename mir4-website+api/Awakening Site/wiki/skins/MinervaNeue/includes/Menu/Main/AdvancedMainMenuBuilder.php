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
use MediaWiki\Minerva\Menu\Group;

/**
 * A menu builder that provides additional menu entries that match
 * Advanced Mobile Contributions project requirements. This menu
 * is used when AMC SkinOption flag is set to true.
 *
 * @package MediaWiki\Minerva\Menu\Main
 */
final class AdvancedMainMenuBuilder implements IMainMenuBuilder {
	/**
	 * @var bool
	 */
	private $showMobileOptions;

	/**
	 * @var bool
	 */
	private $showDonateLink;

	/**
	 * @var Definitions
	 */
	private $definitions;

	/**
	 * Initialize the Default Main Menu builder
	 *
	 * @param bool $showMobileOptions Show MobileOptions instead of Preferences
	 * @param bool $showDonateLink whether to show the donate link
	 * @param Definitions $definitions A menu items definitions set
	 */
	public function __construct( $showMobileOptions, $showDonateLink, Definitions $definitions ) {
		$this->showMobileOptions = $showMobileOptions;
		$this->showDonateLink = $showDonateLink;
		$this->definitions = $definitions;
	}

	/**
	 * @return Group
	 */
	public function getSettingsGroup(): Group {
		return new Group( 'pt-preferences' );
	}

	/**
	 * @inheritDoc
	 */
	public function getPersonalToolsGroup( array $personalTools ): Group {
		return BuilderUtil::getConfigurationTools( $this->definitions, $this->showMobileOptions );
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
	public function getSiteLinks(): Group {
		return BuilderUtil::getSiteLinks( $this->definitions );
	}

	/**
	 * Prepares a list of links that have the purpose of discovery in the main navigation menu
	 * @return Group
	 */
	public function getInteractionToolsGroup(): Group {
		$group = new Group( 'p-interaction' );

		$this->definitions->insertRecentChanges( $group );
		$this->definitions->insertSpecialPages( $group );
		$this->definitions->insertCommunityPortal( $group );

		return $group;
	}
}
