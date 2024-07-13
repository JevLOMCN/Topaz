<?php

namespace MediaWiki\Skins\Vector;

use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderSiteModulePagesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderSiteStylesModulePagesHook;
use MediaWiki\Skins\Hook\SkinPageReadyConfigHook;
use MediaWiki\Skins\Vector\Hooks\HookRunner;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\User;
use RequestContext;
use RuntimeException;
use SkinTemplate;

/**
 * Presentation hook handlers for Vector skin.
 *
 * Hook handler method names should be in the form of:
 *	on<HookName>()
 * @package Vector
 * @internal
 */
class Hooks implements
	GetPreferencesHook,
	LocalUserCreatedHook,
	ResourceLoaderSiteModulePagesHook,
	ResourceLoaderSiteStylesModulePagesHook,
	SkinPageReadyConfigHook
{
	private Config $config;
	private UserOptionsManager $userOptionsManager;

	public function __construct(
		Config $config,
		UserOptionsManager $userOptionsManager
	) {
		$this->config = $config;
		$this->userOptionsManager = $userOptionsManager;
	}

	/**
	 * Checks if the current skin is a variant of Vector
	 *
	 * @param string $skinName
	 * @return bool
	 */
	private static function isVectorSkin( string $skinName ): bool {
		return (
			$skinName === Constants::SKIN_NAME_LEGACY ||
			$skinName === Constants::SKIN_NAME_MODERN
		);
	}

	/**
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function getActiveABTest(
		RL\Context $context,
		Config $config
	) {
		$ab = $config->get(
			Constants::CONFIG_WEB_AB_TEST_ENROLLMENT
		);
		if ( count( $ab ) === 0 ) {
			// If array is empty then no experiment and need to validate.
			return $ab;
		}
		if ( !array_key_exists( 'buckets', $ab ) ) {
			throw new RuntimeException( 'Invalid VectorWebABTestEnrollment value: Must contain buckets key.' );
		}
		if ( !array_key_exists( 'unsampled', $ab['buckets'] ) ) {
			throw new RuntimeException( 'Invalid VectorWebABTestEnrollment value: Must define an `unsampled` bucket.' );
		} else {
			// check bucket values.
			foreach ( $ab['buckets'] as $bucketName => $bucketDefinition ) {
				if ( !is_array( $bucketDefinition ) ) {
					throw new RuntimeException( 'Invalid VectorWebABTestEnrollment value: Buckets should be arrays' );
				}
				$samplingRate = $bucketDefinition['samplingRate'];
				if ( is_string( $samplingRate ) ) {
					throw new RuntimeException(
						'Invalid VectorWebABTestEnrollment value: Sampling rate should be number between 0 and 1.'
					);
				}
			}
		}

		return $ab;
	}

	/**
	 * Generates config variables for skins.vector.search Resource Loader module (defined in
	 * skin.json).
	 *
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array<string,mixed>
	 */
	public static function getVectorSearchResourceLoaderConfig(
		RL\Context $context,
		Config $config
	): array {
		$vectorSearchConfig = [
			'highlightQuery' =>
				VectorServices::getLanguageService()->canWordsBeSplitSafely( $context->getLanguage() )
		];

		$hookRunner = new HookRunner( MediaWikiServices::getInstance()->getHookContainer() );
		$hookRunner->onVectorSearchResourceLoaderConfig( $vectorSearchConfig );

		return array_merge( $config->get( 'VectorWvuiSearchOptions' ), $vectorSearchConfig );
	}

	/**
	 * SkinPageReadyConfig hook handler
	 *
	 * Replace searchModule provided by skin.
	 *
	 * @since 1.35
	 * @param RL\Context $context
	 * @param mixed[] &$config Associative array of configurable options
	 * @return void This hook must not abort, it must return no value
	 */
	public function onSkinPageReadyConfig(
		RL\Context $context,
		array &$config
	): void {
		// It's better to exit before any additional check
		if ( !self::isVectorSkin( $context->getSkin() ) ) {
			return;
		}

		// Tell the `mediawiki.page.ready` module not to wire up search.
		// This allows us to use the new Vue implementation.
		// Context has no knowledge of legacy / modern Vector
		// and from its point of view they are the same thing.
		// Please see the modules `skins.vector.js` and `skins.vector.legacy.js`
		// for the wire up of search.
		$config['search'] = false;
	}

	/**
	 * Moves watch item from actions to views menu.
	 *
	 * @internal used inside Hooks::onSkinTemplateNavigation
	 * @param array &$content_navigation
	 */
	private static function updateActionsMenu( &$content_navigation ) {
		$key = null;
		if ( isset( $content_navigation['actions']['watch'] ) ) {
			$key = 'watch';
		}
		if ( isset( $content_navigation['actions']['unwatch'] ) ) {
			$key = 'unwatch';
		}

		// Promote watch link from actions to views and add an icon
		// The second check to isset is pointless but shuts up phan.
		if ( $key !== null && isset( $content_navigation['actions'][ $key ] ) ) {
			$content_navigation['views'][$key] = $content_navigation['actions'][$key];
			unset( $content_navigation['actions'][$key] );
		}
	}

	/**
	 * Adds icons to items in the "views" menu.
	 *
	 * @internal used inside Hooks::onSkinTemplateNavigation
	 * @param array &$content_navigation
	 * @param bool $isLegacy is this the legacy Vector skin?
	 */
	private static function updateViewsMenuIcons( &$content_navigation, $isLegacy ) {
		// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
		foreach ( $content_navigation['views'] as &$item ) {
			$icon = $item['icon'] ?? null;
			if ( $icon ) {
				if ( $isLegacy ) {
					self::appendClassToItem(
						$item['class'],
						[ 'icon' ]
					);
				} else {
					// Force the item as a button with hidden text.
					$item['button'] = true;
					$item['text-hidden'] = true;
					$item = self::updateMenuItemData( $item, false );
				}
			} elseif ( !$isLegacy ) {
				// The vector-tab-noicon class is only used in Vector-22.
				self::appendClassToItem(
					$item['class'],
					[ 'vector-tab-noicon' ]
				);
			}
		}
	}

	/**
	 * All associated pages menu items do not have icons so are given the vector-tab-noicon class.
	 *
	 * @internal used inside Hooks::onSkinTemplateNavigation
	 * @param array &$content_navigation
	 */
	private static function updateAssociatedPagesMenuIcons( &$content_navigation ) {
		foreach ( $content_navigation['associated-pages'] as &$item ) {
			self::appendClassToItem(
				$item['class'],
				[ 'vector-tab-noicon' ]
			);
		}
	}

	/**
	 * Adds class to a property
	 *
	 * @param array|string &$item to update
	 * @param array|string $classes to add to the item
	 */
	private static function appendClassToItem( &$item, $classes ) {
		$existingClasses = $item;

		if ( is_array( $existingClasses ) ) {
			// Treat as array
			$newArrayClasses = is_array( $classes ) ? $classes : [ trim( $classes ) ];
			$item = array_merge( $existingClasses, $newArrayClasses );
		} elseif ( is_string( $existingClasses ) ) {
			// Treat as string
			$newStrClasses = is_string( $classes ) ? trim( $classes ) : implode( ' ', $classes );
			$item .= ' ' . $newStrClasses;
		} else {
			// Treat as whatever $classes is
			$item = $classes;
		}

		if ( is_string( $item ) ) {
			$item = trim( $item );
		}
	}

	/**
	 * Updates personal navigation menu (user links) dropdown for modern Vector:
	 *  - Adds icons
	 *  - Makes user page and watchlist collapsible
	 *
	 * @internal used inside ::updateUserLinksItems
	 * @param SkinTemplate $sk
	 * @param array &$content_navigation
	 * @suppress PhanTypeInvalidDimOffset
	 */
	private static function updateUserLinksDropdownItems( $sk, &$content_navigation ) {
		// For logged-in users in modern Vector, rearrange some links in the personal toolbar.
		$user = $sk->getUser();
		if ( $user->isRegistered() ) {
			// Remove user page from personal menu dropdown for logged in use
			$content_navigation['user-menu']['userpage']['collapsible'] = true;
			// watchlist may be disabled if $wgGroupPermissions['*']['viewmywatchlist'] = false;
			// See [[phab:T299671]]
			if ( isset( $content_navigation['user-menu']['watchlist'] ) ) {
				$content_navigation['user-menu']['watchlist']['collapsible'] = true;
			}

			// Anon editor links handled manually in new anon editor menu
			$logoutMenu = [];
			if ( isset( $content_navigation['user-menu']['logout'] ) ) {
				$logoutMenu['logout'] = $content_navigation['user-menu']['logout'];
				$logoutMenu['logout']['id'] = 'pt-logout';
				unset( $content_navigation['user-menu']['logout'] );
			}
			$content_navigation['user-menu-logout'] = $logoutMenu;

			self::updateMenuItems( $content_navigation, 'user-menu' );
			self::updateMenuItems( $content_navigation, 'user-menu-logout' );
		} else {
			// Remove "Not logged in" from personal menu dropdown for anon users.
			unset( $content_navigation['user-menu']['anonuserpage'] );

			// Make login and create account collapsible
			if ( isset( $content_navigation['user-menu']['login'] ) ) {
				$content_navigation['user-menu']['login']['collapsible'] = true;
			}
			if ( isset( $content_navigation['user-menu']['login-private'] ) ) {
				$content_navigation['user-menu']['login-private']['collapsible'] = true;
			}
			if ( isset( $content_navigation['user-menu']['createaccount'] ) ) {
				$content_navigation['user-menu']['createaccount']['collapsible'] = true;
			}

			// Anon editor links handled manually in new anon editor menu
			$anonEditorMenu = [];
			if ( isset( $content_navigation['user-menu']['anoncontribs'] ) ) {
				$anonEditorMenu['anoncontribs'] = $content_navigation['user-menu']['anoncontribs'];
				$anonEditorMenu['anoncontribs']['id'] = 'pt-anoncontribs';
				unset( $content_navigation['user-menu']['anoncontribs'] );
			}
			if ( isset( $content_navigation['user-menu']['anontalk'] ) ) {
				$anonEditorMenu['anontalk'] = $content_navigation['user-menu']['anontalk'];
				$anonEditorMenu['anontalk']['id'] = 'pt-anontalk';
				unset( $content_navigation['user-menu']['anontalk'] );
			}
			$content_navigation['user-menu-anon-editor'] = $anonEditorMenu;

			// Only show icons for anon menu items (login and create account).
			self::updateMenuItems( $content_navigation, 'user-menu' );
		}
	}

	/**
	 * Echo has styles that control icons rendering in places we don't want them.
	 * This code works around T343838.
	 *
	 * @param SkinTemplate $sk
	 * @param array &$content_navigation
	 */
	private static function fixEcho( $sk, &$content_navigation ) {
		if ( isset( $content_navigation['notifications'] ) ) {
			foreach ( $content_navigation['notifications'] as &$item ) {
				$icon = $item['icon'] ?? null;
				if ( $icon ) {
					$linkClass = $item['link-class'] ?? [];
					$newLinkClass = [
						// Allows Echo to react to clicks
						'mw-echo-notification-badge-nojs'
					];
					if ( in_array( 'mw-echo-unseen-notifications', $linkClass ) ) {
						$newLinkClass[] = 'mw-echo-unseen-notifications';
					}
					$item['link-class'] = $newLinkClass;
				}
			}
		}
	}

	/**
	 * Updates personal navigation menu (user links) for modern Vector wherein user page, create account and login links
	 * are removed from the dropdown to be handled separately. In legacy Vector, the custom "user-page" bucket is
	 * removed to preserve existing behavior.
	 *
	 * @internal used inside Hooks::onSkinTemplateNavigation
	 * @param SkinTemplate $sk
	 * @param array &$content_navigation
	 */
	private static function updateUserLinksItems( $sk, &$content_navigation ) {
		$skinName = $sk->getSkinName();
		if ( self::isSkinVersionLegacy( $skinName ) ) {
			// Remove user page from personal toolbar since it will be inside the personal menu for logged-in
			// users in legacy Vector.
			unset( $content_navigation['user-page'] );
		} else {
			self::fixEcho( $sk, $content_navigation );
			self::updateUserLinksDropdownItems( $sk, $content_navigation );
		}
	}

	/**
	 * Modifies list item to make it collapsible.
	 *
	 * @internal used in ::updateItemData and ::createMoreOverflowMenu
	 * @param array &$item
	 * @param string $prefix defaults to user-links-
	 */
	private static function makeMenuItemCollapsible( array &$item, string $prefix = 'user-links-' ) {
		$collapseMenuItemClass = $prefix . 'collapsible-item';
		self::appendClassToItem( $item[ 'class' ], $collapseMenuItemClass );
	}

	/**
	 * Make an icon
	 *
	 * @internal for use inside Vector skin.
	 * @param string $name
	 * @return string of HTML
	 */
	private static function makeIcon( $name ) {
		// Html::makeLink will pass this through rawElement
		return '<span class="vector-icon mw-ui-icon-' . $name . ' mw-ui-icon-wikimedia-' . $name . '"></span>';
	}

	/**
	 * Update template data to include classes and html that handle buttons, icons, and collapsible items.
	 *
	 * @internal used in ::updateMenuItemData
	 * @param array $item data to update
	 * @param string $buttonClassProp property to append button classes
	 * @param string $iconHtmlProp property to set icon HTML
	 * @param bool $unsetIcon should the icon field be unset?
	 * @return array $item Updated data
	 */
	private static function updateItemData(
		$item, $buttonClassProp, $iconHtmlProp, $unsetIcon = true
	) {
		$hasButton = $item['button'] ?? false;
		$hideText = $item['text-hidden'] ?? false;
		$isCollapsible = $item['collapsible'] ?? false;
		$icon = $item['icon'] ?? '';
		if ( $unsetIcon ) {
			unset( $item['icon'] );
		}
		unset( $item['button'] );
		unset( $item['text-hidden'] );
		unset( $item['collapsible'] );

		if ( $isCollapsible ) {
			self::makeMenuItemCollapsible( $item );
		}
		if ( $hasButton ) {
			// Hardcoded button classes, this should be fixed by replacing Hooks.php with VectorComponentButton.php
			self::appendClassToItem( $item[ $buttonClassProp ], [
				'cdx-button',
				'cdx-button--fake-button',
				'cdx-button--fake-button--enabled',
				'cdx-button--weight-quiet'
			] );
		}
		if ( $icon ) {
			if ( $hideText && $hasButton ) {
				self::appendClassToItem( $item[ $buttonClassProp ], [ 'cdx-button--icon-only' ] );
			}

			$item[ $iconHtmlProp ] = self::makeIcon( $icon );
		}
		return $item;
	}

	/**
	 * Updates template data for Vector menu items.
	 *
	 * @internal used inside Hooks::updateMenuItems ::updateViewsMenuIcons and ::updateUserLinksDropdownItems
	 * @param array $item menu item data to update
	 * @param bool $unsetIcon should the icon field be unset?
	 * @return array $item Updated menu item data
	 */
	private static function updateMenuItemData( $item, $unsetIcon = true ) {
		$buttonClassProp = 'link-class';
		$iconHtmlProp = 'link-html';
		return self::updateItemData( $item, $buttonClassProp, $iconHtmlProp, $unsetIcon );
	}

	/**
	 * Updates user interface preferences for modern Vector to upgrade icon/button menu items.
	 *
	 * @param array &$content_navigation
	 * @param string $menu identifier
	 */
	private static function updateMenuItems( &$content_navigation, $menu ) {
		foreach ( $content_navigation[$menu] as &$item ) {
			$item = self::updateMenuItemData( $item );
		}
	}

	/**
	 * Vector 2022 only:
	 * Creates an additional menu that will be injected inside the more (cactions)
	 * dropdown menu. This menu is a clone of `views` and this menu will only be
	 * shown at low resolutions (when the `views` menu is hidden).
	 *
	 * An additional menu is used instead of adding to the existing cactions menu
	 * so that the emptyPortlet logic for that menu is preserved and the cactions menu
	 * is not shown at large resolutions when empty (e.g. all items including collapsed
	 * items are hidden).
	 *
	 * @param array &$content_navigation
	 */
	private static function createMoreOverflowMenu( &$content_navigation ) {
		$clonedViews = [];
		foreach ( $content_navigation['views'] ?? [] as $key => $item ) {
			$newItem = $item;
			self::makeMenuItemCollapsible(
				$newItem,
				'vector-more-'
			);
			$clonedViews['more-' . $key] = $newItem;
		}
		// Inject collapsible menu items ahead of existing actions.
		$content_navigation['views-overflow'] = $clonedViews;
	}

	/**
	 * Upgrades Vector's watch action to a watchstar.
	 * This is invoked inside SkinVector, not via skin registration, as skin hooks
	 * are not guaranteed to run last.
	 * This can possibly be revised based on the outcome of T287622.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 * @param SkinTemplate $sk
	 * @param array &$content_navigation
	 */
	public static function onSkinTemplateNavigation( $sk, &$content_navigation ) {
		$skinName = $sk->getSkinName();
		// These changes should only happen in Vector.
		if ( !$skinName || !self::isVectorSkin( $skinName ) ) {
			return;
		}

		$title = $sk->getRelevantTitle();
		if (
			$sk->getConfig()->get( 'VectorUseIconWatch' ) &&
			$title && $title->canExist()
		) {
			self::updateActionsMenu( $content_navigation );
		}

		self::updateUserLinksItems( $sk, $content_navigation );
		if ( $skinName === Constants::SKIN_NAME_MODERN ) {
			self::createMoreOverflowMenu( $content_navigation );
		}

		// The updating of the views menu happens /after/ the overflow menu has been created
		// this avoids icons showing in the more overflow menu.
		self::updateViewsMenuIcons( $content_navigation, self::isSkinVersionLegacy( $skinName ) );
		self::updateAssociatedPagesMenuIcons( $content_navigation );
	}

	/**
	 * Adds MediaWiki:Vector.css as the skin style that controls classic Vector.
	 *
	 * @param string $skin
	 * @param array &$pages
	 */
	public function onResourceLoaderSiteStylesModulePages( $skin, &$pages ): void {
		if ( $skin === Constants::SKIN_NAME_MODERN && $this->config->get( 'VectorShareUserScripts' ) ) {
			$pages['MediaWiki:Vector.css'] = [ 'type' => 'style' ];
		}
	}

	/**
	 * Adds MediaWiki:Vector.css as the skin style that controls classic Vector.
	 *
	 * @param string $skin
	 * @param array &$pages
	 */
	public function onResourceLoaderSiteModulePages( $skin, &$pages ): void {
		if ( $skin === Constants::SKIN_NAME_MODERN && $this->config->get( 'VectorShareUserScripts' ) ) {
			$pages['MediaWiki:Vector.js'] = [ 'type' => 'script' ];
		}
	}

	/**
	 * Adds Vector specific user preferences that can only be accessed via API.
	 *
	 * @param User $user User whose preferences are being modified.
	 * @param array[] &$prefs Preferences description array, to be fed to a HTMLForm object.
	 */
	public function onGetPreferences( $user, &$prefs ): void {
		$vectorPrefs = [
			Constants::PREF_KEY_FONT_SIZE => [
				'type' => 'api'
			],
			Constants::PREF_KEY_PAGE_TOOLS_PINNED => [
				'type' => 'api'
			],
			Constants::PREF_KEY_MAIN_MENU_PINNED => [
				'type' => 'api'
			],
			Constants::PREF_KEY_TOC_PINNED => [
				'type' => 'api'
			],
			Constants::PREF_KEY_CLIENT_PREFS_PINNED => [
				'type' => 'api'
			],
			Constants::PREF_KEY_LIMITED_WIDTH => [
				'type' => 'toggle',
				'label-message' => 'vector-prefs-limited-width',
				'section' => 'rendering/skin/skin-prefs',
				'help-message' => 'vector-prefs-limited-width-help',
				'hide-if' => [ '!==', 'skin', Constants::SKIN_NAME_MODERN ],
			],
			Constants::PREF_KEY_NIGHT_MODE => [
				'type' => 'api'
			],
		];
		$prefs += $vectorPrefs;
	}

	/**
	 * Called one time when initializing a users preferences for a newly created account.
	 *
	 * @param User $user Newly created user object.
	 * @param bool $isAutoCreated
	 */
	public function onLocalUserCreated( $user, $isAutoCreated ) {
		$default = $this->config->get( Constants::CONFIG_KEY_DEFAULT_SKIN_VERSION_FOR_NEW_ACCOUNTS );
		if ( $default ) {
			$this->userOptionsManager->setOption(
				$user,
				Constants::PREF_KEY_SKIN,
				$default === Constants::SKIN_VERSION_LEGACY ?
					Constants::SKIN_NAME_LEGACY : Constants::SKIN_NAME_MODERN
			);
		}
	}

	/**
	 * Gets whether the current skin version is the legacy version.
	 *
	 * @param string $skinName hint that can be used to detect modern vector.
	 * @return bool
	 */
	private static function isSkinVersionLegacy( $skinName ): bool {
		return $skinName === Constants::SKIN_NAME_LEGACY;
	}

	/**
	 * Register Vector 2022 beta feature to the beta features list
	 *
	 * @param User $user User the preferences are for
	 * @param array &$betaFeatures
	 */
	public function onGetBetaFeaturePreferences( User $user, array &$betaFeatures ) {
		$skinName = RequestContext::getMain()->getSkinName();
		// Only Vector 2022 is supported for beta features
		if ( $skinName !== Constants::SKIN_NAME_MODERN ) {
			return;
		}
		// Only add Vector 2022 beta feature if there is at least one beta feature present in config
		$configHasBeta = false;
		foreach ( Constants::VECTOR_BETA_FEATURES as $featureName ) {
			if ( $this->config->has( $featureName ) && $this->config->get( $featureName )[ 'beta' ] === true ) {
				$configHasBeta = true;
				break;
			}
		}
		if ( !$configHasBeta ) {
			return;
		}
		$skinsAssetsPath = $this->config->get( 'StylePath' );
		$imagesDir = "$skinsAssetsPath/Vector/resources/images";
		$betaFeatures[ Constants::VECTOR_2022_BETA_KEY ] = [
			'label-message' => 'vector-2022-beta-preview-label',
			'desc-message' => 'vector-2022-beta-preview-description',
			'screenshot' => [
				// follow up work to add images is required in T349321
				'ltr' => "$imagesDir/vector-2022-beta-preview-ltr.svg",
				'rtl' => "$imagesDir/vector-2022-beta-preview-rtl.svg",
			],
			'info-link' => 'https://www.mediawiki.org/wiki/Special:MyLanguage/Reading/Web/Accessibility_for_reading',
			'discussion-link' => 'https://www.mediawiki.org/wiki/Talk:Reading/Web/Accessibility_for_reading',
		];
	}
}
