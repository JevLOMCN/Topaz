<?php

namespace MediaWiki\Skins\Vector;

use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\Skins\Vector\Components\VectorComponentButton;
use MediaWiki\Skins\Vector\Components\VectorComponentClientPrefs;
use MediaWiki\Skins\Vector\Components\VectorComponentDropdown;
use MediaWiki\Skins\Vector\Components\VectorComponentLanguageDropdown;
use MediaWiki\Skins\Vector\Components\VectorComponentMainMenu;
use MediaWiki\Skins\Vector\Components\VectorComponentPageTools;
use MediaWiki\Skins\Vector\Components\VectorComponentPinnableContainer;
use MediaWiki\Skins\Vector\Components\VectorComponentSearchBox;
use MediaWiki\Skins\Vector\Components\VectorComponentStickyHeader;
use MediaWiki\Skins\Vector\Components\VectorComponentTableOfContents;
use MediaWiki\Skins\Vector\Components\VectorComponentUserLinks;
use MediaWiki\Skins\Vector\Components\VectorComponentVariants;
use MediaWiki\Skins\Vector\FeatureManagement\FeatureManager;
use MediaWiki\Skins\Vector\FeatureManagement\FeatureManagerFactory;
use RuntimeException;
use SkinMustache;
use SkinTemplate;

/**
 * @ingroup Skins
 * @package Vector
 * @internal
 */
class SkinVector22 extends SkinMustache {
	private const STICKY_HEADER_ENABLED_CLASS = 'vector-sticky-header-enabled';
	/** @var null|array for caching purposes */
	private $languages;

	private FeatureManagerFactory $featureManagerFactory;
	private ?FeatureManager $featureManager = null;

	public function __construct(
		FeatureManagerFactory $featureManagerFactory,
		array $options
	) {
		parent::__construct( $options );

		// Cannot use the context in the constructor, setContext is called after construction
		$this->featureManagerFactory = $featureManagerFactory;
	}

	/**
	 * @inheritDoc
	 */
	protected function runOnSkinTemplateNavigationHooks( SkinTemplate $skin, &$content_navigation ) {
		parent::runOnSkinTemplateNavigationHooks( $skin, $content_navigation );
		Hooks::onSkinTemplateNavigation( $skin, $content_navigation );
	}

	/**
	 * @inheritDoc
	 */
	public function isResponsive() {
		// Check it's enabled by user preference and configuration
		$responsive = parent::isResponsive() && $this->getConfig()->get( 'VectorResponsive' );
		// For historic reasons, the viewport is added when Vector is loaded on the mobile
		// domain. This is only possible for 3rd parties or by useskin parameter as there is
		// no preference for changing mobile skin. Only need to check if $responsive is falsey.
		if ( !$responsive && ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			$mobFrontContext = MediaWikiServices::getInstance()->getService( 'MobileFrontend.Context' );
			if ( $mobFrontContext->shouldDisplayMobileView() ) {
				return true;
			}
		}
		return $responsive;
	}

	/**
	 * Whether or not toc data is available
	 *
	 * @param array $parentData Template data
	 * @return bool
	 */
	private function isTocAvailable( array $parentData ): bool {
		return !empty( $parentData['data-toc'][ 'array-sections' ] );
	}

	/**
	 * This should be upstreamed to the Skin class in core once the logic is finalized.
	 * Returns false if the page is a special page without any languages, or if an action
	 * other than view is being used.
	 *
	 * @return bool
	 */
	private function canHaveLanguages(): bool {
		$action = $this->getActionName();

		// FIXME: This logic should be moved into the ULS extension or core given the button is hidden,
		// it should not be rendered, short term fix for T328996.
		if ( $action === 'history' ) {
			return false;
		}

		$title = $this->getTitle();
		// Defensive programming - if a special page has added languages explicitly, best to show it.
		if ( $title && $title->isSpecialPage() && $this->getLanguagesCached() === [] ) {
			return false;
		}
		return true;
	}

