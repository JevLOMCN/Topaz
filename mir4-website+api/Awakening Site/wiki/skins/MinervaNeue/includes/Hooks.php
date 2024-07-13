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

use ChangesList;
use ChangesListFilterGroup;
use DifferenceEngine;
use ExtensionRegistry;
use MediaWiki\Config\Config;
use MediaWiki\Diff\Hook\DifferenceEngineViewHeaderHook;
use MediaWiki\Hook\FetchChangesListHook;
use MediaWiki\Hook\OutputPageBodyAttributesHook;
use MediaWiki\Hook\PreferencesGetLayoutHook;
use MediaWiki\Hook\UserLogoutCompleteHook;
use MediaWiki\Html\Html;
use MediaWiki\Minerva\Skins\SkinMinerva;
use MediaWiki\Output\OutputPage;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Skins\Hook\SkinPageReadyConfigHook;
use MediaWiki\SpecialPage\Hook\SpecialPageBeforeExecuteHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use MobileContext;
use OldChangesList;
use Skin;
use Wikimedia\Rdbms\ConfiguredReadOnlyMode;

/**
 * Hook handlers for Minerva skin.
 *
 * Hook handler method names should be in the form of:
 *	on<HookName>()
 */
class Hooks implements
	DifferenceEngineViewHeaderHook,
	FetchChangesListHook,
	GetPreferencesHook,
	OutputPageBodyAttributesHook,
	PreferencesGetLayoutHook,
	ResourceLoaderGetConfigVarsHook,
	ResourceLoaderRegisterModulesHook,
	SkinPageReadyConfigHook,
	SpecialPageBeforeExecuteHook,
	UserLogoutCompleteHook
{
	public const FEATURE_OVERFLOW_PAGE_ACTIONS = 'MinervaOverflowInPageActions';

	private ConfiguredReadOnlyMode $configuredReadOnlyMode;
	private SkinOptions $skinOptions;
	private UserOptionsLookup $userOptionsLookup;
	private ?MobileContext $mobileContext;

	public function __construct(
		ConfiguredReadOnlyMode $configuredReadOnlyMode,
		SkinOptions $skinOptions,
		UserOptionsLookup $userOptionsLookup,
		?MobileContext $mobileContext
	) {
		$this->configuredReadOnlyMode = $configuredReadOnlyMode;
		$this->skinOptions = $skinOptions;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->mobileContext = $mobileContext;
	}

	/**
	 * ResourceLoaderRegisterModules hook handler.
	 *
	 * Registers:
	 *
	 * * EventLogging schema modules, if the EventLogging extension is loaded;
	 * * Modules for the Visual Editor overlay, if the VisualEditor extension is loaded; and
	 * * Modules for the notifications overlay, if the Echo extension is loaded.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderRegisterModules
	 *
	 * @param ResourceLoader $resourceLoader
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			$resourceLoader->register( [
				'mobile.startup' => [
					'dependencies' => [ 'mediawiki.searchSuggest' ],
					'localBasePath' => dirname( __DIR__ ),
					'remoteExtPath' => 'Minerva',
					'scripts' => 'resources/mobile.startup.stub.js',
				]
			] );
		}
	}

	/**
	 * Adds Minerva-specific user preferences that can only be accessed via API
	 *
	 * @param User $user user whose preferences are being modified
	 * @param array[] &$prefs preferences description array, to be fed to a HTMLForm object
	 */
	public function onGetPreferences( $user, &$prefs ): void {
		$minervaPrefs = [
			'minerva-theme' => [
				'type' => 'api'
			],
		];

		$prefs += $minervaPrefs;
	}

	/**
	 * PreferencesGetLayout hook handler.
	 *
	 * Use mobile layout in Special:Preferences
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PreferencesGetLayout
	 *
	 * @param bool &$useMobileLayout
	 * @param string $skinName
	 * @param array $skinProperties
	 */
	public function onPreferencesGetLayout( &$useMobileLayout, $skinName, $skinProperties = [] ) {
		if ( $skinName === 'minerva' ) {
			$useMobileLayout = true;
		}
	}

	/**
	 * Disable recent changes enhanced mode (table mode)
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FetchChangesList
	 *
	 * @param User $user
	 * @param Skin $skin
	 * @param ChangesList|null &$list
	 * @param ChangesListFilterGroup[] $groups
	 * @return bool|null
	 */
	public function onFetchChangesList( $user, $skin, &$list, $groups ) {
		if ( $skin->getSkinName() === 'minerva' ) {
			// The new changes list (table-based) does not work with Minerva
			$list = new OldChangesList( $skin->getContext(), $groups );
			// returning false makes sure $list is used instead.
			return false;
		}
	}

	/**
	 * Invocation of hook SpecialPageBeforeExecute
	 *
	 * We use this hook to ensure that login/account creation pages
	 * are redirected to HTTPS if they are not accessed via HTTPS and
	 * $wgSecureLogin == true - but only when using the
	 * mobile site.
	 *
	 * @param SpecialPage $special
	 * @param string $subpage
	 */
	public function onSpecialPageBeforeExecute( $special, $subpage ) {
		$name = $special->getName();
		if ( !in_array( $name, [ 'Recentchanges', 'Userlogin', 'CreateAccount' ] ) ) {
			return;
		}
		$skin = $special->getSkin();
		if ( !$skin instanceof SkinMinerva ) {
			return;
		}
		$request = $special->getRequest();
		if ( $name === 'Recentchanges' ) {
			$isEnhancedDefaultForUser = $this->userOptionsLookup
				->getBoolOption( $special->getUser(), 'usenewrc' );
			$enhanced = $request->getBool( 'enhanced', $isEnhancedDefaultForUser );
			if ( $enhanced ) {
				$special->getOutput()->addHTML( Html::warningBox(
					$special->msg( 'skin-minerva-recentchanges-warning-enhanced-not-supported' )->parse()
				) );
			}
		} else {
			// Add default warning message to Special:UserLogin and Special:UserCreate
			// if no warning message set.
			if (
				!$request->getVal( 'warning' ) &&
				!$special->getUser()->isRegistered() &&
				!$request->wasPosted()
			) {
				$request->setVal( 'warning', 'mobile-frontend-generic-login-new' );
			}
		}
	}

	/**
	 * UserLogoutComplete hook handler.
	 * Resets skin options if a user logout occurs - this is necessary as the
	 * RequestContextCreateSkinMobile hook runs before the UserLogout hook.
	 *
	 * @param User $user
	 * @param string &$inject_html
	 * @param string $oldName
	 */
	public function onUserLogoutComplete( $user, &$inject_html, $oldName ) {
		if ( $this->mobileContext ) {
			$this->skinOptions->setMinervaSkinOptions( $this->mobileContext, $this->mobileContext->getSkin() );
		}
	}

	/**
	 * ResourceLoaderGetConfigVars hook handler.
	 * Used for setting JS variables which are pulled in dynamically with RL
	 * instead of embedded directly on the page with a script tag.
	 * These vars have a shorter cache-life than those in `getJsConfigVars`.
	 *
	 * @param array &$vars Array of variables to be added into the output of the RL startup module.
	 * @param string $skin
	 * @param Config $config
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		if ( $skin === 'minerva' ) {
			// This is to let the UI adjust itself to a wiki that is always read-only.
			// Ignore temporary read-only on live wikis, requires heavy DB check (T233458).
			$roConf = $this->configuredReadOnlyMode;
			$vars += [
				'wgMinervaABSamplingRate' => $config->get( 'MinervaABSamplingRate' ),
				'wgMinervaReadOnly' => $roConf->isReadOnly(),
			];
		}
	}

	/**
	 * Modifies the `<body>` element's attributes.
	 *
	 * By default, the `class` attribute is set to the output's "bodyClassName"
	 * property.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param string[] &$bodyAttrs
	 */
	public function onOutputPageBodyAttributes( $out, $skin, &$bodyAttrs ): void {
		$classes = $out->getProperty( 'bodyClassName' );
		$isMinerva = $skin instanceof SkinMinerva;

		if ( $isMinerva && $this->skinOptions->get( SkinOptions::HISTORY_IN_PAGE_ACTIONS ) ) {
			// Class is used when page actions is modified to contain more elements
			$classes .= ' minerva--history-page-action-enabled';
		}

		if ( $isMinerva ) {
			$bodyAttrs['class'] .= ' ' . $classes;
		}
	}

	/**
	 * SkinPageReadyConfig hook handler
	 *
	 * Disable collapsible on page load
	 *
	 * @param Context $context
	 * @param mixed[] &$config Associative array of configurable options
	 */
	public function onSkinPageReadyConfig(
		Context $context,
		array &$config
	): void {
		if ( $context->getSkin() === 'minerva' ) {
			$config['search'] = false;
			$config['collapsible'] = false;
			$config['selectorLogoutLink'] = 'a.menu__item--logout[data-mw="interface"]';
		}
	}

	/**
	 * Force inline diffs on mobile site.
	 *
	 * @param DifferenceEngine $differenceEngine
	 */
	public function onDifferenceEngineViewHeader( $differenceEngine ) {
		$skin = $differenceEngine->getSkin();
		if ( $skin->getSkinName() !== 'minerva' ) {
			return;
		}
		$differenceEngine->setSlotDiffOptions( [
			'diff-type' => 'inline',
		] );
	}
}
