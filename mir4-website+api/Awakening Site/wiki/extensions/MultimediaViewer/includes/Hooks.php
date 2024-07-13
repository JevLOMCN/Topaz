<?php
/**
 * This file is part of the MediaWiki extension MultimediaViewer.
 *
 * MultimediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MultimediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MultimediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup extensions
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 * @copyright Copyright © 2013, Mark Holmquist
 */

namespace MediaWiki\Extension\MultimediaViewer;

use CategoryPage;
use ExtensionRegistry;
use MediaWiki\Category\Category;
use MediaWiki\Config\Config;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\MakeGlobalVariablesScriptHook;
use MediaWiki\Hook\ThumbnailBeforeProduceHTMLHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Hook\CategoryPageViewHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\User\Hook\UserGetDefaultOptionsHook;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use MobileContext;
use Skin;
use ThumbnailImage;

class Hooks implements
	MakeGlobalVariablesScriptHook,
	UserGetDefaultOptionsHook,
	GetPreferencesHook,
	BeforePageDisplayHook,
	CategoryPageViewHook,
	ResourceLoaderGetConfigVarsHook,
	ThumbnailBeforeProduceHTMLHook
{
	/** @var string Link to more information about this module */
	protected static $infoLink =
		'https://mediawiki.org/wiki/Special:MyLanguage/Extension:Media_Viewer/About';

	/** @var string Link to a page where this module can be discussed */
	protected static $discussionLink =
		'https://mediawiki.org/wiki/Special:MyLanguage/Extension_talk:Media_Viewer/About';

	/** @var string Link to help about this module */
	protected static $helpLink =
		'https://mediawiki.org/wiki/Special:MyLanguage/Help:Extension:Media_Viewer';

	private Config $config;
	private SpecialPageFactory $specialPageFactory;
	private UserOptionsLookup $userOptionsLookup;
	private ?MobileContext $mobileContext;

	/**
	 * @param Config $config
	 * @param SpecialPageFactory $specialPageFactory
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		Config $config,
		SpecialPageFactory $specialPageFactory,
		UserOptionsLookup $userOptionsLookup,
		?MobileContext $mobileContext
	) {
		$this->config = $config;
		$this->specialPageFactory = $specialPageFactory;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->mobileContext = $mobileContext;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGetDefaultOptions
	 * @param array &$defaultOptions
	 */
	public function onUserGetDefaultOptions( &$defaultOptions ) {
		if ( $this->config->get( 'MediaViewerEnableByDefault' ) ) {
			$defaultOptions['multimediaviewer-enable'] = 1;
		}
	}

	/**
	 * Checks the context for whether to load the viewer.
	 * @param User $performer
	 * @return bool
	 */
	protected function shouldHandleClicks( User $performer ): bool {
		if ( $performer->isNamed() ) {
			return (bool)$this->userOptionsLookup->getOption( $performer, 'multimediaviewer-enable' );
		}

		return (bool)(
			$this->config->get( 'MediaViewerEnableByDefaultForAnonymous' ) ??
			$this->config->get( 'MediaViewerEnableByDefault' )
		);
	}

	/**
	 * Handler for all places where we add the modules
	 * Could be on article pages or on Category pages
	 * @param OutputPage $out
	 */
	protected function getModules( OutputPage $out ) {
		// The MobileFrontend extension provides its own implementation of MultimediaViewer.
		// See https://phabricator.wikimedia.org/T65504 and subtasks for more details.
		// To avoid loading MMV twice, we check the environment we are running in.
		$isMobileFrontendView = ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			$this->mobileContext && $this->mobileContext->shouldDisplayMobileView();
		if ( !$isMobileFrontendView ) {
			$out->addModules( [ 'mmv.head', 'mmv.bootstrap.autostart' ] );
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * Add JavaScript to the page when an image is on it
	 * and the user has enabled the feature
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$pageHasThumbnails = count( $out->getFileSearchOptions() ) > 0;
		$pageIsFilePage = $out->getTitle()->inNamespace( NS_FILE );
		// TODO: Have Flow work out if there are any images on the page
		$pageIsFlowPage = ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) &&
			// CONTENT_MODEL_FLOW_BOARD
			$out->getTitle()->getContentModel() === 'flow-board';
		$fileRelatedSpecialPages = [ 'Newimages', 'Listfiles', 'Mostimages',
			'MostGloballyLinkedFiles', 'Uncategorizedimages', 'Unusedimages', 'Search' ];
		$pageIsFileRelatedSpecialPage = $out->getTitle()->inNamespace( NS_SPECIAL )
			&& in_array( $this->specialPageFactory->resolveAlias( $out->getTitle()->getDBkey() )[0],
				$fileRelatedSpecialPages );

		if ( $pageHasThumbnails || $pageIsFilePage || $pageIsFileRelatedSpecialPage || $pageIsFlowPage ) {
			$this->getModules( $out );
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CategoryPageView
	 * Add JavaScript to the page if there are images in the category
	 * @param CategoryPage $catPage
	 */
	public function onCategoryPageView( $catPage ) {
		$title = $catPage->getTitle();
		$cat = Category::newFromTitle( $title );
		if ( $cat->getFileCount() > 0 ) {
			$out = $catPage->getContext()->getOutput();
			$this->getModules( $out );
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 * Adds a default-enabled preference to gate the feature
	 * @param User $user
	 * @param array &$prefs
	 */
	public function onGetPreferences( $user, &$prefs ) {
		$prefs['multimediaviewer-enable'] = [
			'type' => 'toggle',
			'label-message' => 'multimediaviewer-optin-pref',
			'section' => 'rendering/files',
		];
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 * Export variables used in both PHP and JS to keep DRY
	 * @param array &$vars
	 * @param string $skin
	 * @param Config $config
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['wgMultimediaViewer'] = [
			'infoLink' => self::$infoLink,
			'discussionLink' => self::$discussionLink,
			'helpLink' => self::$helpLink,
			'useThumbnailGuessing' => (bool)$this->config->get( 'MediaViewerUseThumbnailGuessing' ),
			'imageQueryParameter' => $this->config->get( 'MediaViewerImageQueryParameter' ),
			'recordVirtualViewBeaconURI' => $this->config->get( 'MediaViewerRecordVirtualViewBeaconURI' ),
			'extensions' => $this->config->get( 'MediaViewerExtensions' ),
		];
		$vars['wgMediaViewer'] = true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/MakeGlobalVariablesScript
	 * Export variables which depend on the current user
	 * @param array &$vars
	 * @param OutputPage $out
	 * @return void
	 */
	public function onMakeGlobalVariablesScript( &$vars, $out ): void {
		$user = $out->getUser();
		$isMultimediaViewerEnable = $this->userOptionsLookup->getDefaultOption(
			'multimediaviewer-enable',
			$user
		);

		$vars['wgMediaViewerOnClick'] = $this->shouldHandleClicks( $user );
		// needed because of T71942; could be different for anon and logged-in
		$vars['wgMediaViewerEnabledByDefault'] = (bool)$isMultimediaViewerEnable;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ThumbnailBeforeProduceHTML
	 * Modify thumbnail DOM
	 * @param ThumbnailImage $thumbnail
	 * @param array &$attribs Attributes of the <img> element
	 * @param array|bool &$linkAttribs Attributes of the wrapping <a> element
	 */
	public function onThumbnailBeforeProduceHTML(
		$thumbnail,
		&$attribs,
		&$linkAttribs
	) {
		$file = $thumbnail->getFile();

		if ( $file ) {
			$attribs['data-file-width'] = $file->getWidth();
			$attribs['data-file-height'] = $file->getHeight();
		}
	}
}