	/**
	 * Remove the add topic button from data-views if present
	 *
	 * @param array &$parentData Template data
	 * @return bool An add topic button was removed
	 */
	private function removeAddTopicButton( array &$parentData ): bool {
		$views = $parentData['data-portlets']['data-views']['array-items'];
		$hasAddTopicButton = false;
		$html = '';
		foreach ( $views as $i => $view ) {
			if ( $view['id'] === 'ca-addsection' ) {
				array_splice( $views, $i, 1 );
				$hasAddTopicButton = true;
				continue;
			}
			$html .= $view['html-item'];
		}
		$parentData['data-portlets']['data-views']['array-items'] = $views;
		$parentData['data-portlets']['data-views']['html-items'] = $html;
		return $hasAddTopicButton;
	}

	private function getFeatureManager(): FeatureManager {
		if ( $this->featureManager === null ) {
			$this->featureManager = $this->featureManagerFactory->createFeatureManager( $this->getContext() );
		}
		return $this->featureManager;
	}

	/**
	 * @param string $location Either 'top' or 'bottom' is accepted.
	 * @return bool
	 */
	protected function isLanguagesInContentAt( string $location ): bool {
		if ( !$this->canHaveLanguages() ) {
			return false;
		}
		$featureManager = $this->getFeatureManager();
		$inContent = $featureManager->isFeatureEnabled(
			Constants::FEATURE_LANGUAGE_IN_HEADER
		);
		$title = $this->getTitle();
		$isMainPage = $title ? $title->isMainPage() : false;

		switch ( $location ) {
			case 'top':
				return $isMainPage ? $inContent && $featureManager->isFeatureEnabled(
					Constants::FEATURE_LANGUAGE_IN_MAIN_PAGE_HEADER
				) : $inContent;
			case 'bottom':
				return $inContent && $isMainPage && !$featureManager->isFeatureEnabled(
					Constants::FEATURE_LANGUAGE_IN_MAIN_PAGE_HEADER
				);
			default:
				throw new RuntimeException( 'unknown language button location' );
		}
	}

	/**
	 * Whether or not the languages are out of the sidebar and in the content either at
	 * the top or the bottom.
	 *
	 * @return bool
	 */
	final protected function isLanguagesInContent(): bool {
		return $this->isLanguagesInContentAt( 'top' ) || $this->isLanguagesInContentAt( 'bottom' );
	}

	/**
	 * Calls getLanguages with caching.
	 *
	 * @return array
	 */
	protected function getLanguagesCached(): array {
		if ( $this->languages === null ) {
			$this->languages = $this->getLanguages();
		}
		return $this->languages;
	}

	/**
	 * Check whether ULS is enabled
	 *
	 * @return bool
	 */
	final protected function isULSExtensionEnabled(): bool {
		return ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
	}

	/**
	 * Check whether Visual Editor Tab Position is first
	 *
	 * @param array $dataViews
	 * @return bool
	 */
	final protected function isVisualEditorTabPositionFirst( $dataViews ): bool {
		$names = [ 've-edit', 'edit' ];
		// find if under key 'name' 've-edit' or 'edit' is the before item in the array
		for ( $i = 0; $i < count( $dataViews[ 'array-items' ] ); $i++ ) {
			if ( in_array( $dataViews[ 'array-items' ][ $i ][ 'name' ], $names ) ) {
				return $dataViews[ 'array-items' ][ $i ][ 'name' ] === $names[ 0 ];
			}
		}
		return false;
	}

	/**
	 * Show the ULS button if it's modern Vector, languages in header is enabled,
	 * the ULS extension is enabled, and we are on a subect page. Hide it otherwise.
	 * There is no point in showing the language button if ULS extension is unavailable
	 * as there is no ways to add languages without it.
	 * @return bool
	 */
	protected function shouldHideLanguages(): bool {
		$title = $this->getTitle();
		$isSubjectPage = $title && $title->exists() && !$title->isTalkPage();
		return !$this->isLanguagesInContent() || !$this->isULSExtensionEnabled() || !$isSubjectPage;
	}

