<?php
/**
 * DiscussionTools extension hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use ExtensionRegistry;
use IContextSource;
use IDBAccessObject;
use LqtDispatch;
use MediaWiki\Extension\DiscussionTools\CommentParser;
use MediaWiki\Extension\DiscussionTools\CommentUtils;
use MediaWiki\Extension\DiscussionTools\ContentThreadItemSet;
use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\ParserOutputAccess;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserIdentity;
use ParserOptions;
use RequestContext;
use RuntimeException;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Core\ResourceLimitExceededException;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class HookUtils {

	public const REPLYTOOL = 'replytool';
	public const NEWTOPICTOOL = 'newtopictool';
	public const SOURCEMODETOOLBAR = 'sourcemodetoolbar';
	public const TOPICSUBSCRIPTION = 'topicsubscription';
	public const AUTOTOPICSUB = 'autotopicsub';
	public const VISUALENHANCEMENTS = 'visualenhancements';
	public const VISUALENHANCEMENTS_REPLY = 'visualenhancements_reply';
	public const VISUALENHANCEMENTS_PAGEFRAME = 'visualenhancements_pageframe';

	/**
	 * @var string[] List of all sub-features. Will be used to generate:
	 *  - Body class: ext-discussiontools-FEATURE-enabled
	 *  - User option: discussiontools-FEATURE
	 */
	public const FEATURES = [
		// Can't use static:: in compile-time constants
		self::REPLYTOOL,
		self::NEWTOPICTOOL,
		self::SOURCEMODETOOLBAR,
		self::TOPICSUBSCRIPTION,
		self::AUTOTOPICSUB,
		self::VISUALENHANCEMENTS,
		self::VISUALENHANCEMENTS_REPLY,
		self::VISUALENHANCEMENTS_PAGEFRAME,
	];

	/**
	 * @var string[] List of configurable sub-features, used to generate:
	 *  - Feature override global: $wgDiscussionTools_FEATURE
	 */
	public const CONFIGS = [
		self::VISUALENHANCEMENTS,
		self::VISUALENHANCEMENTS_REPLY,
		self::VISUALENHANCEMENTS_PAGEFRAME,
	];

	public const FEATURES_CONFLICT_WITH_GADGET = [
		self::REPLYTOOL,
	];

	private const CACHED_PAGE_PROPS = [
		'newsectionlink',
		'nonewsectionlink',
		'notalk',
		'archivedtalk',
	];
	private static array $propCache = [];

	/**
	 * Check if a title has a page prop, and use an in-memory cache to avoid extra queries
	 *
	 * @param Title $title Title
	 * @param string $prop Page property
	 * @return bool Title has page property
	 */
	public static function hasPagePropCached( Title $title, string $prop ): bool {
		Assert::parameter(
			in_array( $prop, self::CACHED_PAGE_PROPS, true ),
			'$prop',
			'must be one of the cached properties'
		);
		$id = $title->getArticleId();
		if ( !isset( self::$propCache[ $id ] ) ) {
			$services = MediaWikiServices::getInstance();
			// Always fetch all of our properties, we need to check several of them on most requests
			$pagePropsPerId = $services->getPageProps()->getProperties( $title, self::CACHED_PAGE_PROPS );
			if ( $pagePropsPerId ) {
				self::$propCache += $pagePropsPerId;
			} else {
				self::$propCache[ $id ] = [];
			}
		}
		return isset( self::$propCache[ $id ][ $prop ] );
	}

	/**
	 * Parse a revision by using the discussion parser on the HTML provided by Parsoid.
	 *
	 * @param RevisionRecord $revRecord
	 * @param string|false $updateParserCacheFor Whether the parser cache should be updated on cache miss.
	 *        May be set to false for batch operations to avoid flooding the cache.
	 *        Otherwise, it should be set to the name of the calling method (__METHOD__),
	 *        so we can track what is causing parser cache writes.
	 *
	 * @return ContentThreadItemSet
	 * @throws ResourceLimitExceededException
	 */
	public static function parseRevisionParsoidHtml(
		RevisionRecord $revRecord,
		$updateParserCacheFor
	): ContentThreadItemSet {
		$services = MediaWikiServices::getInstance();
		$mainConfig = $services->getMainConfig();
		$parserOutputAccess = $services->getParserOutputAccess();

		// Look up the page by ID in master. If we just used $revRecord->getPage(),
		// ParserOutputAccess would look it up by namespace+title in replica.
		$pageRecord = $services->getPageStore()->getPageById( $revRecord->getPageId() ) ?:
			$services->getPageStore()->getPageById( $revRecord->getPageId(), IDBAccessObject::READ_LATEST );
		Assert::postcondition( $pageRecord !== null, 'Revision had no page' );

		$parserOptions = ParserOptions::newFromAnon();
		$parserOptions->setUseParsoid();

		if ( $updateParserCacheFor ) {
			// $updateParserCache contains the name of the calling method
			$parserOptions->setRenderReason( $updateParserCacheFor );
		}

		$status = $parserOutputAccess->getParserOutput(
			$pageRecord,
			$parserOptions,
			$revRecord,
			// Don't flood the parser cache
			$updateParserCacheFor ? 0 : ParserOutputAccess::OPT_NO_UPDATE_CACHE
		);

		if ( !$status->isOK() ) {
			[ 'message' => $key, 'params' => $params ] = $status->getErrors()[0];
			// XXX: HtmlOutputRendererHelper checks for a resource limit exceeded message as well,
			// maybe we shouldn't be catching that so low down.  Re-throw for callers.
			if ( $status->hasMessage( 'parsoid-resource-limit-exceeded' ) ) {
				throw new ResourceLimitExceededException( $params[0] ?? '' );
			}
			$message = wfMessage( $key, ...$params );
			throw new RuntimeException( $message->inLanguage( 'en' )->useDatabase( false )->text() );
		}

		$parserOutput = $status->getValue();
		$html = $parserOutput->getRawText();

		// Run the discussion parser on it
		$doc = DOMUtils::parseHTML( $html );
		$container = DOMCompat::getBody( $doc );

		// Unwrap sections, so that transclusions overlapping section boundaries don't cause all
		// comments in the sections to be treated as transcluded from another page.
		CommentUtils::unwrapParsoidSections( $container );

		/** @var CommentParser $parser */
		$parser = $services->getService( 'DiscussionTools.CommentParser' );
		$title = TitleValue::newFromPage( $revRecord->getPage() );
		return $parser->parse( $container, $title );
	}

	/**
	 * @param UserIdentity $user
	 * @param string $feature Feature to check for
	 * @return bool
	 */
	public static function featureConflictsWithGadget( UserIdentity $user, string $feature ) {
		$dtConfig = MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'discussiontools' );
		$gadgetName = $dtConfig->get( 'DiscussionToolsConflictingGadgetName' );
		if ( !$gadgetName ) {
			return false;
		}

		if ( !in_array( $feature, static::FEATURES_CONFLICT_WITH_GADGET, true ) ) {
			return false;
		}

		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( $extensionRegistry->isLoaded( 'Gadgets' ) ) {
			$gadgetsRepo = GadgetRepo::singleton();
			$match = array_search( $gadgetName, $gadgetsRepo->getGadgetIds(), true );
			if ( $match !== false ) {
				try {
					return $gadgetsRepo->getGadget( $gadgetName )
						->isEnabled( $user );
				} catch ( \InvalidArgumentException $e ) {
					return false;
				}
			}
		}
		return false;
	}

	/**
	 * Check if a DiscussionTools feature is available to this user
	 *
	 * @param UserIdentity $user
	 * @param string|null $feature Feature to check for (one of static::FEATURES)
	 *  Null will check for any DT feature.
	 * @return bool
	 */
	public static function isFeatureAvailableToUser( UserIdentity $user, ?string $feature = null ): bool {
		$services = MediaWikiServices::getInstance();
		$dtConfig = $services->getConfigFactory()->makeConfig( 'discussiontools' );

		$userIdentityUtils = $services->getUserIdentityUtils();
		if (
			( $feature === static::TOPICSUBSCRIPTION || $feature === static::AUTOTOPICSUB ) &&
			// Users must be logged in to use topic subscription, and Echo must be installed (T322498)
			( !$user->isRegistered() || $userIdentityUtils->isTemp( $user ) ||
				!ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) )
		) {
			return false;
		}

		$optionsLookup = $services->getUserOptionsLookup();

		if ( $feature ) {
			// Feature-specific override
			if ( !in_array( $feature, static::CONFIGS, true ) ) {
				// Feature is not configurable, always available
				return true;
			}
			if ( $dtConfig->get( 'DiscussionTools_' . $feature ) !== 'default' ) {
				// Feature setting can be 'available' or 'unavailable', overriding any BetaFeatures settings
				return $dtConfig->get( 'DiscussionTools_' . $feature ) === 'available';
			}
		} else {
			// Some features are always available, so if no feature is
			// specified (i.e. checking for any feature), always return true.
			return true;
		}

		// Being in the "test" group for this feature means it's enabled. This
		// overrules the wiki's beta feature setting. (However, a user who's
		// in the control group can still bypass this and enable the feature
		// normally.)
		$abtest = static::determineUserABTestBucket( $user, $feature );
		if ( $abtest === 'test' ) {
			return true;
		}

		// No feature-specific override found.

		if ( $dtConfig->get( 'DiscussionToolsBeta' ) ) {
			$betaenabled = $optionsLookup->getOption( $user, 'discussiontools-betaenable', 0 );
			return (bool)$betaenabled;
		}

		return true;
	}

	/**
	 * Check if a DiscussionTools feature is enabled by this user
	 *
	 * @param UserIdentity $user
	 * @param string|null $feature Feature to check for (one of static::FEATURES)
	 *  Null will check for any DT feature.
	 * @return bool
	 */
	public static function isFeatureEnabledForUser( UserIdentity $user, ?string $feature = null ): bool {
		if ( !static::isFeatureAvailableToUser( $user, $feature ) ) {
			return false;
		}
		$services = MediaWikiServices::getInstance();
		$optionsLookup = $services->getUserOptionsLookup();
		if ( $feature ) {
			if ( static::featureConflictsWithGadget( $user, $feature ) ) {
				return false;
			}
			// Check for a specific feature
			$enabled = $optionsLookup->getOption( $user, 'discussiontools-' . $feature );
			// `null` means there is no user option for this feature, so it must be enabled
			return $enabled === null ? true : $enabled;
		} else {
			// Check for any feature
			foreach ( static::FEATURES as $feat ) {
				if ( $optionsLookup->getOption( $user, 'discussiontools-' . $feat ) ) {
					return true;
				}
			}
			return false;
		}
	}

	/**
	 * Work out the A/B test bucket for the current user
	 *
	 * Currently this just checks whether the user is logged in, and assigns
	 * them to a consistent bucket based on their ID.
	 *
	 * @param UserIdentity $user
	 * @param string|null $feature Feature to check for (one of static::FEATURES)
	 *  Null will check for any DT feature.
	 * @return string 'test' if in the test group, 'control' if in the control group, or '' if
	 * 	they're not in the test
	 */
	public static function determineUserABTestBucket( UserIdentity $user, ?string $feature = null ): string {
		$services = MediaWikiServices::getInstance();
		$dtConfig = $services->getConfigFactory()->makeConfig( 'discussiontools' );

		$abtest = $dtConfig->get( 'DiscussionToolsABTest' );
		if ( !$abtest ) {
			return '';
		}

		if (
			( $feature ? in_array( $feature, (array)$abtest, true ) : (bool)$abtest ) &&
			$user->isRegistered()
		) {
			return $user->getId() % 2 === 0 ? 'test' : 'control';
		}
		return '';
	}

	/**
	 * Check if the tools are available for a given title
	 *
	 * Keep in sync with SQL conditions in persistRevisionThreadItems.php.
	 *
	 * @param Title $title
	 * @param string|null $feature Feature to check for (one of static::FEATURES)
	 *  Null will check for any DT feature.
	 * @return bool
	 */
	public static function isAvailableForTitle( Title $title, ?string $feature = null ): bool {
		// Only wikitext pages (e.g. not Flow boards, special pages)
		if ( $title->getContentModel() !== CONTENT_MODEL_WIKITEXT ) {
			return false;
		}
		// LiquidThreads needs a separate check, since it predates content models other than wikitext (T329423)
		// @phan-suppress-next-line PhanUndeclaredClassMethod
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Liquid Threads' ) && LqtDispatch::isLqtPage( $title ) ) {
			return false;
		}
		if ( !$title->canExist() ) {
			return false;
		}

		// ARCHIVEDTALK/NOTALK magic words
		if ( self::hasPagePropCached( $title, 'notalk' ) ) {
			return false;
		}
		if (
			$feature === static::REPLYTOOL &&
			self::hasPagePropCached( $title, 'archivedtalk' )
		) {
			return false;
		}

		$services = MediaWikiServices::getInstance();

		if ( $feature === static::VISUALENHANCEMENTS ) {
			$dtConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'discussiontools' );
			// Visual enhancements are only enabled on talk namespaces (T325417) ...
			return $title->isTalkPage() || (
				// ... or __NEWSECTIONLINK__ pages (T331635)
				static::hasPagePropCached( $title, 'newsectionlink' ) &&
				// excluding the main namespace, unless it has been configured for signatures
				(
					!$title->inNamespace( NS_MAIN ) ||
					$services->getNamespaceInfo()->wantSignatures( $title->getNamespace() )
				)
			);
		}

		// Check that the page supports discussions.
		return (
			// Talk namespaces, and other namespaces where the signature button is shown in wikitext
			// editor using $wgExtraSignatureNamespaces (T249036)
			$services->getNamespaceInfo()->wantSignatures( $title->getNamespace() ) ||
			// Treat pages with __NEWSECTIONLINK__ as talk pages (T245890)
			static::hasPagePropCached( $title, 'newsectionlink' )
		);
	}

	/**
	 * Check if the tool is available on a given page
	 *
	 * @param OutputPage $output
	 * @param string|null $feature Feature to check for (one of static::FEATURES)
	 *  Null will check for any DT feature.
	 * @return bool
	 */
	public static function isFeatureEnabledForOutput( OutputPage $output, ?string $feature = null ): bool {
		// Only show on normal page views (not history etc.), and in edit mode for previews
		if (
			// Don't try to call $output->getActionName if testing for NEWTOPICTOOL as we use
			// the hook onGetActionName to override the action for the tool on empty pages.
			// If we tried to call it here it would set up infinite recursion (T312689)
			$feature !== static::NEWTOPICTOOL && !(
				in_array( $output->getActionName(), [ 'view', 'edit', 'submit' ], true ) ||
				// Subscriptions (specifically page-level subscriptions) are available on history pages (T345096)
				(
					$output->getActionName() === 'history' &&
					$feature === static::TOPICSUBSCRIPTION
				)
			)
		) {
			return false;
		}

		$title = $output->getTitle();
		// Don't show on pages without a Title
		if ( !$title ) {
			return false;
		}

		// Topic subscription is not available on your own talk page, as you will
		// get 'edit-user-talk' notifications already. (T276996)
		if (
			( $feature === static::TOPICSUBSCRIPTION || $feature === static::AUTOTOPICSUB ) &&
			$title->equals( $output->getUser()->getTalkPage() )
		) {
			return false;
		}

		// Subfeatures are disabled if the main feature is disabled
		if ( (
			$feature === static::VISUALENHANCEMENTS_REPLY ||
			$feature === static::VISUALENHANCEMENTS_PAGEFRAME
		) && !self::isFeatureEnabledForOutput( $output, static::VISUALENHANCEMENTS ) ) {
			return false;
		}

		// ?dtenable=1 overrides all user and title checks
		$queryEnable = $output->getRequest()->getRawVal( 'dtenable' ) ?:
			// Extra hack for parses from API, where this parameter isn't passed to derivative requests
			RequestContext::getMain()->getRequest()->getRawVal( 'dtenable' );

		if ( $queryEnable ) {
			return true;
		}

		if ( $queryEnable === '0' ) {
			// ?dtenable=0 forcibly disables the feature regardless of any other checks (T285578)
			return false;
		}

		if ( !static::isAvailableForTitle( $title, $feature ) ) {
			return false;
		}

		$isMobile = false;
		if ( ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			$mobFrontContext = MediaWikiServices::getInstance()->getService( 'MobileFrontend.Context' );
			$isMobile = $mobFrontContext->shouldDisplayMobileView();
		}
		$dtConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'discussiontools' );

		if ( $isMobile ) {
			return $feature === null ||
				$feature === static::REPLYTOOL ||
				$feature === static::NEWTOPICTOOL ||
				$feature === static::SOURCEMODETOOLBAR ||
				// Even though mobile ignores user preferences, TOPICSUBSCRIPTION must
				// still be disabled if the user isn't registered.
				( $feature === static::TOPICSUBSCRIPTION && $output->getUser()->isNamed() ) ||
				$feature === static::VISUALENHANCEMENTS ||
				$feature === static::VISUALENHANCEMENTS_REPLY ||
				$feature === static::VISUALENHANCEMENTS_PAGEFRAME;
		}

		return static::isFeatureEnabledForUser( $output->getUser(), $feature );
	}

	/**
	 * Check if the "New section" tab would be shown in a normal skin.
	 */
	public static function shouldShowNewSectionTab( IContextSource $context ): bool {
		$title = $context->getTitle();
		$output = $context->getOutput();

		// Match the logic in MediaWiki core (as defined in SkinTemplate::buildContentNavigationUrlsInternal):
		// https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/core/+/add6d0a0e38167a710fb47fac97ff3004451494c/includes/skins/SkinTemplate.php#1317
		// * __NONEWSECTIONLINK__ is not present (OutputPage::forceHideNewSectionLink) and...
		//   - This is the current revision of a non-redirect in a talk namespace or...
		//   - __NEWSECTIONLINK__ is present (OutputPage::showNewSectionLink)
		return (
			!static::hasPagePropCached( $title, 'nonewsectionlink' ) &&
			( ( $title->isTalkPage() && !$title->isRedirect() && $output->isRevisionCurrent() ) ||
				static::hasPagePropCached( $title, 'newsectionlink' ) )
		);
	}

	/**
	 * Check if this page view should open the new topic tool on page load.
	 */
	public static function shouldOpenNewTopicTool( IContextSource $context ): bool {
		$req = $context->getRequest();
		$out = $context->getOutput();
		$hasPreload = $req->getCheck( 'editintro' ) || $req->getCheck( 'preload' ) ||
			$req->getCheck( 'preloadparams' ) || $req->getCheck( 'preloadtitle' ) ||
			// Switching or previewing from an external tool (T316333)
			$req->getCheck( 'wpTextbox1' );

		return (
			// ?title=...&action=edit&section=new
			// ?title=...&veaction=editsource&section=new
			( $req->getRawVal( 'action' ) === 'edit' || $req->getRawVal( 'veaction' ) === 'editsource' ) &&
			$req->getRawVal( 'section' ) === 'new' &&
			// Handle new topic with preloaded text only when requested (T269310)
			( $req->getCheck( 'dtpreload' ) || !$hasPreload ) &&
			// User has new topic tool enabled (and not using &dtenable=0)
			static::isFeatureEnabledForOutput( $out, static::NEWTOPICTOOL )
		);
	}

	/**
	 * Check if this page view should display the "empty state" message for empty talk pages.
	 */
	public static function shouldDisplayEmptyState( IContextSource $context ): bool {
		$req = $context->getRequest();
		$out = $context->getOutput();
		$user = $context->getUser();
		$title = $context->getTitle();

		$optionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();

		return (
			(
				// When following a red link from another page (but not when clicking the 'Edit' tab)
				(
					$req->getRawVal( 'action' ) === 'edit' && $req->getRawVal( 'redlink' ) === '1' &&
					// …if not disabled by the user
					$optionsLookup->getOption( $user, 'discussiontools-newtopictool-createpage' )
				) ||
				// When the new topic tool will be opened (usually when clicking the 'Add topic' tab)
				static::shouldOpenNewTopicTool( $context ) ||
				// In read mode (accessible for non-existent pages by clicking 'Cancel' in editor)
				$req->getRawVal( 'action', 'view' ) === 'view'
			) &&
			// Only in talk namespaces, not including other namespaces that isAvailableForTitle() allows
			$title->isTalkPage() &&
			// Only if the subject page or the user exists (T288319, T312560)
			static::pageSubjectExists( $title ) &&
			// The default display will probably be more useful for links to old revisions of deleted
			// pages (existing pages are already excluded in shouldShowNewSectionTab())
			$req->getIntOrNull( 'oldid' ) === null &&
			// Only if "New section" tab would be shown by the skin.
			// If the page doesn't exist, this only happens in talk namespaces.
			// If the page exists, it also considers magic words on the page.
			static::shouldShowNewSectionTab( $context ) &&
			// User has new topic tool enabled (and not using &dtenable=0)
			static::isFeatureEnabledForOutput( $out, static::NEWTOPICTOOL )
		);
	}

	/**
	 * Return whether the corresponding subject page exists, or (if the page is a user talk page,
	 * excluding subpages) whether the user is registered or a valid IP address.
	 */
	private static function pageSubjectExists( LinkTarget $talkPage ): bool {
		$services = MediaWikiServices::getInstance();
		$namespaceInfo = $services->getNamespaceInfo();
		Assert::precondition( $namespaceInfo->isTalk( $talkPage->getNamespace() ), "Page is a talk page" );

		if ( $talkPage->inNamespace( NS_USER_TALK ) && !str_contains( $talkPage->getText(), '/' ) ) {
			if ( $services->getUserNameUtils()->isIP( $talkPage->getText() ) ) {
				return true;
			}
			$subjectUser = $services->getUserFactory()->newFromName( $talkPage->getText() );
			if ( $subjectUser && $subjectUser->isRegistered() ) {
				return true;
			}
			return false;
		} else {
			$subjectPage = $namespaceInfo->getSubjectPage( $talkPage );
			return $services->getPageStore()->getPageForLink( $subjectPage )->exists();
		}
	}

	/**
	 * Check if we should be adding automatic topic subscriptions for this user on this page.
	 */
	public static function shouldAddAutoSubscription( UserIdentity $user, Title $title ): bool {
		// This duplicates the logic from isFeatureEnabledForOutput(),
		// because we don't have access to the request or the output here.

		// Topic subscription is not available on your own talk page, as you will
		// get 'edit-user-talk' notifications already. (T276996)
		// (can't use User::getTalkPage() to check because this is a UserIdentity)
		if ( $title->inNamespace( NS_USER_TALK ) && $title->getText() === $user->getName() ) {
			return false;
		}

		// Users flagged as bots shouldn't be autosubscribed. They can
		// manually subscribe if it becomes relevant. (T301933)
		$user = MediaWikiServices::getInstance()
			->getUserFactory()
			->newFromUserIdentity( $user );
		if ( $user->isBot() ) {
			return false;
		}

		// Check if the user has automatic subscriptions enabled, and the tools are enabled on the page.
		return static::isAvailableForTitle( $title ) &&
			static::isFeatureEnabledForUser( $user, static::AUTOTOPICSUB );
	}
}
