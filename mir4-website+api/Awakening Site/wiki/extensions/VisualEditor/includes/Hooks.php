<?php
/**
 * VisualEditor extension hooks
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2020 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

namespace MediaWiki\Extension\VisualEditor;

use Article;
use Config;
use DeferredUpdates;
use ExtensionRegistry;
use Html;
use HTMLForm;
use IContextSource;
use Language;
use MediaWiki\Actions\ActionEntryPoint;
use MediaWiki\Auth\Hook\UserLoggedInHook;
use MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook;
use MediaWiki\ChangeTags\Hook\ListDefinedTagsHook;
use MediaWiki\Diff\Hook\DifferenceEngineViewHeaderHook;
use MediaWiki\Diff\Hook\TextSlotDiffRendererTablePrefixHook;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Hook\BeforeInitializeHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\CustomEditorHook;
use MediaWiki\Hook\EditPage__showEditForm_fieldsHook;
use MediaWiki\Hook\MakeGlobalVariablesScriptHook;
use MediaWiki\Hook\OutputPageBodyAttributesHook;
use MediaWiki\Hook\ParserTestGlobalsHook;
use MediaWiki\Hook\RecentChange_saveHook;
use MediaWiki\Hook\SkinEditSectionLinksHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Preferences\Hook\PreferencesFormPreSaveHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\SpecialPage\Hook\RedirectSpecialArticleRedirectParamsHook;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use OOUI\ButtonGroupWidget;
use OOUI\ButtonWidget;
use OutputPage;
use RecentChange;
use RequestContext;
use Skin;
use SkinTemplate;
use SpecialPage;
use TextSlotDiffRenderer;
use User;
use WebRequest;

/**
 * @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
 */