	/**
	 * Merges the `view-overflow` menu into the `action` menu.
	 * This ensures that the previous state of the menu e.g. emptyPortlet class
	 * is preserved.
	 *
	 * @param array $data
	 * @return array
	 */
	private function mergeViewOverflowIntoActions( array $data ): array {
		$portlets = $data['data-portlets'];
		$actions = $portlets['data-actions'];
		$overflow = $portlets['data-views-overflow'];
		// if the views overflow menu is not empty, then signal that the more menu despite
		// being initially empty now has collapsible items.
		if ( !$overflow['is-empty'] ) {
			$data['data-portlets']['data-actions']['class'] .= ' vector-has-collapsible-items';
		}
		$data['data-portlets']['data-actions']['html-items'] = $overflow['html-items'] . $actions['html-items'];
		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function getHtmlElementAttributes() {
		$original = parent::getHtmlElementAttributes();
		$featureManager = $this->getFeatureManager();
		$original['class'] .= ' ' . implode( ' ', $featureManager->getFeatureBodyClass() );

		if ( $featureManager->isFeatureEnabled( Constants::FEATURE_STICKY_HEADER ) ) {
			// T290518: Add scroll padding to root element when the sticky header is
			// enabled. This class needs to be server rendered instead of added from
			// JS in order to correctly handle situations where the sticky header
			// isn't visible yet but we still need scroll padding applied (e.g. when
			// the user navigates to a page with a hash fragment in the URI). For this
			// reason, we can't rely on the `vector-sticky-header-visible` class as it
			// is added too late.
			//
			// Please note that this class applies scroll padding which does not work
			// when applied to the body tag in Chrome, Safari, and Firefox (and
			// possibly others). It must instead be applied to the html tag.
			$original['class'] = implode( ' ', [ $original['class'] ?? '', self::STICKY_HEADER_ENABLED_CLASS ] );
		}
		$original['class'] = trim( $original['class'] );

		return $original;
	}

	/**
	 * Pulls the page tools menu out of $sidebar into $pageToolsMenu
	 *
	 * @param array &$sidebar
	 * @param array &$pageToolsMenu
	 */
	private static function extractPageToolsFromSidebar( array &$sidebar, array &$pageToolsMenu ) {
		$restPortlets = $sidebar[ 'array-portlets-rest' ] ?? [];
		$toolboxMenuIndex = array_search(
			VectorComponentPageTools::TOOLBOX_ID,
			array_column(
				$restPortlets,
				'id'
			)
		);

		if ( $toolboxMenuIndex !== false ) {
			// Splice removes the toolbox menu from the $restPortlets array
			// and current returns the first value of array_splice, i.e. the $toolbox menu data.
			$pageToolsMenu = array_splice( $restPortlets, $toolboxMenuIndex );
			$sidebar['array-portlets-rest'] = $restPortlets;
		}
	}

	/**
	 * Get the ULS button label, accounting for the number of available
	 * languages.
	 *
	 * @return array
	 */
	final protected function getULSLabels(): array {
		$numLanguages = count( $this->getLanguagesCached() );

		if ( $numLanguages === 0 ) {
			return [
				'label' => $this->msg( 'vector-no-language-button-label' )->text(),
				'aria-label' => $this->msg( 'vector-no-language-button-aria-label' )->text()
			];
		} else {
			return [
				'label' => $this->msg( 'vector-language-button-label' )->numParams( $numLanguages )->text(),
				'aria-label' => $this->msg( 'vector-language-button-aria-label' )->numParams( $numLanguages )->text()
			];
		}
	}

	/**
	 * @return array
	 */
	public function getTemplateData(): array {
		$parentData = parent::getTemplateData();
		$parentData = $this->mergeViewOverflowIntoActions( $parentData );
		$portlets = $parentData['data-portlets'];

		$langData = $portlets['data-languages'] ?? null;
		$config = $this->getConfig();
		$featureManager = $this->getFeatureManager();

		$sidebar = $parentData[ 'data-portlets-sidebar' ];
		$pageToolsMenu = [];
		self::extractPageToolsFromSidebar( $sidebar, $pageToolsMenu );

		$hasAddTopicButton = $config->get( 'VectorPromoteAddTopic' ) &&
			$this->removeAddTopicButton( $parentData );

		$langButtonClass = $langData['class'] ?? '';
		$ulsLabels = $this->getULSLabels();
		$user = $this->getUser();
		$localizer = $this->getContext();
		$title = $this->getTitle();

		// If the table of contents has no items, we won't output it.
		// empty array is interpreted by Mustache as falsey.
		$tocComponents = [];
		if ( $this->isTocAvailable( $parentData ) ) {
			// @phan-suppress-next-line SecurityCheck-XSS
			$dataToc = new VectorComponentTableOfContents(
				$parentData['data-toc'],
				$localizer,
				$config,
				$featureManager
			);
			$isPinned = $dataToc->isPinned();
			$tocComponents = [
				'data-toc' => $dataToc,
				'data-toc-pinnable-container' => new VectorComponentPinnableContainer(
					VectorComponentTableOfContents::ID,
					$isPinned
				),
				'data-page-titlebar-toc-dropdown' => new VectorComponentDropdown(
					'vector-page-titlebar-toc',
					// label
					$this->msg( 'vector-toc-collapsible-button-label' ),
					// class
					'vector-page-titlebar-toc vector-button-flush-left',
					// icon
					'listBullet',
				),
				'data-page-titlebar-toc-pinnable-container' => new VectorComponentPinnableContainer(
					'vector-page-titlebar-toc',
					$isPinned
				),
				'data-sticky-header-toc-dropdown' => new VectorComponentDropdown(
					'vector-sticky-header-toc',
					// label
					$this->msg( 'vector-toc-collapsible-button-label' ),
					// class
					'mw-portlet mw-portlet-sticky-header-toc vector-sticky-header-toc vector-button-flush-left',
					// icon
					'listBullet'
				),
				'data-sticky-header-toc-pinnable-container' => new VectorComponentPinnableContainer(
					'vector-sticky-header-toc',
					$isPinned
				),
			];
			$this->getOutput()->addHtmlClasses( 'vector-toc-available' );
		} else {
			$this->getOutput()->addHtmlClasses( 'vector-toc-not-available' );
		}

		$isRegistered = $user->isRegistered();
		$userPage = $isRegistered ? $this->buildPersonalPageItem() : [];
		$isClientPreferencesEnabled = $featureManager->isFeatureEnabled(
			Constants::FEATURE_CLIENT_PREFERENCES,
		);

		$components = $tocComponents + [
			'data-add-topic-button' => $hasAddTopicButton ? new VectorComponentButton(
				$this->msg( [ 'vector-2022-action-addsection', 'skin-action-addsection' ] )->text(),
				'speechBubbleAdd-progressive',
				'ca-addsection',
				'',
				[ 'data-event-name' => 'addsection-header' ],
				'quiet',
				'progressive',
				false,
				$title->getLocalURL( [ 'action' => 'edit', 'section' => 'new' ] )
			) : null,
			'data-variants' => new VectorComponentVariants(
				$portlets['data-variants'],
				$title->getPageLanguage(),
				$this->msg( 'vector-language-variant-switcher-label' )
			),
			'data-vector-user-links' => new VectorComponentUserLinks(
				$localizer,
				$user,
				$portlets,
				$this->getOptions()['link'],
				$userPage[ 'icon' ] ?? ''
			),
			'data-lang-dropdown' => $langData ? new VectorComponentLanguageDropdown(
				$ulsLabels['label'],
				$ulsLabels['aria-label'],
				$langButtonClass,
				count( $this->getLanguagesCached() ),
				$langData['html-items'] ?? '',
				$langData['html-before-portal'] ?? '',
				$langData['html-after-portal'] ?? '',
				$title
			) : null,
			'data-search-box' => new VectorComponentSearchBox(
				$parentData['data-search-box'],
				true,
				// is primary mode of search
				true,
				'searchform',
				true,
				$config,
				Constants::SEARCH_BOX_INPUT_LOCATION_MOVED,
				$localizer
			),
			'data-main-menu' => new VectorComponentMainMenu(
				$sidebar,
				$portlets['data-languages'] ?? [],
				$localizer,
				$user,
				$featureManager,
				$this,
			),
			'data-main-menu-dropdown' => new VectorComponentDropdown(
				VectorComponentMainMenu::ID . '-dropdown',
				$this->msg( VectorComponentMainMenu::ID . '-label' )->text(),
				VectorComponentMainMenu::ID . '-dropdown' . ' vector-button-flush-left vector-button-flush-right',
				'menu'
			),
			'data-page-tools' => new VectorComponentPageTools(
				array_merge( [ $portlets['data-actions'] ?? [] ], $pageToolsMenu ),
				$localizer,
				$featureManager
			),
			'data-page-tools-dropdown' => new VectorComponentDropdown(
				VectorComponentPageTools::ID . '-dropdown',
				$this->msg( 'toolbox' )->text(),
				VectorComponentPageTools::ID . '-dropdown',
			),
			'data-client-prefs' => $isClientPreferencesEnabled ?
				new VectorComponentClientPrefs( $localizer, $featureManager ) : null,
			'data-client-prefs-dropdown' => $isClientPreferencesEnabled ? new VectorComponentDropdown(
				'vector-client-prefs-dropdown',
				$this->msg( 'vector-client-prefs-label' )->text(),
				'',
				// @todo: Use new theme icon (T351142)
				'appearance'
			) : null,
			'data-vector-sticky-header' => $featureManager->isFeatureEnabled(
				Constants::FEATURE_STICKY_HEADER
			) ? new VectorComponentStickyHeader(
				$localizer,
				new VectorComponentSearchBox(
					$parentData['data-search-box'],
					// Collapse inside search box is disabled.
					false,
					false,
					'vector-sticky-search-form',
					false,
					$config,
					Constants::SEARCH_BOX_INPUT_LOCATION_MOVED,
					$localizer
				),
				// Show sticky ULS if the ULS extension is enabled and the ULS in header is not hidden
				$this->isULSExtensionEnabled() && !$this->shouldHideLanguages() ?
					new VectorComponentButton(
						$ulsLabels[ 'label' ],
						'wikimedia-language',
						'p-lang-btn-sticky-header',
						'mw-interlanguage-selector',
						[
							'tabindex' => '-1',
							'data-event-name' => 'ui.dropdown-p-lang-btn-sticky-header'
						],
						'quiet',
					) : null,
				$this->isVisualEditorTabPositionFirst( $portlets[ 'data-views' ] )
			) : null,
			'data-vector-settings-button' => $featureManager->isFeatureEnabled(
				Constants::FEATURE_CLIENT_PREFERENCES,
			) ? null : new VectorComponentButton(
				$this->msg( 'vector-limited-width-toggle' ),
				$featureManager->isFeatureEnabled(
					Constants::FEATURE_LIMITED_WIDTH
				) ? 'fullScreen' : 'exitFullscreen',
				'',
				'vector-limited-width-toggle',
				[],
				'normal',
				'default',
				true
			)
		];

		foreach ( $components as $key => $component ) {
			// Array of components or null values.
			if ( $component ) {
				$parentData[$key] = $component->getTemplateData();
			}
		}

		return array_merge( $parentData, [
			'is-language-in-content' => $this->isLanguagesInContent(),
			'has-buttons-in-content-top' => $this->isLanguagesInContentAt( 'top' ) || $hasAddTopicButton,
			'is-language-in-content-bottom' => $this->isLanguagesInContentAt( 'bottom' ),
			// Cast empty string to null
			'html-subtitle' => $parentData['html-subtitle'] === '' ? null : $parentData['html-subtitle'],
		] );
	}
}
