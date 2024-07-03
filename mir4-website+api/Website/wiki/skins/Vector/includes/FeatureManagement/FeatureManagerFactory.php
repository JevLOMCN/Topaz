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
 * @since 1.42
 */

namespace MediaWiki\Skins\Vector\FeatureManagement;

use MediaWiki\Context\IContextSource;
use MediaWiki\Skins\Vector\Constants;
use MediaWiki\Skins\Vector\FeatureManagement\Requirements\DynamicConfigRequirement;
use MediaWiki\Skins\Vector\FeatureManagement\Requirements\LimitedWidthContentRequirement;
use MediaWiki\Skins\Vector\FeatureManagement\Requirements\LoggedInRequirement;
use MediaWiki\Skins\Vector\FeatureManagement\Requirements\OverridableConfigRequirement;
use MediaWiki\Skins\Vector\FeatureManagement\Requirements\UserPreferenceRequirement;
use MediaWiki\User\Options\UserOptionsLookup;

/**
 * A simple feature manager factory.
 *
 * @unstable
 *
 * @package MediaWiki\Skins\Vector\FeatureManagement
 * @internal
 */
class FeatureManagerFactory {

	private UserOptionsLookup $userOptionsLookup;

	public function __construct(
		UserOptionsLookup $userOptionsLookup
	) {
		$this->userOptionsLookup = $userOptionsLookup;
	}

	public function createFeatureManager( IContextSource $context ): FeatureManager {
		$featureManager = new FeatureManager();

		$request = $context->getRequest();
		$config = $context->getConfig();
		$user = $context->getUser();
		$title = $context->getTitle();

		$featureManager->registerRequirement(
			new DynamicConfigRequirement(
				$config,
				Constants::CONFIG_KEY_FULLY_INITIALISED,
				Constants::REQUIREMENT_FULLY_INITIALISED
			)
		);

		// Feature: Languages in sidebar
		// ================================
		$featureManager->registerRequirement(
			new OverridableConfigRequirement(
				$config,
				$user,
				$request,
				Constants::CONFIG_KEY_LANGUAGE_IN_HEADER,
				Constants::REQUIREMENT_LANGUAGE_IN_HEADER
			)
		);

		// ---

		$featureManager->registerFeature(
			Constants::FEATURE_LANGUAGE_IN_HEADER,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_LANGUAGE_IN_HEADER,
			]
		);

		// Feature: T293470: Language in main page header
		// ================================
		$featureManager->registerRequirement(
			new OverridableConfigRequirement(
				$config,
				$user,
				$request,
				Constants::CONFIG_LANGUAGE_IN_MAIN_PAGE_HEADER,
				Constants::REQUIREMENT_LANGUAGE_IN_MAIN_PAGE_HEADER
			)
		);

		$featureManager->registerSimpleRequirement(
			Constants::REQUIREMENT_IS_MAIN_PAGE,
			$title ? $title->isMainPage() : false
		);