class Hooks implements
	TextSlotDiffRendererTablePrefixHook,
	BeforeInitializeHook,
	BeforePageDisplayHook,
	ChangeTagsListActiveHook,
	CustomEditorHook,
	DifferenceEngineViewHeaderHook,
	EditPage__showEditForm_fieldsHook,
	GetPreferencesHook,
	ListDefinedTagsHook,
	MakeGlobalVariablesScriptHook,
	OutputPageBodyAttributesHook,
	ParserTestGlobalsHook,
	PreferencesFormPreSaveHook,
	RecentChange_saveHook,
	RedirectSpecialArticleRedirectParamsHook,
	ResourceLoaderGetConfigVarsHook,
	ResourceLoaderRegisterModulesHook,
	SkinEditSectionLinksHook,
	SkinTemplateNavigation__UniversalHook,
	UserLoggedInHook
{

	// Known parameters that VE does not handle
	// TODO: Other params too?
	// Known-good parameters: edit, veaction, section, oldid, lintid, preload, preloadparams, editintro
	// Partially-good: preloadtitle (source-mode only)
	private const UNSUPPORTED_EDIT_PARAMS = [
		'undo',
		'undoafter',
		// Only for WTE. This parameter is not supported right now, and NWE has a very different design
		// for previews, so we might not want to support this at all.
		'preview',
		'veswitched'
	];

	private const TAGS = [
		'visualeditor',
		'visualeditor-wikitext',
		// Edit check
		'editcheck-references',
		'editcheck-references-activated',
		'editcheck-newcontent',
		'editcheck-newreference',
		'editcheck-reference-decline-common-knowledge',
		'editcheck-reference-decline-irrelevant',
		'editcheck-reference-decline-uncertain',
		'editcheck-reference-decline-other',
		// No longer in active use:
		'visualeditor-needcheck',
		'visualeditor-switched'
	];

	/**
	 * Initialise the 'VisualEditorAvailableNamespaces' setting, and add content
	 * namespaces to it. This will run after LocalSettings.php is processed.
	 * Also ensure Parsoid extension is loaded when necessary.
	 */
	public static function onRegistration() {
		global $wgVisualEditorAvailableNamespaces, $wgContentNamespaces;

		foreach ( $wgContentNamespaces as $contentNamespace ) {
			if ( !isset( $wgVisualEditorAvailableNamespaces[$contentNamespace] ) ) {
				$wgVisualEditorAvailableNamespaces[$contentNamespace] = true;
			}
		}
	}

	/**
	 * Adds VisualEditor JS to the output.
	 *
	 * This is attached to the MediaWiki 'BeforePageDisplay' hook.
	 *
	 * @param OutputPage $output The page view.
	 * @param Skin $skin The skin that's going to build the UI.
	 */
	public function onBeforePageDisplay( $output, $skin ): void {
		$services = MediaWikiServices::getInstance();
		$hookRunner = new VisualEditorHookRunner( $services->getHookContainer() );
		if ( !$hookRunner->onVisualEditorBeforeEditor( $output, $skin ) ) {
			$output->addJsConfigVars( 'wgVisualEditorDisabledByHook', true );
			return;
		}
		if ( !(
			ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			$services->getService( 'MobileFrontend.Context' )
				->shouldDisplayMobileView()
		) ) {
			$output->addModules( [
				'ext.visualEditor.desktopArticleTarget.init',
				'ext.visualEditor.targetLoader'
			] );
			$output->addModuleStyles( [ 'ext.visualEditor.desktopArticleTarget.noscript' ] );
		}
		if (
			$services->getUserOptionsLookup()->getOption( $skin->getUser(), 'visualeditor-collab' ) ||
			// Joining a collab session
			$output->getRequest()->getVal( 'collabSession' )
		) {
			$output->addModules( 'ext.visualEditor.collab' );
		}

		// add scroll offset js variable to output
		$veConfig = $services->getConfigFactory()->makeConfig( 'visualeditor' );
		$skinsToolbarScrollOffset = $veConfig->get( 'VisualEditorSkinToolbarScrollOffset' );
		$toolbarScrollOffset = 0;
		$skinName = $skin->getSkinName();
		if ( isset( $skinsToolbarScrollOffset[$skinName] ) ) {
			$toolbarScrollOffset = $skinsToolbarScrollOffset[$skinName];
		}
		// T220158: Don't add this unless it's non-default
		// TODO: Move this to packageFiles as it's not relevant to the HTML request.
		if ( $toolbarScrollOffset !== 0 ) {
			$output->addJsConfigVars( 'wgVisualEditorToolbarScrollOffset', $toolbarScrollOffset );
		}

		$output->addJsConfigVars(
			'wgEditSubmitButtonLabelPublish',
			$veConfig->get( 'EditSubmitButtonLabelPublish' )
		);

		// Don't index VE edit pages (T319124)
		if ( $output->getRequest()->getVal( 'veaction' ) ) {
			$output->setRobotPolicy( 'noindex,nofollow' );
		}
	}

	/**
	 * @internal For internal use in extension.json only.
	 * @return array
	 */
	public static function getDataForDesktopArticleTargetInitModule() {
		return [
			'unsupportedEditParams' => self::UNSUPPORTED_EDIT_PARAMS,
		];
	}

	/** @inheritDoc */
	public function onDifferenceEngineViewHeader( $differenceEngine ) {
		$output = $differenceEngine->getContext()->getOutput();
		$output->addModuleStyles( [
			'ext.visualEditor.diffPage.init.styles',
			'oojs-ui.styles.icons-accessibility',
			'oojs-ui.styles.icons-editing-advanced'
		] );
		// T344596: Must load this module unconditionally. The TextSlotDiffRendererTablePrefix hook
		// below doesn't run when the diff is e.g. a log entry with no change to the content.
		$output->addModules( 'ext.visualEditor.diffPage.init' );
		$output->enableOOUI();
	}

	/**
	 * Handler for the DifferenceEngineViewHeader hook, to add visual diffs code as configured
	 *
	 * @param TextSlotDiffRenderer $textSlotDiffRenderer
	 * @param IContextSource $context
	 * @param string[] &$parts
	 * @return void
	 */
	public function onTextSlotDiffRendererTablePrefix(
		TextSlotDiffRenderer $textSlotDiffRenderer,
		IContextSource $context,
		array &$parts
	) {
		$services = MediaWikiServices::getInstance();
		$veConfig = $services->getConfigFactory()
			->makeConfig( 'visualeditor' );
		$output = $context->getOutput();

		// Return early if not viewing a diff of an allowed type.
		if ( !ApiVisualEditor::isAllowedContentType( $veConfig, $textSlotDiffRenderer->getContentModel() )
			|| $output->getActionName() !== 'view'
		) {
			return;
		}

		$parts['50_ve-init-mw-diffPage-diffMode'] = '<div class="ve-init-mw-diffPage-diffMode">' .
			// Will be replaced by a ButtonSelectWidget in JS
			new ButtonGroupWidget( [
				'items' => [
					new ButtonWidget( [
						'data' => 'visual',
						'icon' => 'eye',
						'disabled' => true,
						'label' => $output->msg( 'visualeditor-savedialog-review-visual' )->plain()
					] ),
					new ButtonWidget( [
						'data' => 'source',
						'icon' => 'wikiText',
						'active' => true,
						'label' => $output->msg( 'visualeditor-savedialog-review-wikitext' )->plain()
					] )
				]
			] ) .
			'</div>';
	}

	/**
	 * Detect incompatible browsers which we can't expect to load VE
	 *
	 * @param WebRequest $req The web request to check the details of
	 * @param Config $config VE config object
	 * @return bool The User Agent is unsupported
	 */
	private static function isUAUnsupported( WebRequest $req, $config ) {
		if ( $req->getVal( 'vesupported' ) ) {
			return false;
		}
		$unsupportedList = $config->get( 'VisualEditorBrowserUnsupportedList' );
		$ua = strtolower( $req->getHeader( 'User-Agent' ) );
		foreach ( $unsupportedList as $uaSubstr => $rules ) {
			if ( !strpos( $ua, $uaSubstr . '/' ) ) {
				continue;
			}
			if ( !is_array( $rules ) ) {
				return true;
			}

			$matches = [];
			$ret = preg_match( '/' . $uaSubstr . '\/([0-9\.]*) ?/i', $ua, $matches );
			if ( $ret !== 1 ) {
				continue;
			}
			$version = $matches[1];
			foreach ( $rules as $rule ) {
				[ $op, $matchVersion ] = $rule;
				if (
					( $op === '<' && $version < $matchVersion ) ||
					( $op === '>' && $version > $matchVersion ) ||
					( $op === '<=' && $version <= $matchVersion ) ||
					( $op === '>=' && $version >= $matchVersion )
				) {
					return true;
				}
			}

		}
		return false;
	}

	/**
	 * @param Title $title
	 * @param User $user
	 * @param WebRequest $req
	 * @return bool
	 */
	private static function isSupportedEditPage( Title $title, User $user, WebRequest $req ) {
		if (
			$req->getVal( 'action' ) !== 'edit' ||
			!MediaWikiServices::getInstance()->getPermissionManager()->quickUserCan( 'edit', $user, $title )
		) {
			return false;
		}

		foreach ( self::UNSUPPORTED_EDIT_PARAMS as $param ) {
			if ( $req->getVal( $param ) !== null ) {
				return false;
			}
		}

		switch ( self::getEditPageEditor( $user, $req ) ) {
			case 'visualeditor':
				return self::isVisualAvailable( $title, $req, $user ) ||
					self::isWikitextAvailable( $title, $user );
			case 'wikitext':
			default:
				return self::isWikitextAvailable( $title, $user );
		}
	}

	/**
	 * @param User $user
	 * @return bool
	 */
	private static function enabledForUser( $user ) {
		$services = MediaWikiServices::getInstance();
		$veConfig = $services->getConfigFactory()->makeConfig( 'visualeditor' );
		$userOptionsLookup = $services->getUserOptionsLookup();
		$isBeta = $veConfig->get( 'VisualEditorEnableBetaFeature' );

		return ( $isBeta ?
			$userOptionsLookup->getOption( $user, 'visualeditor-enable' ) :
			!$userOptionsLookup->getOption( $user, 'visualeditor-betatempdisable' ) ) &&
			!$userOptionsLookup->getOption( $user, 'visualeditor-autodisable' );
	}

	/**
	 * @param Title $title
	 * @param WebRequest $req
	 * @param User $user
	 * @return bool
	 */
	private static function isVisualAvailable( $title, $req, $user ) {
		$veConfig = MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'visualeditor' );

		return (
			// If forced by the URL parameter, skip the namespace check (T221892) and preference check
			( $req->getVal( 'veaction' ) === 'edit' || (
				// Only in enabled namespaces
				ApiVisualEditor::isAllowedNamespace( $veConfig, $title->getNamespace() ) &&

				// Enabled per user preferences
				self::enabledForUser( $user )
			) ) &&
			// Only for pages with a supported content model
			ApiVisualEditor::isAllowedContentType( $veConfig, $title->getContentModel() )
		);
	}

	/**
	 * @param Title $title
	 * @param User $user
	 * @return bool
	 */
	private static function isWikitextAvailable( $title, $user ) {
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		return $userOptionsLookup->getOption( $user, 'visualeditor-newwikitext' ) &&
			$title->getContentModel() === 'wikitext';
	}

	/**
	 * @param UserIdentity $user
	 * @param string $key
	 * @param string $value
	 */
	private static function deferredSetUserOption( UserIdentity $user, string $key, string $value ) {
		DeferredUpdates::addCallableUpdate( static function () use ( $user, $key, $value ) {
			$services = MediaWikiServices::getInstance();
			if ( $services->getReadOnlyMode()->isReadOnly() ) {
				return;
			}
			$userOptionsManager = $services->getUserOptionsManager();
			$userOptionsManager->setOption( $user, $key, $value );
			$userOptionsManager->saveOptions( $user );
		} );
	}

	/**
	 * Decide whether to bother showing the wikitext editor at all.
	 * If not, we expect the VE initialisation JS to activate.
	 *
	 * @param Article $article The article being viewed.
	 * @param User $user The user-specific settings.
	 * @return bool Whether to show the wikitext editor or not.
	 */
	public function onCustomEditor( $article, $user ) {
		$req = $article->getContext()->getRequest();
		$services = MediaWikiServices::getInstance();
		$veConfig = $services->getConfigFactory()->makeConfig( 'visualeditor' );

		if ( ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			// If mobilefrontend is involved it can make its own decisions about this
			$mobFrontContext = MediaWikiServices::getInstance()->getService( 'MobileFrontend.Context' );
			if ( $mobFrontContext->shouldDisplayMobileView() ) {
				return true;
			}
		}

		if (
			!self::enabledForUser( $user ) ||
			self::isUAUnsupported( $req, $veConfig )
		) {
			return true;
		}

		$title = $article->getTitle();

		if ( $req->getVal( 'venoscript' ) ) {
			$req->response()->setCookie( 'VEE', 'wikitext', 0, [ 'prefix' => '' ] );
			if ( $user->isNamed() ) {
				self::deferredSetUserOption( $user, 'visualeditor-editor', 'wikitext' );
			}
			return true;
		}

		if ( self::isSupportedEditPage( $title, $user, $req ) ) {
			$params = $req->getValues();
			$params['venoscript'] = '1';
			$url = wfScript() . '?' . wfArrayToCgi( $params );

			$out = $article->getContext()->getOutput();
			$titleMsg = $title->exists() ? 'editing' : 'creating';
			$out->setPageTitleMsg( wfMessage( $titleMsg, $title->getPrefixedText() ) );
			$out->showPendingTakeover( $url, 'visualeditor-toload', wfExpandUrl( $url ) );

			$out->setRevisionId( $req->getInt( 'oldid', $article->getRevIdFetched() ) );
			return false;
		}
		return true;
	}

	/**
	 * @param User $user
	 * @param WebRequest $req
	 * @return string 'wikitext' or 'visual'
	 */
	private static function getEditPageEditor( User $user, WebRequest $req ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'visualeditor' );
		if ( $config->get( 'VisualEditorDisableForAnons' ) && !$user->isRegistered() ) {
			return 'wikitext';
		}
		$isRedLink = $req->getBool( 'redlink' );
		// On dual-edit-tab wikis, the edit page must mean the user wants wikitext,
		// unless following a redlink
		if ( !$config->get( 'VisualEditorUseSingleEditTab' ) && !$isRedLink ) {
			return 'wikitext';
		}
		return self::getPreferredEditor( $user, $req, !$isRedLink );
	}

	/**
	 * @param User $user
	 * @param WebRequest $req
	 * @param bool $useWikitextInMultiTab
	 * @return string 'wikitext' or 'visual'
	 */
	public static function getPreferredEditor(
		User $user, WebRequest $req, $useWikitextInMultiTab = false
	) {
		// VisualEditor shouldn't even call this method when it's disabled, but it is a public API for
		// other extensions (e.g. DiscussionTools), and the editor preferences might have surprising
		// values if the user has tried VisualEditor in the past and then disabled it. (T257234)
		if ( !self::enabledForUser( $user ) ) {
			return 'wikitext';
		}

		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();

		switch ( $userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) ) {
			case 'prefer-ve':
				return 'visualeditor';
			case 'prefer-wt':
				return 'wikitext';
			case 'multi-tab':
				// May have got here by switching from VE
				// TODO: Make such an action explicitly request wikitext
				// so we can use getLastEditor here instead.
				return $useWikitextInMultiTab ?
					'wikitext' :
					self::getLastEditor( $user, $req );
			case 'remember-last':
			default:
				return self::getLastEditor( $user, $req );
		}
	}

	/**
	 * @param User $user
	 * @param WebRequest $req
	 * @return string
	 */
	private static function getLastEditor( User $user, WebRequest $req ) {
		// This logic matches getLastEditor in:
		// modules/ve-mw/init/targets/ve.init.mw.DesktopArticleTarget.init.js
		$editor = $req->getCookie( 'VEE', '' );
		// Set editor to user's preference or site's default (ignore the cookie) if …
		if (
			// … user is logged in,
			$user->isNamed() ||
			// … no cookie is set, or
			!$editor ||
			// value is invalid.
			!( $editor === 'visualeditor' || $editor === 'wikitext' )
		) {
			$services = MediaWikiServices::getInstance();
			$userOptionsLookup = $services->getUserOptionsLookup();
			$editor = $userOptionsLookup->getOption( $user, 'visualeditor-editor' );
		}
		return $editor;
	}

	/**
	 * Changes the Edit tab and adds the VisualEditor tab.
	 *
	 * This is attached to the MediaWiki 'SkinTemplateNavigation::Universal' hook.
	 *
	 * @param SkinTemplate $skin The skin template on which the UI is built.
	 * @param array &$links Navigation links.
	 */
	public function onSkinTemplateNavigation__Universal( $skin, &$links ): void {
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		$config = $services->getConfigFactory()
			->makeConfig( 'visualeditor' );

		self::onSkinTemplateNavigationSpecialPage( $skin, $links );

		if (
			ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			$services->getService( 'MobileFrontend.Context' )->shouldDisplayMobileView()
		) {
			return;
		}

		// Exit if there's no edit link for whatever reason (e.g. protected page)
		if ( !isset( $links['views']['edit'] ) ) {
			return;
		}

		$hookRunner = new VisualEditorHookRunner( $services->getHookContainer() );
		if ( !$hookRunner->onVisualEditorBeforeEditor( $skin->getOutput(), $skin ) ) {
			return;
		}

		$user = $skin->getUser();
		if (
			$config->get( 'VisualEditorUseSingleEditTab' ) &&
			$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'prefer-wt'
		) {
			return;
		}

		if (
			$config->get( 'VisualEditorUseSingleEditTab' ) &&
			wfTimestampNow() < $config->get( 'VisualEditorSingleEditTabSwitchTimeEnd' ) &&
			$user->isNamed() &&
			self::enabledForUser( $user ) &&
			!$userOptionsLookup->getOption( $user, 'visualeditor-hidetabdialog' ) &&
			$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'remember-last'
		) {
			// Check if the user has made any edits before the SET switch time
			$dbr = $services->getConnectionProvider()->getReplicaDatabase();
			$revExists = $dbr->newSelectQueryBuilder()
				->from( 'revision' )
				->field( '1' )
				->where( [
					'rev_actor' => $user->getActorId(),
					$dbr->expr( 'rev_timestamp', '<', $dbr->timestamp(
						$config->get( 'VisualEditorSingleEditTabSwitchTime' )
					) )
				] )
				->caller( __METHOD__ )
				->fetchField();
			if ( $revExists ) {
				$links['views']['edit']['class'] .= ' visualeditor-showtabdialog';
			}
		}

		// Exit if the user doesn't have VE enabled
		if (
			!self::enabledForUser( $user ) ||
			// T253941: This option does not actually disable the editor, only leaves the tabs/links unchanged
			( $config->get( 'VisualEditorDisableForAnons' ) && !$user->isRegistered() )
		) {
			return;
		}

		$title = $skin->getRelevantTitle();
		// Don't exit if this page isn't VE-enabled, since we should still
		// change "Edit" to "Edit source".
		$isAvailable = self::isVisualAvailable( $title, $skin->getRequest(), $user );

		$tabMessages = $config->get( 'VisualEditorTabMessages' );
		// Rebuild the $links['views'] array and inject the VisualEditor tab before or after
		// the edit tab as appropriate. We have to rebuild the array because PHP doesn't allow
		// us to splice into the middle of an associative array.
		$newViews = [];
		$wikiPageFactory = $services->getWikiPageFactory();
		$isRemote = !$wikiPageFactory->newFromTitle( $title )->isLocal();

		$skinHasEditIcons = in_array(
			$skin->getSkinName(),
			ExtensionRegistry::getInstance()->getAttribute( 'VisualEditorIconSkins' )
		);

		foreach ( $links['views'] as $action => $data ) {
			if ( $action === 'edit' ) {
				// Build the VisualEditor tab
				$existing = $title->exists() || (
					$title->inNamespace( NS_MEDIAWIKI ) &&
					$title->getDefaultMessageText() !== false
				);
				$action = $existing ? 'edit' : 'create';
				$veParams = $skin->editUrlOptions();
				// Remove action=edit
				unset( $veParams['action'] );
				// Set veaction=edit
				$veParams['veaction'] = 'edit';
				$veTabMessage = $tabMessages[$action];
				$veTabText = $veTabMessage === null ? $data['text'] :
					$skin->msg( $veTabMessage )->text();
				if ( $isRemote ) {
					// The following messages can be used here:
					// * tooltip-ca-ve-edit-local
					// * tooltip-ca-ve-create-local
					// The following messages can be generated upstream:
					// * accesskey-ca-ve-edit-local
					// * accesskey-ca-ve-create-local
					$veTooltip = 'ca-ve-' . $action . '-local';
				} else {
					// The following messages can be used here:
					// * tooltip-ca-ve-edit
					// * tooltip-ca-ve-create
					// The following messages can be generated upstream:
					// * accesskey-ca-ve-edit
					// * accesskey-ca-ve-create
					$veTooltip = 'ca-ve-' . $action;
				}
				$veTab = [
					'href' => $title->getLocalURL( $veParams ),
					'text' => $veTabText,
					'single-id' => $veTooltip,
					'primary' => true,
					'icon' => $skinHasEditIcons ? 'edit' : null,
					'class' => '',
				];

				// Alter the edit tab
				$editTab = $data;
				if ( $isRemote ) {
					// The following messages can be used here:
					// * visualeditor-ca-editlocaldescriptionsource
					// * visualeditor-ca-createlocaldescriptionsource
					$editTabMessage = $tabMessages[$action . 'localdescriptionsource'];
					// The following messages can be used here:
					// * tooltip-ca-editsource-local
					// * tooltip-ca-createsource-local
					// The following messages can be generated upstream:
					// * accesskey-ca-editsource-local
					// * accesskey-ca-createsource-local
					$editTabTooltip = 'ca-' . $action . 'source-local';
				} else {
					// The following messages can be used here:
					// * visualeditor-ca-editsource
					// * visualeditor-ca-createsource
					$editTabMessage = $tabMessages[$action . 'source'];
					// The following messages can be used here:
					// * tooltip-ca-editsource
					// * tooltip-ca-createsource
					// The following messages can be generated upstream:
					// * accesskey-ca-editsource
					// * accesskey-ca-createsource
					$editTabTooltip = 'ca-' . $action . 'source';
				}

				if ( $editTabMessage !== null ) {
					$editTab['text'] = $skin->msg( $editTabMessage )->text();
					$editTab['single-id'] = $editTabTooltip;
				}

				$editor = self::getLastEditor( $user, $skin->getRequest() );
				if (
					$isAvailable &&
					$config->get( 'VisualEditorUseSingleEditTab' ) &&
					(
						$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'prefer-ve' ||
						(
							$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'remember-last' &&
							$editor === 'visualeditor'
						)
					)
				) {
					$editTab['text'] = $veTabText;
					$newViews['edit'] = $editTab;
				} elseif (
					$isAvailable &&
					(
						!$config->get( 'VisualEditorUseSingleEditTab' ) ||
						$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'multi-tab'
					)
				) {
					// Change icon
					$editTab['icon'] = $skinHasEditIcons ? 'wikiText' : null;
					// Inject the VE tab before or after the edit tab
					if ( $config->get( 'VisualEditorTabPosition' ) === 'before' ) {
						// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
						$editTab['class'] .= ' collapsible';
						$newViews['ve-edit'] = $veTab;
						$newViews['edit'] = $editTab;
					} else {
						$veTab['class'] .= ' collapsible';
						$newViews['edit'] = $editTab;
						$newViews['ve-edit'] = $veTab;
					}
				} elseif (
					!$config->get( 'VisualEditorUseSingleEditTab' ) ||
					!$isAvailable ||
					$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'multi-tab' ||
					(
						$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'remember-last' &&
						$editor === 'wikitext'
					)
				) {
					// Don't add ve-edit, but do update the edit tab (e.g. "Edit source").
					$newViews['edit'] = $editTab;
				} else {
					// This should not happen.
				}
			} else {
				// Just pass through
				$newViews[$action] = $data;
			}
		}
		$links['views'] = $newViews;
	}

	/**
	 * @param SkinTemplate $skin The skin template on which the UI is built.
	 * @param array &$links Navigation links.
	 */
	private static function onSkinTemplateNavigationSpecialPage( SkinTemplate $skin, array &$links ) {
		$title = $skin->getTitle();
		if ( !$title || !$title->isSpecialPage() ) {
			return;
		}
		[ $special, $subPage ] = MediaWikiServices::getInstance()->getSpecialPageFactory()
			->resolveAlias( $title->getDBkey() );
		if ( $special !== 'CollabPad' ) {
			return;
		}
		$links['namespaces']['special']['text'] = $skin->msg( 'collabpad' )->text();
		$subPageTitle = Title::newFromText( $subPage );
		if ( $subPageTitle ) {
			$links['namespaces']['special']['href'] = SpecialPage::getTitleFor( $special )->getLocalURL();
			$links['namespaces']['special']['class'] = '';

			$links['namespaces']['pad']['text'] = $subPageTitle->getPrefixedText();
			$links['namespaces']['pad']['href'] = '';
			$links['namespaces']['pad']['class'] = 'selected';
		}
	}

	/**
	 * Called when the normal wikitext editor is shown.
	 * Inserts a 'veswitched' hidden field if requested by the client
	 *
	 * @param EditPage $editPage The edit page view.
	 * @param OutputPage $output The page view.
	 */
	public function onEditPage__showEditForm_fields( $editPage, $output ) {
		$request = $output->getRequest();
		if ( $request->getBool( 'veswitched' ) ) {
			$output->addHTML( Html::hidden( 'veswitched', '1' ) );
		}
	}

	/**
	 * Called when an edit is saved
	 * Adds 'visualeditor-switched' tag to the edit if requested
	 * Adds whatever tags from static::TAGS are present in the vetags parameter
	 *
	 * @param RecentChange $rc The new RC entry.
	 */
	public function onRecentChange_Save( $rc ) {
		$request = RequestContext::getMain()->getRequest();
		if ( $request->getBool( 'veswitched' ) && $rc->getAttribute( 'rc_this_oldid' ) ) {
			$rc->addTags( 'visualeditor-switched' );
		}

		$tags = explode( ',', $request->getVal( 'vetags' ) ?? '' );
		$tags = array_values( array_intersect( $tags, static::TAGS ) );
		if ( $tags ) {
			$rc->addTags( $tags );
		}
	}

	/**
	 * Changes the section edit links to add a VE edit link.
	 *
	 * This is attached to the MediaWiki 'SkinEditSectionLinks' hook.
	 *
	 * @param Skin $skin Skin being used to render the UI
	 * @param Title $title Title being used for request
	 * @param string $section The name of the section being pointed to.
	 * @param string $tooltip The default tooltip.
	 * @param array &$result All link detail arrays.
	 * @phan-param array{editsection:array{text:string,targetTitle:Title,attribs:array,query:array}} $result
	 * @param Language $lang The user interface language.
	 */
	public function onSkinEditSectionLinks( $skin, $title, $section,
		$tooltip, &$result, $lang
	) {
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		$config = $services->getConfigFactory()
			->makeConfig( 'visualeditor' );

		// Exit if we're in parserTests
		if ( isset( $GLOBALS[ 'wgVisualEditorInParserTests' ] ) ) {
			return;
		}

		if (
			ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			$services->getService( 'MobileFrontend.Context' )->shouldDisplayMobileView()
		) {
			return;
		}

		$user = $skin->getUser();
		// Exit if the user doesn't have VE enabled
		if (
			!self::enabledForUser( $user ) ||
			// T253941: This option does not actually disable the editor, only leaves the tabs/links unchanged
			( $config->get( 'VisualEditorDisableForAnons' ) && !$user->isRegistered() )
		) {
			return;
		}

		// Exit if we're on a foreign file description page
		if (
			$title->inNamespace( NS_FILE ) &&
			!$services->getWikiPageFactory()->newFromTitle( $title )->isLocal()
		) {
			return;
		}

		$editor = self::getLastEditor( $user, $skin->getRequest() );
		if (
			!$config->get( 'VisualEditorUseSingleEditTab' ) ||
			$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'multi-tab' ||
			(
				$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) === 'remember-last' &&
				$editor === 'wikitext'
			)
		) {
			// Don't add ve-edit, but do update the edit tab (e.g. "Edit source").
			$tabMessages = $config->get( 'VisualEditorTabMessages' );
			// The following messages can be used here:
			// * visualeditor-ca-editsource-section
			$sourceEditSection = $tabMessages['editsectionsource'];
			$result['editsection']['text'] = $skin->msg( $sourceEditSection )->inLanguage( $lang )->text();
			// The following messages can be used here:
			// * visualeditor-ca-editsource-section-hint
			$sourceEditSectionHint = $tabMessages['editsectionsourcehint'];
			$result['editsection']['attribs']['title'] = $skin->msg( $sourceEditSectionHint )
				->plaintextParams( $tooltip )
				->inLanguage( $lang )->text();
		}

		// Exit if we're using the single edit tab.
		if (
			$config->get( 'VisualEditorUseSingleEditTab' ) &&
			$userOptionsLookup->getOption( $user, 'visualeditor-tabs' ) !== 'multi-tab'
		) {
			return;
		}

		$skinHasEditIcons = in_array(
			$skin->getSkinName(),
			ExtensionRegistry::getInstance()->getAttribute( 'VisualEditorIconSkins' )
		);

		// add VE edit section in VE available namespaces
		if ( self::isVisualAvailable( $title, $skin->getRequest(), $user ) ) {
			// The following messages can be used here:
			// * editsection
			$veEditSection = $tabMessages['editsection'];
			// The following messages can be used here:
			// * editsectionhint
			$veEditSectionHint = $tabMessages['editsectionhint'];

			$attribs = $result['editsection']['attribs'];
			$attribs['class'] = ( $attribs['class'] ?? '' ) . ' mw-editsection-visualeditor';
			$attribs['title'] = $skin->msg( $veEditSectionHint )
				->plaintextParams( $tooltip )
				->inLanguage( $lang )->text();

			$veLink = [
				'text' => $skin->msg( $veEditSection )->inLanguage( $lang )->text(),
				'icon' => $skinHasEditIcons ? 'edit' : null,
				'targetTitle' => $title,
				'attribs' => $attribs,
				'query' => [ 'veaction' => 'edit', 'section' => $section ],
				'options' => [ 'noclasses', 'known' ]
			];
			// Change icon
			$result['editsection']['icon'] = $skinHasEditIcons ? 'wikiText' : null;

			$result['veeditsection'] = $veLink;
			if ( $config->get( 'VisualEditorTabPosition' ) === 'before' ) {
				krsort( $result );
				// TODO: This will probably cause weird ordering if any other extensions added something
				// already.
				// ... wfArrayInsertBefore?
			}
		}
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @param string[] &$bodyAttrs
	 */
	public function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ): void {
		$specialTitle = $sk->getTitle();

		// HACK: Replace classes generated by Skin::getPageClasses as if an article title
		// was passed in, instead of a special page.
		if ( $specialTitle && $specialTitle->isSpecial( 'CollabPad' ) ) {
			$articleTitle = Title::newFromText( 'DummyPage' );

			$specialClasses = $sk->getPageClasses( $specialTitle );
			$articleClasses = $sk->getPageClasses( $articleTitle );

			$bodyAttrs['class'] = str_replace( $specialClasses, $articleClasses, $bodyAttrs['class'] );
		}
	}

	/**
	 * Handler for the GetPreferences hook, to add and hide user preferences as configured
	 *
	 * @param User $user
	 * @param array &$preferences Their preferences object
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		$veConfig = $services->getConfigFactory()->makeConfig( 'visualeditor' );
		$isBeta = $veConfig->get( 'VisualEditorEnableBetaFeature' );

		// Use the old preference keys to avoid having to migrate data for now.
		// (One day we might write and run a maintenance script to update the
		// entries in the database and make this unnecessary.) (T344762)
		if ( $isBeta ) {
			$preferences['visualeditor-enable'] = [
				'type' => 'toggle',
				'label-message' => 'visualeditor-preference-visualeditor',
				'section' => 'editing/editor',
			];
		} else {
			$preferences['visualeditor-betatempdisable'] = [
				'invert' => true,
				'type' => 'toggle',
				'label-message' => 'visualeditor-preference-visualeditor',
				'section' => 'editing/editor',
				'default' => $userOptionsLookup->getOption( $user, 'visualeditor-betatempdisable' ) ||
					$userOptionsLookup->getOption( $user, 'visualeditor-autodisable' )
			];
		}

		if ( $veConfig->get( 'VisualEditorEnableWikitext' ) ) {
			$preferences['visualeditor-newwikitext'] = [
				'type' => 'toggle',
				'label-message' => 'visualeditor-preference-newwikitexteditor-enable',
				'help-message' => 'visualeditor-preference-newwikitexteditor-help',
				'section' => 'editing/editor'
			];
		}

		// Config option for Single Edit Tab
		if (
			$veConfig->get( 'VisualEditorUseSingleEditTab' ) &&
			self::enabledForUser( $user )
		) {
			$preferences['visualeditor-tabs'] = [
				'type' => 'select',
				'label-message' => 'visualeditor-preference-tabs',
				'section' => 'editing/editor',
				'options-messages' => [
					'visualeditor-preference-tabs-remember-last' => 'remember-last',
					'visualeditor-preference-tabs-prefer-ve' => 'prefer-ve',
					'visualeditor-preference-tabs-prefer-wt' => 'prefer-wt',
					'visualeditor-preference-tabs-multi-tab' => 'multi-tab'
				]
			];
		}

		$api = [ 'type' => 'api' ];
		// The "autodisable" preference records whether the user has explicitly opted out of VE.
		// This is saved even when VE is off by default, which allows changing it to be on by default
		// without affecting the users who opted out. There's also a maintenance script to silently
		// opt-out existing users en masse before changing the default, thus only affecting new users.
		// (This option is no longer set to 'true' anywhere, but we can still encounter old true
		// values until they are migrated: T344760.)
		$preferences['visualeditor-autodisable'] = $api;
		// The diff mode is persisted for each editor mode separately,
		// e.g. use visual diffs for visual mode only.
		$preferences['visualeditor-diffmode-source'] = $api;
		$preferences['visualeditor-diffmode-visual'] = $api;
		$preferences['visualeditor-diffmode-historical'] = $api;
		$preferences['visualeditor-editor'] = $api;
		$preferences['visualeditor-hidebetawelcome'] = $api;
		$preferences['visualeditor-hidetabdialog'] = $api;
		$preferences['visualeditor-hidesourceswitchpopup'] = $api;
		$preferences['visualeditor-hidevisualswitchpopup'] = $api;
		$preferences['visualeditor-hideusered'] = $api;
		$preferences['visualeditor-findAndReplace-diacritic'] = $api;
		$preferences['visualeditor-findAndReplace-findText'] = $api;
		$preferences['visualeditor-findAndReplace-replaceText'] = $api;
		$preferences['visualeditor-findAndReplace-regex'] = $api;
		$preferences['visualeditor-findAndReplace-matchCase'] = $api;
		$preferences['visualeditor-findAndReplace-word'] = $api;
	}

	/**
	 * Implements the PreferencesFormPreSave hook, to remove the 'autodisable' flag
	 * when the user it was set on explicitly enables VE.
	 *
	 * @param array $data User-submitted data
	 * @param HTMLForm $form A ContextSource
	 * @param User $user User with new preferences already set
	 * @param bool &$result Success or failure
	 * @param array $oldUserOptions
	 */
	public function onPreferencesFormPreSave( $data, $form, $user, &$result, $oldUserOptions ) {
		$services = MediaWikiServices::getInstance();
		$veConfig = $services->getConfigFactory()->makeConfig( 'visualeditor' );
		$userOptionsManager = $services->getUserOptionsManager();
		$isBeta = $veConfig->get( 'VisualEditorEnableBetaFeature' );

		// The "autodisable" preference records whether the user has explicitly opted out of VE
		// while it was in beta (which would otherwise not be saved, since it's the same as default).

		if (
			// When the user enables VE, clear the preference.
			$userOptionsManager->getOption( $user, 'visualeditor-autodisable' ) &&
			( $isBeta ?
				$userOptionsManager->getOption( $user, 'visualeditor-enable' ) :
				!$userOptionsManager->getOption( $user, 'visualeditor-betatempdisable' ) )
		) {
			$userOptionsManager->setOption( $user, 'visualeditor-autodisable', false );
		}
	}

	/**
	 * @param array &$tags
	 */
	public function onChangeTagsListActive( &$tags ) {
		$this->onListDefinedTags( $tags );
	}

	/**
	 * Implements the ListDefinedTags and ChangeTagsListActive hooks, to
	 * populate core Special:Tags with the change tags in use by VisualEditor.
	 *
	 * @param array &$tags Available change tags.
	 */
	public function onListDefinedTags( &$tags ) {
		$tags = array_merge( $tags, static::TAGS );
	}

	/**
	 * Adds extra variables to the page config.
	 *
	 * @param array &$vars Global variables object
	 * @param OutputPage $out The page view.
	 */
	public function onMakeGlobalVariablesScript( &$vars, $out ): void {
		$pageLanguage = ApiVisualEditor::getPageLanguage( $out->getTitle() );
		$converter = MediaWikiServices::getInstance()->getLanguageConverterFactory()
			->getLanguageConverter( $pageLanguage );

		$fallbacks = $converter->getVariantFallbacks( $converter->getPreferredVariant() );

		$vars['wgVisualEditor'] = [
			'pageLanguageCode' => $pageLanguage->getHtmlCode(),
			'pageLanguageDir' => $pageLanguage->getDir(),
			'pageVariantFallbacks' => $fallbacks,
		];
	}

	/**
	 * Adds extra variables to the global config
	 *
	 * @param array &$vars Global variables object
	 * @param string $skin
	 * @param Config $config
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$coreConfig = RequestContext::getMain()->getConfig();
		$services = MediaWikiServices::getInstance();
		$veConfig = $services->getConfigFactory()->makeConfig( 'visualeditor' );
		$extensionRegistry = ExtensionRegistry::getInstance();
		$availableNamespaces = ApiVisualEditor::getAvailableNamespaceIds( $veConfig );
		$availableContentModels = array_filter(
			array_merge(
				$extensionRegistry->getAttribute( 'VisualEditorAvailableContentModels' ),
				$veConfig->get( 'VisualEditorAvailableContentModels' )
			)
		);

		$namespacesWithSubpages = $coreConfig->get( 'NamespacesWithSubpages' );
		// Export as a list of namespaces where subpages are enabled instead of an object
		// mapping namespaces to if subpages are enabled or not, so filter out disabled
		// namespaces and then just use the keys. See T291729.
		$namespacesWithSubpages = array_filter( $namespacesWithSubpages );
		$namespacesWithSubpagesEnabled = array_keys( $namespacesWithSubpages );
		// $wgNamespacesWithSubpages can include namespaces that don't exist, no need
		// to include those in the JavaScript data. See T291727.
		// Run this filtering after the filter for subpages being enabled, to reduce
		// the number of calls needed to namespace info.
		$nsInfo = $services->getNamespaceInfo();
		$namespacesWithSubpagesEnabled = array_values( array_filter(
			$namespacesWithSubpagesEnabled,
			[ $nsInfo, 'exists' ]
		) );

		$defaultSortPrefix = $services->getMagicWordFactory()->get( 'defaultsort' )->getSynonym( 0 );
		// Sanitize trailing colon. /languages/messages/*.php are not consistent but the
		// presence or absence of a trailing colon in the message makes no difference.
		$defaultSortPrefix = preg_replace( '/:$/', '', $defaultSortPrefix );

		$vars['wgVisualEditorConfig'] = [
			'usePageImages' => $extensionRegistry->isLoaded( 'PageImages' ),
			'usePageDescriptions' => $extensionRegistry->isLoaded( 'WikibaseClient' ),
			'isBeta' => $veConfig->get( 'VisualEditorEnableBetaFeature' ),
			'disableForAnons' => $veConfig->get( 'VisualEditorDisableForAnons' ),
			'preloadModules' => $veConfig->get( 'VisualEditorPreloadModules' ),
			'namespaces' => $availableNamespaces,
			'contentModels' => $availableContentModels,
			'pluginModules' => array_merge(
				$extensionRegistry->getAttribute( 'VisualEditorPluginModules' ),
				// @todo deprecate the global setting
				$veConfig->get( 'VisualEditorPluginModules' )
			),
			'thumbLimits' => $coreConfig->get( 'ThumbLimits' ),
			'galleryOptions' => $coreConfig->get( 'GalleryOptions' ),
			'unsupportedList' => $veConfig->get( 'VisualEditorBrowserUnsupportedList' ),
			'tabPosition' => $veConfig->get( 'VisualEditorTabPosition' ),
			'tabMessages' => array_filter( $veConfig->get( 'VisualEditorTabMessages' ) ),
			'singleEditTab' => $veConfig->get( 'VisualEditorUseSingleEditTab' ),
			'enableVisualSectionEditing' => $veConfig->get( 'VisualEditorEnableVisualSectionEditing' ),
			'showBetaWelcome' => $veConfig->get( 'VisualEditorShowBetaWelcome' ),
			'allowExternalLinkPaste' => $veConfig->get( 'VisualEditorAllowExternalLinkPaste' ),
			'enableHelpCompletion' => $veConfig->get( 'VisualEditorEnableHelpCompletion' ),
			'enableTocWidget' => $veConfig->get( 'VisualEditorEnableTocWidget' ),
			'enableWikitext' => $veConfig->get( 'VisualEditorEnableWikitext' ),
			'useChangeTagging' => $veConfig->get( 'VisualEditorUseChangeTagging' ),
			'editCheckTagging' => $veConfig->get( 'VisualEditorEditCheckTagging' ),
			'editCheck' => $veConfig->get( 'VisualEditorEditCheck' ),
			'editCheckABTest' => $veConfig->get( 'VisualEditorEditCheckABTest' ),
			'namespacesWithSubpages' => $namespacesWithSubpagesEnabled,
			'specialBooksources' => urldecode( SpecialPage::getTitleFor( 'Booksources' )->getPrefixedURL() ),
			'rebaserUrl' => $coreConfig->get( 'VisualEditorRebaserURL' ),
			'feedbackApiUrl' => $veConfig->get( 'VisualEditorFeedbackAPIURL' ),
			'feedbackTitle' => $veConfig->get( 'VisualEditorFeedbackTitle' ),
			'sourceFeedbackTitle' => $veConfig->get( 'VisualEditorSourceFeedbackTitle' ),
			// TODO: Remove when all usages in .js files are removed
			'transclusionDialogNewSidebar' => true,
			'cirrusSearchLookup' => $extensionRegistry->isLoaded( 'CirrusSearch' ),
			'defaultSortPrefix' => $defaultSortPrefix,
		];
	}

	/**
	 * Conditionally register the jquery.uls.data and jquery.i18n modules, in case they've already
	 * been registered by the UniversalLanguageSelector extension or the TemplateData extension.
	 *
	 * @param ResourceLoader $resourceLoader Client-side code and assets to be loaded.
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
		$veResourceTemplate = [
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'VisualEditor',
		];

		// Only register VisualEditor core's local version of jquery.uls.data if it hasn't been
		// installed locally already (presumably, by the UniversalLanguageSelector extension).
		if ( !$resourceLoader->isModuleRegistered( 'jquery.uls.data' ) ) {
			$resourceLoader->register( [
				'jquery.uls.data' => $veResourceTemplate + [
					'scripts' => [
						'lib/ve/lib/jquery.uls/src/jquery.uls.data.js',
						'lib/ve/lib/jquery.uls/src/jquery.uls.data.utils.js',
					],
				] ] );
		}
	}

	/**
	 * Ensures that we know whether we're running inside a parser test.
	 *
	 * @param array &$settings The settings with which MediaWiki is being run.
	 */
	public function onParserTestGlobals( &$settings ) {
		$settings['wgVisualEditorInParserTests'] = true;
	}

	/**
	 * @param array &$redirectParams Parameters preserved on special page redirects
	 *   to wiki pages
	 */
	public function onRedirectSpecialArticleRedirectParams( &$redirectParams ) {
		$redirectParams[] = 'veaction';
	}

	/**
	 * If the user has specified that they want to edit the page with VE, suppress any redirect.
	 *
	 * @param Title $title Title being used for request
	 * @param Article|null $article The page being viewed.
	 * @param OutputPage $output The page view.
	 * @param User $user The user-specific settings.
	 * @param WebRequest $request
	 * @param ActionEntryPoint $mediaWiki Helper class.
	 */
	public function onBeforeInitialize(
		$title, $article, $output, $user, $request, $mediaWiki
	) {
		if ( $request->getVal( 'veaction' ) ) {
			$request->setVal( 'redirect', 'no' );
		}
	}

	/**
	 * On login, if user has a VEE cookie, set their preference equal to it.
	 *
	 * @param User $user The user-specific settings.
	 */
	public function onUserLoggedIn( $user ) {
		$cookie = RequestContext::getMain()->getRequest()->getCookie( 'VEE', '' );
		if ( $user->isNamed() && ( $cookie === 'visualeditor' || $cookie === 'wikitext' ) ) {
			self::deferredSetUserOption( $user, 'visualeditor-editor', $cookie );
		}
	}
}
