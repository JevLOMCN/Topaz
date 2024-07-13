<?php
namespace MediaWiki\Skins\Vector;

use FatalError;

/**
 * A namespace for Vector constants for internal Vector usage only. **Do not rely on this file as an
 * API as it may change without warning at any time.**
 * @package Vector
 * @internal
 */
final class Constants {
	/**
	 * This is tightly coupled to the ValidSkinNames field in skin.json.
	 * @var string
	 */
	public const SKIN_NAME_MODERN = 'vector-2022';

	/**
	 * This is tightly coupled to the ValidSkinNames field in skin.json.
	 * @var string
	 */
	public const SKIN_NAME_LEGACY = 'vector';

	// These are used to provide different default skin for new users.
	/**
	 * @var string
	 */
	public const SKIN_VERSION_LEGACY = '1';
	/**
	 * @var string
	 */
	public const SKIN_VERSION_LATEST = '2';

	// These are tightly coupled to skin.json's configs. See skin.json for documentation.
	/**
	 * @var string
	 */
	public const CONFIG_KEY_DEFAULT_SKIN_VERSION_FOR_NEW_ACCOUNTS =
		'VectorDefaultSkinVersionForNewAccounts';

	/**
	 * @var string
	 */
	public const PREF_KEY_SKIN = 'skin';

	// These are used in the Feature Management System.
	/**
	 * Also known as `$wgFullyInitialised`. Set to true in core/includes/Setup.php.
	 * @var string
	 */
	public const CONFIG_KEY_FULLY_INITIALISED = 'FullyInitialised';

	/**
	 * @var string
	 */
	public const REQUIREMENT_FULLY_INITIALISED = 'FullyInitialised';

	/**
	 * @var string
	 */
	public const REQUIREMENT_LOGGED_IN = 'LoggedIn';

	/**
	 * @var string
	 */
	public const FEATURE_LANGUAGE_IN_HEADER = 'LanguageInHeader';

	/**
	 * @var string
	 */
	public const CONFIG_KEY_LANGUAGE_IN_HEADER = 'VectorLanguageInHeader';

	/**
	 * @var string
	 */
	public const REQUIREMENT_LANGUAGE_IN_HEADER = 'LanguageInHeader';

	/**
	 * @var string
	 */
	public const CONFIG_STICKY_HEADER = 'VectorStickyHeader';

	/**
	 * @var string
	 */
	public const REQUIREMENT_STICKY_HEADER = 'StickyHeader';

	/**
	 * @var string
	 */
	public const FEATURE_STICKY_HEADER = 'StickyHeader';

	/**
	 * Defines whether an A/B test is running.
	 *
	 * @var string
	 */
	public const CONFIG_WEB_AB_TEST_ENROLLMENT = 'VectorWebABTestEnrollment';

	/**
	 * The `mediawiki.searchSuggest` protocol piece of the SearchSatisfaction instrumention reads
	 * the value of an element with the "data-search-loc" attribute and set the event's
	 * `inputLocation` property accordingly.
	 *
	 * When the search widget is moved as part of the "Search 1: Search widget move" feature, the
	 * "data-search-loc" attribute is set to this value.
	 *
	 * See also:
	 * - https://www.mediawiki.org/wiki/Reading/Web/Desktop_Improvements/Features#Search_1:_Search_widget_move
	 * - https://phabricator.wikimedia.org/T261636 and https://phabricator.wikimedia.org/T256100
	 * - https://gerrit.wikimedia.org/g/mediawiki/core/+/61d36def2d7adc15c88929c824b444f434a0511a/resources/src/mediawiki.searchSuggest/searchSuggest.js#106
	 *
	 * @var string
	 */
	public const SEARCH_BOX_INPUT_LOCATION_MOVED = 'header-moved';

	/**
	 * Similar to `Constants::SEARCH_BOX_INPUT_LOCATION_MOVED`, when the search widget hasn't been
	 * moved, the "data-search-loc" attribute is set to this value.
	 *
	 * @var string
	 */
	public const SEARCH_BOX_INPUT_LOCATION_DEFAULT = 'header-navigation';

	/**
	 * @var string
	 */
	public const REQUIREMENT_IS_MAIN_PAGE = 'IsMainPage';

	/**
	 * @var string
	 */
	public const REQUIREMENT_LANGUAGE_IN_MAIN_PAGE_HEADER = 'LanguageInMainPageHeader';

	/**
	 * @var string
	 */
	public const CONFIG_LANGUAGE_IN_MAIN_PAGE_HEADER = 'VectorLanguageInMainPageHeader';