		$featureManager->registerFeature(
			Constants::FEATURE_LANGUAGE_IN_MAIN_PAGE_HEADER,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_IS_MAIN_PAGE,
				Constants::REQUIREMENT_LANGUAGE_IN_HEADER,
				Constants::REQUIREMENT_LANGUAGE_IN_MAIN_PAGE_HEADER
			]
		);

		// Feature: Sticky header
		// ================================
		$featureManager->registerRequirement(
			new OverridableConfigRequirement(
				$config,
				$user,
				$request,
				Constants::CONFIG_STICKY_HEADER,
				Constants::REQUIREMENT_STICKY_HEADER
			)
		);

		$featureManager->registerFeature(
			Constants::FEATURE_STICKY_HEADER,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_STICKY_HEADER
			]
		);

		// Feature: Page tools pinned
		// ================================
		$featureManager->registerRequirement(
			new LoggedInRequirement(
				$user,
				Constants::REQUIREMENT_LOGGED_IN
			)
		);

		$featureManager->registerRequirement(
			new UserPreferenceRequirement(
				$user,
				$this->userOptionsLookup,
				Constants::PREF_KEY_PAGE_TOOLS_PINNED,
				Constants::REQUIREMENT_PAGE_TOOLS_PINNED,
				$request,
				$title
			)
		);

		$featureManager->registerFeature(
			Constants::FEATURE_PAGE_TOOLS_PINNED,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_LOGGED_IN,
				Constants::REQUIREMENT_PAGE_TOOLS_PINNED
			]
		);

		// Feature: Table of Contents pinned
		// ================================
		$featureManager->registerRequirement(
			new UserPreferenceRequirement(
				$user,
				$this->userOptionsLookup,
				Constants::PREF_KEY_TOC_PINNED,
				Constants::REQUIREMENT_TOC_PINNED,
				$request,
				$title
			)
		);

		$featureManager->registerFeature(
			Constants::FEATURE_TOC_PINNED,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_TOC_PINNED
			]
		);

		// Feature: Main menu pinned
		// ================================
		$featureManager->registerRequirement(
			new UserPreferenceRequirement(
				$user,
				$this->userOptionsLookup,
				Constants::PREF_KEY_MAIN_MENU_PINNED,
				Constants::REQUIREMENT_MAIN_MENU_PINNED,
				$request,
				$title
			)
		);

		$featureManager->registerFeature(
			Constants::FEATURE_MAIN_MENU_PINNED,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_LOGGED_IN,
				Constants::REQUIREMENT_MAIN_MENU_PINNED
			]
		);

		// Feature: Max Width (skin)
		// ================================
		$featureManager->registerRequirement(
			new UserPreferenceRequirement(
				$user,
				$this->userOptionsLookup,
				Constants::PREF_KEY_LIMITED_WIDTH,
				Constants::REQUIREMENT_LIMITED_WIDTH,
				$request,
				$title
			)
		);
		$featureManager->registerFeature(
			Constants::FEATURE_LIMITED_WIDTH,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_LIMITED_WIDTH
			]
		);

		// Feature: Max Width (content)
		// ================================
		$featureManager->registerRequirement(
			new LimitedWidthContentRequirement(
				$config,
				$request,
				$title
			)
		);
		$featureManager->registerFeature(
			Constants::FEATURE_LIMITED_WIDTH_CONTENT,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_LIMITED_WIDTH_CONTENT,
			]
		);

		// Feature: T343928: Feature Font Size.
		// ================================
		$featureManager->registerRequirement(
			new UserPreferenceRequirement(
				$user,
				$this->userOptionsLookup,
				Constants::PREF_KEY_FONT_SIZE,
				Constants::REQUIREMENT_FONT_SIZE,
				$request,
				$title
			)
		);

		// Register 'custom-font-size' as the default requirement
		$featureManager->registerFeature(
			Constants::FEATURE_FONT_SIZE,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_FONT_SIZE
			]
		);

		// Feature: T345363: Client preferences dialog
		// ============================================
		$featureManager->registerRequirement(
			new OverridableConfigRequirement(
				$config,
				$user,
				$request,
				Constants::CONFIG_KEY_CLIENT_PREFERENCES,
				Constants::REQUIREMENT_CLIENT_PREFERENCES
			)
		);

		$featureManager->registerFeature(
			Constants::FEATURE_CLIENT_PREFERENCES,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_CLIENT_PREFERENCES
			]
		);

		// Feature: Client preference pinned
		// ================================
		$featureManager->registerRequirement(
			new UserPreferenceRequirement(
				$user,
				$this->userOptionsLookup,
				Constants::PREF_KEY_CLIENT_PREFS_PINNED,
				Constants::REQUIREMENT_CLIENT_PREFS_PINNED,
				$request,
				$title
			)
		);

		$featureManager->registerFeature(
			Constants::FEATURE_CLIENT_PREFS_PINNED,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_CLIENT_PREFERENCES,
				Constants::REQUIREMENT_CLIENT_PREFS_PINNED
			]
		);

		// Feature: Night mode (T355065)
		// ============================================
		$featureManager->registerRequirement(
			new OverridableConfigRequirement(
				$config,
				$user,
				$request,
				Constants::CONFIG_KEY_NIGHT_MODE,
				Constants::REQUIREMENT_NIGHT_MODE
			)
		);

		$featureManager->registerFeature(
			Constants::FEATURE_NIGHT_MODE,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_NIGHT_MODE
			]
		);

		// Preference: Night mode (T355065)
		// ============================================
		$featureManager->registerRequirement(
			new UserPreferenceRequirement(
				$user,
				$this->userOptionsLookup,
				Constants::PREF_KEY_NIGHT_MODE,
				Constants::REQUIREMENT_PREF_NIGHT_MODE,
				$request,
				$title
			)
		);

		$featureManager->registerFeature(
			Constants::PREF_NIGHT_MODE,
			[
				Constants::REQUIREMENT_FULLY_INITIALISED,
				Constants::REQUIREMENT_NIGHT_MODE,
				Constants::REQUIREMENT_PREF_NIGHT_MODE
			]
		);

		return $featureManager;
	}

}
