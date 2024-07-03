<?php
/**
 * DiscussionTools page hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use Article;
use ExtensionRegistry;
use IContextSource;
use MediaWiki\Actions\Hook\GetActionNameHook;
use MediaWiki\Extension\DiscussionTools\CommentFormatter;
use MediaWiki\Extension\DiscussionTools\CommentUtils;
use MediaWiki\Extension\DiscussionTools\SubscriptionStore;
use MediaWiki\Extension\VisualEditor\Hooks as VisualEditorHooks;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Hook\BeforeDisplayNoArticleTextHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserNameUtils;
use OOUI\ButtonWidget;
use ParserOutput;
use RequestContext;
use Skin;
use SkinTemplate;

class PageHooks implements
	BeforeDisplayNoArticleTextHook,
	BeforePageDisplayHook,
	GetActionNameHook,
	OutputPageBeforeHTMLHook,
	OutputPageParserOutputHook,
	SidebarBeforeOutputHook,
	SkinTemplateNavigation__UniversalHook
{

	private SubscriptionStore $subscriptionStore;
	private UserNameUtils $userNameUtils;
	private UserOptionsLookup $userOptionsLookup;

	public function __construct(
		SubscriptionStore $subscriptionStore,
		UserNameUtils $userNameUtils,
		UserOptionsLookup $userOptionsLookup
	) {
		$this->subscriptionStore = $subscriptionStore;
		$this->userNameUtils = $userNameUtils;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	private function isMobile(): bool {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			/** @var \MobileContext $mobFrontContext */
			$mobFrontContext = MediaWikiServices::getInstance()->getService( 'MobileFrontend.Context' );
			return $mobFrontContext->shouldDisplayMobileView();
		}
		return false;
	}

	/**
	 * Adds DiscussionTools JS to the output.
	 *
	 * This is attached to the MediaWiki 'BeforePageDisplay' hook.
	 *
	 * @param OutputPage $output
	 * @param Skin $skin
	 * @return void This hook must not abort, it must return no value
	 */
	public function onBeforePageDisplay( $output, $skin ): void {
		$user = $output->getUser();
		$req = $output->getRequest();
		$title = $output->getTitle();

		foreach ( HookUtils::FEATURES as $feature ) {
			// Add a CSS class for each enabled feature
			if ( HookUtils::isFeatureEnabledForOutput( $output, $feature ) ) {
				// The following CSS classes are generated here:
				// * ext-discussiontools-replytool-enabled
				// * ext-discussiontools-newtopictool-enabled
				// * ext-discussiontools-sourcemodetoolbar-enabled
				// * ext-discussiontools-topicsubscription-enabled
				// * ext-discussiontools-autotopicsub-enabled
				// * ext-discussiontools-visualenhancements-enabled
				// * ext-discussiontools-visualenhancements_reply-enabled
				// * ext-discussiontools-visualenhancements_pageframe-enabled
				$output->addBodyClasses( "ext-discussiontools-$feature-enabled" );
			}
		}

		$isMobile = $this->isMobile();

		if ( $isMobile && HookUtils::isFeatureEnabledForOutput( $output, HookUtils::VISUALENHANCEMENTS ) ) {
			$output->addBodyClasses( 'collapsible-headings-collapsed' );
		}

		// Load style modules if the tools can be available for the title
		// to selectively hide DT features, depending on the body classes added above.
		if ( HookUtils::isAvailableForTitle( $title ) ) {
			$output->addModuleStyles( 'ext.discussionTools.init.styles' );
		}

		// Load modules if any DT feature is enabled for this user
		if ( HookUtils::isFeatureEnabledForOutput( $output ) ) {
			$output->addModules( 'ext.discussionTools.init' );

			$enabledVars = [];
			foreach ( HookUtils::FEATURES as $feature ) {
				$enabledVars[$feature] = HookUtils::isFeatureEnabledForOutput( $output, $feature );
			}
			$output->addJsConfigVars( 'wgDiscussionToolsFeaturesEnabled', $enabledVars );

			$editor = $this->userOptionsLookup->getOption( $user, 'discussiontools-editmode' );
			// User has no preferred editor yet
			// If the user has a preferred editor, this will be evaluated in the client
			if ( !$editor ) {
				// Check which editor we would use for articles
				// VE pref is 'visualeditor'/'wikitext'. Here we describe the mode,
				// not the editor, so 'visual'/'source'
				$editor = VisualEditorHooks::getPreferredEditor( $user, $req ) === 'visualeditor' ?
					'visual' : 'source';
				$output->addJsConfigVars(
					'wgDiscussionToolsFallbackEditMode',
					$editor
				);
			}
		}

		// This doesn't involve any DB checks, and so we can put it on every
		// page to make it easy to pick for logging in WikiEditor. If this
		// becomes not-cheap, move it elsewhere.
		$abstate = HookUtils::determineUserABTestBucket( $user );
		if ( $abstate ) {
			$output->addJsConfigVars(
				'wgDiscussionToolsABTestBucket',
				$abstate
			);
		}

		// Replace the action=edit&section=new form with the new topic tool.
		if ( HookUtils::shouldOpenNewTopicTool( $output->getContext() ) ) {
			$output->addJsConfigVars( 'wgDiscussionToolsStartNewTopicTool', true );

			// For no-JS compatibility, redirect to the old new section editor if JS is unavailable.
			// This isn't great, because the user has to load the page twice. But making a page that is
			// both a view mode and an edit mode seems difficult, so I'm cutting some corners here.
			// (Code below adapted from VisualEditor.)
			$params = $output->getRequest()->getValues();
			$params['dtenable'] = '0';
			$url = wfScript() . '?' . wfArrayToCgi( $params );
			$escapedUrl = htmlspecialchars( $url );

			// Redirect if the user has no JS (<noscript>)
			$output->addHeadItem(
				'dt-noscript-fallback',
				"<noscript><meta http-equiv=\"refresh\" content=\"0; url=$escapedUrl\"></noscript>"
			);
			// Redirect if the user has no ResourceLoader
			$output->addScript( Html::inlineScript(
				"(window.NORLQ=window.NORLQ||[]).push(" .
					"function(){" .
						"location.href=\"$url\";" .
					"}" .
				");"
			) );
		}

		if ( $isMobile ) {
			if (
				$title->isTalkPage() &&
				HookUtils::isFeatureEnabledForOutput( $output, HookUtils::REPLYTOOL ) && (
					// 'DiscussionTools-ledeButton' property may be already set to true or false.
					// Examine the other conditions only if it's unset.
					$output->getProperty( 'DiscussionTools-ledeButton' ) ?? (
						// Header shown on all talk pages, see Article::showNamespaceHeader
						!$output->getContext()->msg( 'talkpageheader' )->isDisabled() &&
						// Check if it isn't empty since it may use parser functions to only show itself on some pages
						trim( $output->getContext()->msg( 'talkpageheader' )->text() ) !== ''
					)
				)
			) {
				$output->addBodyClasses( 'ext-discussiontools-init-lede-hidden' );
				$output->enableOOUI();
				$output->prependHTML(
					Html::rawElement( 'div',
						[ 'class' => 'ext-discussiontools-init-lede-button-container' ],
						( new ButtonWidget( [
							'label' => $output->getContext()->msg( 'discussiontools-ledesection-button' )->text(),
							'classes' => [ 'ext-discussiontools-init-lede-button' ],
							'framed' => false,
							'icon' => 'info',
							'infusable' => true,
						] ) )
					)
				);

				// Preload jquery.makeCollapsible for LedeSectionDialog.
				// Using the same approach as in Skin::getDefaultModules in MediaWiki core.
				if ( str_contains( $output->getHTML(), 'mw-collapsible' ) ) {
					$output->addModules( 'jquery.makeCollapsible' );
					$output->addModuleStyles( 'jquery.makeCollapsible.styles' );
				}
			}
		}

		if ( $output->getSkin()->getSkinName() === 'minerva' ) {
			if (
				$req->getRawVal( 'action', 'view' ) === 'view' &&
				HookUtils::isFeatureEnabledForOutput( $output, HookUtils::NEWTOPICTOOL ) &&
				// Only add the button if "New section" tab would be shown in a normal skin.
				HookUtils::shouldShowNewSectionTab( $output->getContext() )
			) {
				$output->enableOOUI();
				// For speechBubbleAdd
				$output->addModuleStyles( 'oojs-ui.styles.icons-alerts' );
				$output->addBodyClasses( 'ext-discussiontools-init-new-topic-opened' );

				// Minerva doesn't show a new topic button.
				$output->addHTML( Html::rawElement( 'div',
					[ 'class' => 'ext-discussiontools-init-new-topic' ],
					( new ButtonWidget( [
						'classes' => [ 'ext-discussiontools-init-new-topic-button' ],
						'href' => $title->getLinkURL( [ 'action' => 'edit', 'section' => 'new' ] ),
						'icon' => 'speechBubbleAdd',
						'label' => $output->getContext()->msg( 'skin-action-addsection' )->text(),
						'flags' => [ 'progressive', 'primary' ],
						'infusable' => true,
					] ) )
						// For compatibility with MobileWebUIActionsTracking logging (T295490)
						->setAttributes( [ 'data-event-name' => 'talkpage.add-topic' ] )
				) );
			}

			if ( HookUtils::isFeatureEnabledForOutput( $output, HookUtils::TOPICSUBSCRIPTION ) ) {
				$output->addModuleStyles( 'ext.discussionTools.minervaicons' );
			}
		}
	}

	/**
	 * OutputPageBeforeHTML hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBeforeHTML
	 *
	 * @param OutputPage $output OutputPage object that corresponds to the page
	 * @param string &$text Text that will be displayed, in HTML
	 * @return bool|void This hook must not abort, it must return true or null.
	 */
	public function onOutputPageBeforeHTML( $output, &$text ) {
		// ParserOutputPostCacheTransform hook would be a better place to do this,
		// so that when the ParserOutput is used directly without using this hook,
		// we don't leave half-baked interface elements in it (see e.g. T292345, T294168).
		// But that hook doesn't provide parameters that we need to render correctly
		// (including the page title, interface language, and current user).

		// This hook can be executed more than once per page view if the page content is composed from
		// multiple sources!

		$isMobile = $this->isMobile();
		$visualEnhancementsEnabled =
			HookUtils::isFeatureEnabledForOutput( $output, HookUtils::VISUALENHANCEMENTS );
		$visualEnhancementsReplyEnabled =
			HookUtils::isFeatureEnabledForOutput( $output, HookUtils::VISUALENHANCEMENTS_REPLY );

		if ( HookUtils::isFeatureEnabledForOutput( $output, HookUtils::TOPICSUBSCRIPTION ) ) {
			// Just enable OOUI PHP - the OOUI subscribe button isn't infused unless VISUALENHANCEMENTS are enabled
			$output->setupOOUI();
			$text = CommentFormatter::postprocessTopicSubscription(
				$text, $output, $this->subscriptionStore, $isMobile, $visualEnhancementsEnabled
			);
		}

		if ( HookUtils::isFeatureEnabledForOutput( $output, HookUtils::REPLYTOOL ) ) {
			$output->enableOOUI();
			$text = CommentFormatter::postprocessReplyTool(
				$text, $output, $isMobile, $visualEnhancementsReplyEnabled
			);
		}

		if ( $visualEnhancementsEnabled ) {
			$output->enableOOUI();
			if ( HookUtils::isFeatureEnabledForOutput( $output, HookUtils::TOPICSUBSCRIPTION ) ) {
				// Visually enhanced topic subscriptions: bell, bellOutline
				$output->addModuleStyles( 'oojs-ui.styles.icons-alerts' );
			}
			if (
				$isMobile ||
				(
					$visualEnhancementsReplyEnabled &&
					CommentFormatter::isLanguageRequiringReplyIcon( $output->getLanguage() )
				)
			) {
				// Reply button: share
				$output->addModuleStyles( 'oojs-ui.styles.icons-content' );
			}
			if ( $isMobile ) {
				$output->addModuleStyles( [
					// Mobile overflow menu:
					// ellipsis
					'oojs-ui.styles.icons-interactions',
					// edit
					'oojs-ui.styles.icons-editing-core',
				] );
			}
			$text = CommentFormatter::postprocessVisualEnhancements( $text, $output, $isMobile );
		}
	}

	/**
	 * OutputPageParserOutput hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
	 *
	 * @param OutputPage $output
	 * @param ParserOutput $pout ParserOutput instance being added in $output
	 * @return void This hook must not abort, it must return no value
	 */
	public function onOutputPageParserOutput( $output, $pout ): void {
		// ParserOutputPostCacheTransform hook would be a better place to do this,
		// so that when the ParserOutput is used directly without using this hook,
		// we don't leave half-baked interface elements in it (see e.g. T292345, T294168).
		// But that hook doesn't provide parameters that we need to render correctly
		// (including the page title, interface language, and current user).

		// This hook can be executed more than once per page view if the page content is composed from
		// multiple sources!

		CommentFormatter::postprocessTableOfContents( $pout, $output );

		if (
			CommentFormatter::isEmptyTalkPage( $pout ) &&
			HookUtils::shouldDisplayEmptyState( $output->getContext() )
		) {
			$output->enableOOUI();
			CommentFormatter::appendToEmptyTalkPage(
				$pout, $this->getEmptyStateHtml( $output->getContext() )
			);
			$output->addBodyClasses( 'ext-discussiontools-emptystate-shown' );
		}

		if ( HookUtils::isFeatureEnabledForOutput( $output, HookUtils::VISUALENHANCEMENTS ) ) {
			$subtitle = CommentFormatter::postprocessVisualEnhancementsSubtitle( $pout, $output );

			if ( $subtitle ) {
				$output->addSubtitle( $subtitle );
			}
		}

		if ( $output->getSkin()->getSkinName() === 'minerva' ) {
			$title = $output->getTitle();

			if (
				$title->isTalkPage() &&
				HookUtils::isFeatureEnabledForOutput( $output, HookUtils::REPLYTOOL )
			) {
				if (
					CommentFormatter::hasCommentsInLedeContent( $pout )
				) {
					// If there are comments in the lede section, we can't really separate them from other lede
					// content, so keep the whole section visible.
					$output->setProperty( 'DiscussionTools-ledeButton', false );

				} elseif (
					CommentFormatter::hasLedeContent( $pout ) &&
					$output->getProperty( 'DiscussionTools-ledeButton' ) === null
				) {
					// If there is lede content and the lede button hasn't been disabled above, enable it.
					$output->setProperty( 'DiscussionTools-ledeButton', true );
				}
			}
		}
	}

	/**
	 * GetActionName hook handler
	 *
	 * @param IContextSource $context Request context
	 * @param string &$action Default action name, reassign to change it
	 * @return void This hook must not abort, it must return no value
	 */
	public function onGetActionName( IContextSource $context, string &$action ): void {
		if ( $action === 'edit' && (
			HookUtils::shouldOpenNewTopicTool( $context ) ||
			HookUtils::shouldDisplayEmptyState( $context )
		) ) {
			$action = 'view';
		}
	}

	/**
	 * BeforeDisplayNoArticleText hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
	 *
	 * @param Article $article The (empty) article
	 * @return bool|void This hook can abort
	 */
	public function onBeforeDisplayNoArticleText( $article ) {
		// We want to override the empty state for articles on which we would be enabled
		$context = $article->getContext();
		if ( !HookUtils::shouldDisplayEmptyState( $context ) ) {
			// Our empty states are all about using the new topic tool, but
			// expect to be on a talk page, so fall back if it's not
			// available or if we're in a non-talk namespace that still has
			// DT features enabled
			return true;
		}

		$output = $context->getOutput();
		$output->enableOOUI();
		$output->disableClientCache();

		$html = $this->getEmptyStateHtml( $context );

		$output->addHTML(
			// This being mw-parser-output is a lie, but makes the reply controller cope much better with everything
			Html::rawElement( 'div', [ 'class' => 'mw-parser-output noarticletext' ], $html )
		);
		$output->addBodyClasses( 'ext-discussiontools-emptystate-shown' );

		return false;
	}

	/**
	 * Generate HTML markup for the new topic tool's empty state, shown on talk pages that don't exist
	 * or have no topics.
	 *
	 * @param IContextSource $context
	 * @return string HTML
	 */
	private function getEmptyStateHtml( IContextSource $context ): string {
		$coreConfig = RequestContext::getMain()->getConfig();
		$iconpath = $coreConfig->get( 'ExtensionAssetsPath' ) . '/DiscussionTools/images';

		$descParams = [];
		$buttonMsg = 'discussiontools-emptystate-button';
		$title = $context->getTitle();
		if ( $title->inNamespace( NS_USER_TALK ) && !$title->isSubpage() ) {
			// This is a user talk page
			$isIP = $this->userNameUtils->isIP( $title->getText() );
			$isTemp = $this->userNameUtils->isTemp( $title->getText() );
			if ( $title->equals( $context->getUser()->getTalkPage() ) ) {
				// This is your own user talk page
				if ( $isIP || $isTemp ) {
					if ( $isIP ) {
						// You're an IP editor, so this is only *sort of* your talk page
						$titleMsg = 'discussiontools-emptystate-title-self-anon';
						$descMsg = 'discussiontools-emptystate-desc-self-anon';
					} else {
						// You're a temporary user, so you don't get some of the good stuff
						$titleMsg = 'discussiontools-emptystate-title-self-temp';
						$descMsg = 'discussiontools-emptystate-desc-self-temp';
					}
					$query = $context->getRequest()->getValues();
					unset( $query['title'] );
					$descParams = [
						SpecialPage::getTitleFor( 'CreateAccount' )->getFullURL( [
							'returnto' => $context->getTitle()->getFullText(),
							'returntoquery' => wfArrayToCgi( $query ),
						] ),
						SpecialPage::getTitleFor( 'Userlogin' )->getFullURL( [
							'returnto' => $context->getTitle()->getFullText(),
							'returntoquery' => wfArrayToCgi( $query ),
						] ),
					];
				} else {
					// You're logged in, this is very much your talk page
					$titleMsg = 'discussiontools-emptystate-title-self';
					$descMsg = 'discussiontools-emptystate-desc-self';
				}
				$buttonMsg = false;
			} elseif ( $isIP ) {
				// This is an IP editor
				$titleMsg = 'discussiontools-emptystate-title-user-anon';
				$descMsg = 'discussiontools-emptystate-desc-user-anon';
			} elseif ( $isTemp ) {
				// This is a temporary user
				$titleMsg = 'discussiontools-emptystate-title-user-temp';
				$descMsg = 'discussiontools-emptystate-desc-user-temp';
			} else {
				// This is any other user
				$titleMsg = 'discussiontools-emptystate-title-user';
				$descMsg = 'discussiontools-emptystate-desc-user';
			}
		} else {
			// This is any other page on which DT is enabled
			$titleMsg = 'discussiontools-emptystate-title';
			$descMsg = 'discussiontools-emptystate-desc';
		}

		$text =
			Html::rawElement( 'h3', [],
				$context->msg( $titleMsg )->parse()
			) .
			Html::rawElement( 'div', [ 'class' => 'plainlinks' ],
				$context->msg( $descMsg, $descParams )->parseAsBlock()
			);

		if ( $buttonMsg ) {
			$text .= new ButtonWidget( [
				'label' => $context->msg( $buttonMsg )->text(),
				'href' => $title->getLocalURL( 'action=edit&section=new' ),
				'flags' => [ 'primary', 'progressive' ]
			] );
		}

		$wrapped =
			Html::rawElement( 'div', [ 'class' => 'ext-discussiontools-emptystate' ],
				Html::rawElement( 'div', [ 'class' => 'ext-discussiontools-emptystate-text' ], $text ) .
				Html::element( 'img', [
					'src' => $iconpath . '/emptystate.svg',
					'class' => 'ext-discussiontools-emptystate-logo',
					// This is a purely decorative element
					'alt' => '',
				] )
			);

		return $wrapped;
	}

	/**
	 * @param Skin $skin
	 * @param array &$sidebar
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		$output = $skin->getOutput();
		if (
			$skin->getSkinName() === 'minerva' &&
			HookUtils::isFeatureEnabledForOutput( $output, HookUtils::TOPICSUBSCRIPTION )
		) {
			$button = $this->getNewTopicsSubscriptionButton(
				$skin->getUser(),
				$skin->getTitle(),
				$skin->getContext()
			);
			$sidebar['TOOLBOX']['t-page-subscribe'] = [
				'icon' => $button['icon'],
				'text' => $button['label'],
				'href' => $button['href'],
			];
		}
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 * @return void
	 * @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$output = $sktemplate->getOutput();
		if ( HookUtils::isFeatureEnabledForOutput( $output, HookUtils::TOPICSUBSCRIPTION ) ) {
			$button = $this->getNewTopicsSubscriptionButton(
				$sktemplate->getUser(),
				$sktemplate->getTitle(),
				$sktemplate->getContext()
			);

			$links['actions']['dt-page-subscribe'] = [
				'text' => $button['label'],
				'title' => $button['tooltip'],
				'data-mw-subscribed' => $button['isSubscribed'] ? '1' : '0',
				'href' => $button['href'],
			];

			$output->addModules( [ 'ext.discussionTools.init' ] );
		}
	}

	/**
	 * Get data from a new topics subcription button
	 *
	 * @param UserIdentity $user User
	 * @param Title $title Title
	 * @param IContextSource $context Context
	 * @return array Array containing label, tooltip, icon, isSubscribed and href.
	 */
	private function getNewTopicsSubscriptionButton(
		UserIdentity $user, Title $title, IContextSource $context
	): array {
		$items = $this->subscriptionStore->getSubscriptionItemsForUser(
			$user,
			[ CommentUtils::getNewTopicsSubscriptionId( $title ) ]
		);
		$subscriptionItem = count( $items ) ? $items[ 0 ] : null;
		$isSubscribed = $subscriptionItem && !$subscriptionItem->isMuted();

		return [
			'label' => $context->msg( $isSubscribed ?
				'discussiontools-newtopicssubscription-button-unsubscribe-label' :
				'discussiontools-newtopicssubscription-button-subscribe-label'
			)->text(),
			'tooltip' => $context->msg( $isSubscribed ?
				'discussiontools-newtopicssubscription-button-unsubscribe-tooltip' :
				'discussiontools-newtopicssubscription-button-subscribe-tooltip'
			)->text(),
			'icon' => $isSubscribed ? 'bell' : 'bellOutline',
			'isSubscribed' => $isSubscribed,
			'href' => $title->getLinkURL( [
				'action' => $isSubscribed ? 'dtunsubscribe' : 'dtsubscribe',
				'commentname' => CommentUtils::getNewTopicsSubscriptionId( $title ),
			] ),
		];
	}
}
