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
namespace MediaWiki\Minerva;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Minerva\Hooks\HookRunner;
use MediaWiki\Minerva\Skins\SkinMinerva;
use MediaWiki\Minerva\Skins\SkinUserPageHelper;
use MobileContext;
use Skin;

/**
 * A wrapper for all available Skin options.
 */
final class SkinOptions {

	public const MOBILE_OPTIONS = 'mobileOptionsLink';
	public const CATEGORIES = 'categories';
	public const PAGE_ISSUES = 'pageIssues';
	public const BETA_MODE = 'beta';
	public const TALK_AT_TOP = 'talkAtTop';
	public const SHOW_DONATE = 'donate';
	public const HISTORY_IN_PAGE_ACTIONS = 'historyInPageActions';
	public const TOOLBAR_SUBMENU = 'overflowSubmenu';
	public const TABS_ON_SPECIALS = 'tabsOnSpecials';
	public const MAIN_MENU_EXPANDED = 'mainMenuExpanded';
	public const PERSONAL_MENU = 'personalMenu';
	public const SINGLE_ECHO_BUTTON = 'echo';
	public const NIGHT_MODE = 'nightMode';

	/**
	 * Note stable skin options default to true for desktop-Minerva and are expected to be
	 * overridden on mobile.
	 * @var array skin specific options, initialized with default values
	 */
	private $skinOptions = [
		self::BETA_MODE => false,
		self::SHOW_DONATE => true,
		/**
		 * Whether the main menu should include a link to
		 * Special:Preferences of Special:MobileOptions
		 */
		self::MOBILE_OPTIONS => false,
		/** Whether a categories button should appear at the bottom of the skin. */
		self::CATEGORIES => false,
		/** requires a wiki using Template:Ambox */
		self::PAGE_ISSUES => false,
		/** no extension requirements */
		self::TALK_AT_TOP => true,
		/** no extension requirements */
		self::HISTORY_IN_PAGE_ACTIONS => true,
		/** no extension requirements */
		self::TOOLBAR_SUBMENU => true,
		/** Whether to show tabs on special pages */
		self::TABS_ON_SPECIALS => true,
		/** whether to show a personal menu */
		self::PERSONAL_MENU => true,
		/** whether to show a main menu with additional items */
		self::MAIN_MENU_EXPANDED => true,
		/** whether Echo should be replaced with a single button */
		self::SINGLE_ECHO_BUTTON => false,
		/** whether night mode is available to the user */
		self::NIGHT_MODE => false,
	];

	private HookContainer $hookContainer;
	private SkinUserPageHelper $skinUserPageHelper;

	public function __construct(
		HookContainer $hookContainer,
		SkinUserPageHelper $skinUserPageHelper
	) {
		$this->hookContainer = $hookContainer;
		$this->skinUserPageHelper = $skinUserPageHelper;
	}

	/**
	 * override an existing option or options with new values
	 * @param array $options
	 */
	public function setMultiple( array $options ) {
		foreach ( $options as $option => $value ) {
			if ( !array_key_exists( $option, $this->skinOptions ) ) {
				throw new \OutOfBoundsException( "SkinOption $option is not defined" );
			}
		}
		$this->skinOptions = array_merge( $this->skinOptions, $options );
	}

	/**
	 * Return whether a skin option is truthy. Should be one of self:* constants
	 * @param string $key
	 * @return bool
	 */
	public function get( $key ) {
		if ( !array_key_exists( $key, $this->skinOptions ) ) {
			throw new \OutOfBoundsException( "SkinOption $key doesn't exist" );
		}
		return $this->skinOptions[$key];
	}

	/**
	 * Get all skin options
	 * @return array
	 */
	public function getAll() {
		return $this->skinOptions;
	}

	/**
	 * Return whether any of the skin options have been set
	 * @return bool
	 */
	public function hasSkinOptions() {
		foreach ( $this->skinOptions as $key => $val ) {
			if ( $val ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Set the skin options for Minerva
	 *
	 * @param MobileContext $mobileContext
	 * @param Skin $skin
	 */
	public function setMinervaSkinOptions(
		MobileContext $mobileContext, Skin $skin
	) {
		// setSkinOptions is not available
		if ( $skin instanceof SkinMinerva ) {
			$services = MediaWikiServices::getInstance();
			$featureManager = $services
				->getService( 'MobileFrontend.FeaturesManager' );
			$title = $skin->getTitle();

			// T245162 - this should only apply if the context relates to a page view.
			// Examples:
			// - parsing wikitext during an REST response
			// - a ResourceLoader response
			if ( $title !== null ) {
				// T232653: TALK_AT_TOP, HISTORY_IN_PAGE_ACTIONS, TOOLBAR_SUBMENU should
				// be true on user pages and user talk pages for all users
				$this->skinUserPageHelper
					->setContext( $mobileContext )
					->setTitle(
						$title->inNamespace( NS_USER_TALK ) ? $title->getSubjectPage() : $title
					);

				$isUserPage = $this->skinUserPageHelper->isUserPage();
				$isUserPageAccessible = $this->skinUserPageHelper->isUserPageAccessibleToCurrentUser();
				$isUserPageOrUserTalkPage = $isUserPage && $isUserPageAccessible;
			} else {
				// If no title this must be false
				$isUserPageOrUserTalkPage = false;
			}

			$isBeta = $mobileContext->isBetaGroupMember();
			$this->setMultiple( [
				self::SHOW_DONATE => $featureManager->isFeatureAvailableForCurrentUser( 'MinervaDonateLink' ),
				self::TALK_AT_TOP => $isUserPageOrUserTalkPage ?
					true : $featureManager->isFeatureAvailableForCurrentUser( 'MinervaTalkAtTop' ),
				self::BETA_MODE
					=> $isBeta,
				self::CATEGORIES
					=> $featureManager->isFeatureAvailableForCurrentUser( 'MinervaShowCategories' ),
				self::PAGE_ISSUES
					=> $featureManager->isFeatureAvailableForCurrentUser( 'MinervaPageIssuesNewTreatment' ),
				self::MOBILE_OPTIONS => true,
				self::PERSONAL_MENU => $featureManager->isFeatureAvailableForCurrentUser(
					'MinervaPersonalMenu'
				),
				self::MAIN_MENU_EXPANDED => $featureManager->isFeatureAvailableForCurrentUser(
					'MinervaAdvancedMainMenu'
				),
				// In mobile, always resort to single icon.
				self::SINGLE_ECHO_BUTTON => true,
				self::HISTORY_IN_PAGE_ACTIONS => $isUserPageOrUserTalkPage ?
					true : $featureManager->isFeatureAvailableForCurrentUser( 'MinervaHistoryInPageActions' ),
				self::TOOLBAR_SUBMENU => $isUserPageOrUserTalkPage ?
					true : $featureManager->isFeatureAvailableForCurrentUser(
						Hooks::FEATURE_OVERFLOW_PAGE_ACTIONS
					),
				self::TABS_ON_SPECIALS => true,
				self::NIGHT_MODE => $featureManager->isFeatureAvailableForCurrentUser( 'MinervaNightMode' ),
			] );
			( new HookRunner( $this->hookContainer ) )->onSkinMinervaOptionsInit( $skin, $this );
		}
	}
}