	/**
	 * @var string
	 */
	public const FEATURE_LANGUAGE_IN_MAIN_PAGE_HEADER = 'LanguageInMainPageHeader';

	/**
	 * @var string
	 */
	public const WEB_AB_TEST_ARTICLE_ID_FACTORY_SERVICE = 'WikimediaEvents.WebABTestArticleIdFactory';

	/**
	 * @var string
	 */
	public const FEATURE_PAGE_TOOLS_PINNED = 'PageToolsPinned';

	/**
	 * @var string
	 */
	public const REQUIREMENT_PAGE_TOOLS_PINNED = 'PageToolsPinned';

	/**
	 * @var string
	 */
	public const PREF_KEY_PAGE_TOOLS_PINNED = 'vector-page-tools-pinned';

	/**
	 * @var string
	 */
	public const REQUIREMENT_TOC_PINNED = 'TOCPinned';

	/**
	 * @var string
	 */
	public const PREF_KEY_TOC_PINNED = 'vector-toc-pinned';

	/**
	 * @var string
	 */
	public const FEATURE_TOC_PINNED = 'TOCPinned';

	/**
	 * @var string
	 */
	public const FEATURE_MAIN_MENU_PINNED = 'MainMenuPinned';

	/**
	 * @var string
	 */
	public const REQUIREMENT_MAIN_MENU_PINNED = 'MainMenuPinned';

	/**
	 * @var string
	 */
	public const PREF_KEY_MAIN_MENU_PINNED = 'vector-main-menu-pinned';

	/**
	 * @var string
	 */
	public const FEATURE_LIMITED_WIDTH = 'LimitedWidth';

	/**
	 * @var string
	 */
	public const REQUIREMENT_LIMITED_WIDTH = 'LimitedWidth';

	/**
	 * @var string
	 */
	public const PREF_KEY_LIMITED_WIDTH = 'vector-limited-width';

	/**
	 * @var string
	 */
	public const FEATURE_LIMITED_WIDTH_CONTENT = 'LimitedWidthContent';

	/**
	 * @var string
	 */
	public const REQUIREMENT_LIMITED_WIDTH_CONTENT = 'LimitedWidthContent';

	/**
	 * @var bool
	 */
	public const CONFIG_DEFAULT_LIMITED_WIDTH = 1;

	/**
	 * @var string
	 */
	public const PREF_KEY_FONT_SIZE = 'vector-font-size';

	/**
	 * @var string
	 */
	public const FEATURE_FONT_SIZE = 'CustomFontSize';

	/**
	 * @var string
	 */
	public const REQUIREMENT_FONT_SIZE = 'CustomFontSize';

	/**
	 * @var string
	 */
	public const FEATURE_CLIENT_PREFS_PINNED = 'ClientPrefsPinned';

	/**
	 * @var string
	 */
	public const REQUIREMENT_CLIENT_PREFS_PINNED = 'ClientPrefsPinned';

	/**
	 * @var string
	 */
	public const PREF_KEY_CLIENT_PREFS_PINNED = 'vector-client-prefs-pinned';

	/**
	 * @var string
	 */
	public const CONFIG_KEY_CLIENT_PREFERENCES = 'VectorClientPreferences';

	/**
	 * @var string
	 */
	public const FEATURE_CLIENT_PREFERENCES = 'ClientPreferences';

	/**
	 * @var string
	 */
	public const REQUIREMENT_CLIENT_PREFERENCES = 'ClientPreferences';

	/**
	 * @var string
	 */
	public const CONFIG_KEY_NIGHT_MODE = 'VectorNightMode';

	/**
	 * @var string
	 */
	public const FEATURE_NIGHT_MODE = 'NightMode';

	/**
	 * @var string
	 */
	public const REQUIREMENT_NIGHT_MODE = 'NightMode';

	/**
	 * @var string
	 */
	public const PREF_KEY_NIGHT_MODE = 'vector-theme';

	/**
	 * @var string
	 */
	public const REQUIREMENT_PREF_NIGHT_MODE = 'PrefNightMode';

	/**
	 * @var string
	 */
	public const PREF_NIGHT_MODE = 'PrefNightMode';

	/**
	 * @var string
	 */
	public const VECTOR_2022_BETA_KEY = 'vector-2022-beta-feature';

	/**
	 * @var array
	 */
	public const VECTOR_BETA_FEATURES = [
		self::CONFIG_KEY_CLIENT_PREFERENCES,
	];

	/**
	 * This class is for namespacing constants only. Forbid construction.
	 * @throws FatalError
	 * @return never
	 */
	private function __construct() {
		throw new FatalError( "Cannot construct a utility class." );
	}
}
