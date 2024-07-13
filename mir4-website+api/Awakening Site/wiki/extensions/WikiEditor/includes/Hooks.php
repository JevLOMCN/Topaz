<?php
/**
 * Hooks for WikiEditor extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\WikiEditor;

use ApiMessage;
use Article;
use Content;
use ExtensionRegistry;
use MediaWiki\Cache\CacheKeyHelper;
use MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook;
use MediaWiki\ChangeTags\Hook\ListDefinedTagsHook;
use MediaWiki\Config\Config;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\ConfirmEdit\Hooks as ConfirmEditHooks;
use MediaWiki\Extension\DiscussionTools\Hooks as DiscussionToolsHooks;
use MediaWiki\Extension\EventLogging\EventLogging;
use MediaWiki\Hook\EditPage__attemptSave_afterHook;
use MediaWiki\Hook\EditPage__attemptSaveHook;
use MediaWiki\Hook\EditPage__showEditForm_fieldsHook;
use MediaWiki\Hook\EditPage__showEditForm_initialHook;
use MediaWiki\Hook\EditPageGetPreviewContentHook;
use MediaWiki\Hook\RecentChange_saveHook;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Request\WebRequest;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\Status\Status;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserEditTracker;
use MediaWiki\WikiMap\WikiMap;
use MessageLocalizer;
use MobileContext;
use MWCryptRand;
use RecentChange;
use RequestContext;
use WikimediaEvents\WikimediaEventsHooks;

/**
 * @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
 */
class Hooks implements
	EditPage__showEditForm_initialHook,
	EditPage__showEditForm_fieldsHook,
	GetPreferencesHook,
	EditPage__attemptSaveHook,
	EditPage__attemptSave_afterHook,
	EditPageGetPreviewContentHook,
	ListDefinedTagsHook,
	ChangeTagsListActiveHook,
	RecentChange_saveHook
{

	/** @var string|bool ID used for grouping entries all of a session's entries together in EventLogging. */
	private static $statsId = false;

	/** @var string[] */
	private static $tags = [ 'wikieditor' ];

	/** @var Config */
	private $config;

	/** @var UserEditTracker */
	private $userEditTracker;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/** @var MobileContext|null */
	private ?MobileContext $mobileContext;

	/**
	 * @param Config $config
	 * @param UserEditTracker $userEditTracker
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param MobileContext|null $mobileContext
	 */
	public function __construct(
		Config $config,
		UserEditTracker $userEditTracker,
		UserOptionsLookup $userOptionsLookup,
		?MobileContext $mobileContext
	) {
		$this->config = $config;
		$this->userEditTracker = $userEditTracker;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->mobileContext = $mobileContext;
	}

	/**
	 * Should the current session be sampled for EventLogging?
	 *
	 * @param string $sessionId
	 * @return bool Whether to sample the session
	 */
	protected function inEventSample( $sessionId ) {
		// Sample 6.25%
		$samplingRate = $this->config->has( 'WMESchemaEditAttemptStepSamplingRate' ) ?
			$this->config->get( 'WMESchemaEditAttemptStepSamplingRate' ) : 0.0625;

		// (T314896) Convert whatever we've been given to a string of hex, as that's what EL needs
		$hexValue = hash( 'md5', $sessionId, false );

		$inSample = EventLogging::sessionInSample(
			(int)( 1 / $samplingRate ), $hexValue
		);
		return $inSample;
	}

	/**
	 * Log stuff to the eventlogging_EditAttemptStep stream in a shape that conforms to the
	 * analytics/legacy/editattemptstep schema.
	 *
	 * If the EventLogging extension is not loaded, then this is a NOP.
	 *
	 * @see https://meta.wikimedia.org/wiki/Schema:EditAttemptStep
	 *
	 * @param string $action
	 * @param Article $article Which article (with full context, page, title, etc.)
	 * @param array $data Data to log for this action
	 * @return void
	 */
	public function doEventLogging( $action, $article, $data = [] ) {
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( !$extensionRegistry->isLoaded( 'EventLogging' ) || !$extensionRegistry->isLoaded( 'WikimediaEvents' ) ) {
			return;
		}
		if ( $extensionRegistry->isLoaded( 'MobileFrontend' ) && $this->mobileContext ) {
			if ( $this->mobileContext->shouldDisplayMobileView() ) {
				// on a MobileFrontend page the logging should be handled by it
				return;
			}
		}
		$inSample = $this->inEventSample( $data['editing_session_id'] );
		$shouldOversample = WikimediaEventsHooks::shouldSchemaEditAttemptStepOversample( $article->getContext() );

		$user = $article->getContext()->getUser();
		$page = $article->getPage();
		$title = $article->getTitle();
		$revisionRecord = $page->getRevisionRecord();
		$skin = $article->getContext()->getSkin();

		$data = [
			'action' => $action,
			'version' => 1,
			'is_oversample' => !$inSample,
			'editor_interface' => 'wikitext',
			// @todo FIXME for other than 'desktop'. T249944
			'platform' => 'desktop',
			'integration' => 'page',
			'page_id' => $page->getId(),
			'page_title' => $title->getPrefixedText(),
			'page_ns' => $title->getNamespace(),
			'revision_id' => $revisionRecord ? $revisionRecord->getId() : 0,
			'user_id' => $user->getId(),
			'user_is_temp' => $user->isTemp(),
			'user_editcount' => $this->userEditTracker->getUserEditCount( $user ) ?: 0,
			'mw_version' => MW_VERSION,
			'skin' => $skin ? $skin->getSkinName() : null,
			'is_bot' => $user->isRegistered() && $user->isBot(),
			'is_anon' => $user->isAnon(),
			'wiki' => WikiMap::getCurrentWikiId(),
		] + $data;

		$bucket = ExtensionRegistry::getInstance()->isLoaded( 'DiscussionTools' ) ?
			// @phan-suppress-next-line PhanUndeclaredClassMethod
			DiscussionToolsHooks\HookUtils::determineUserABTestBucket( $user ) : false;
		if ( $bucket ) {
			$data['bucket'] = $bucket;
		}

		if ( $user->isAnon() ) {
			$data['user_class'] = 'IP';
		}

		if ( !$inSample && !$shouldOversample ) {
			return;
		}

		EventLogging::submit(
			'eventlogging_EditAttemptStep',
			[
				'$schema' => '/analytics/legacy/editattemptstep/2.0.2',
				'event' => $data,
			]
		);
	}

	/**
	 * Log stuff to EventLogging's Schema:VisualEditorFeatureUse -
	 * see https://meta.wikimedia.org/wiki/Schema:VisualEditorFeatureUse
	 * If you don't have EventLogging and WikimediaEvents installed, does nothing.
	 *
	 * @param string $feature
	 * @param string $action
	 * @param Article $article Which article (with full context, page, title, etc.)
	 * @param string $sessionId Session identifier
	 * @return bool Whether the event was logged or not.
	 */
	public function doVisualEditorFeatureUseLogging( $feature, $action, $article, $sessionId ) {
		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( !$extensionRegistry->isLoaded( 'EventLogging' ) || !$extensionRegistry->isLoaded( 'WikimediaEvents' ) ) {
			return false;
		}
		$inSample = $this->inEventSample( $sessionId );
		$shouldOversample = WikimediaEventsHooks::shouldSchemaEditAttemptStepOversample( $article->getContext() );
		if ( !$inSample && !$shouldOversample ) {
			return false;
		}

		$user = $article->getContext()->getUser();
		$editCount = $this->userEditTracker->getUserEditCount( $user );
		$data = [
			'feature' => $feature,
			'action' => $action,
			'editingSessionId' => $sessionId,
			// @todo FIXME for other than 'desktop'. T249944
			'platform' => 'desktop',
			'integration' => 'page',
			'editor_interface' => 'wikitext',
			'user_id' => $user->getId(),
			'user_is_temp' => $user->isTemp(),
			'user_editcount' => $editCount ?: 0,
		];

		$bucket = ExtensionRegistry::getInstance()->isLoaded( 'DiscussionTools' ) ?
			// @phan-suppress-next-line PhanUndeclaredClassMethod
			DiscussionToolsHooks\HookUtils::determineUserABTestBucket( $user ) : false;
		if ( $bucket ) {
			$data['bucket'] = $bucket;
		}

		// NOTE: The 'VisualEditorFeatureUse' event was migrated to the Event Platform and is no
		//  longer using the legacy EventLogging schema from metawiki. $revId is actually
		//  overridden by the EventLoggingSchemas extension attribute in
		//  WikimediaEvents/extension.json.
		return EventLogging::logEvent( 'VisualEditorFeatureUse', -1, $data );
	}

	/**
	 * EditPage::showEditForm:initial hook
	 *
	 * Adds the modules to the edit form
	 *
	 * @param EditPage $editPage the current EditPage object.
	 * @param OutputPage $outputPage object.
	 */
	public function onEditPage__showEditForm_initial( $editPage, $outputPage ) {
		if ( $editPage->contentModel !== CONTENT_MODEL_WIKITEXT ) {
			return;
		}

		$article = $editPage->getArticle();
		$request = $article->getContext()->getRequest();

		// Add modules if enabled
		$user = $article->getContext()->getUser();
		if ( $this->userOptionsLookup->getBoolOption( $user, 'usebetatoolbar' ) ) {
			$outputPage->addModuleStyles( 'ext.wikiEditor.styles' );
			$outputPage->addModules( 'ext.wikiEditor' );
			if ( $this->config->get( 'WikiEditorRealtimePreview' ) ) {
				$outputPage->addModules( 'ext.wikiEditor.realtimepreview' );
			}
		}

		// Don't run this if the request was posted - we don't want to log 'init' when the
		// user just pressed 'Show preview' or 'Show changes', or switched from VE keeping
		// changes.
		if ( ExtensionRegistry::getInstance()->isLoaded( 'EventLogging' ) && !$request->wasPosted() ) {
			$data = [];
			$data['editing_session_id'] = self::getEditingStatsId( $request );
			$section = $request->getRawVal( 'section' );
			if ( $section !== null ) {
				$data['init_type'] = 'section';
			} else {
				$data['init_type'] = 'page';
			}
			if ( $request->getHeader( 'Referer' ) ) {
				if (
					$section === 'new'
					|| !$article->getPage()->exists()
				) {
					$data['init_mechanism'] = 'new';
				} else {
					$data['init_mechanism'] = 'click';
				}
			} else {
				if (
					$section === 'new'
					|| !$article->getPage()->exists()
				) {
					$data['init_mechanism'] = 'url-new';
				} else {
					$data['init_mechanism'] = 'url';
				}
			}
			if ( $request->getRawVal( 'wvprov' ) === 'sticky-header' ) {
				$data['init_mechanism'] .= '-sticky-header';
			}

			$this->doEventLogging( 'init', $article, $data );
		}
	}

	/**
	 * EditPage::showEditForm:fields hook
	 *
	 * Adds the event fields to the edit form
	 *
	 * @param EditPage $editPage the current EditPage object.
	 * @param OutputPage $outputPage object.
	 */
	public function onEditPage__showEditForm_fields( $editPage, $outputPage ) {
		$outputPage->addHTML(
			Html::hidden(
				'wikieditorUsed',
				'',
				[ 'id' => 'wikieditorUsed' ]
			)
		);

		if ( $editPage->contentModel !== CONTENT_MODEL_WIKITEXT
			|| !ExtensionRegistry::getInstance()->isLoaded( 'EventLogging' ) ) {
			return;
		}

		$req = $outputPage->getRequest();
		$editingStatsId = self::getEditingStatsId( $req );

		$shouldOversample = ExtensionRegistry::getInstance()->isLoaded( 'WikimediaEvents' ) &&
			WikimediaEventsHooks::shouldSchemaEditAttemptStepOversample( $outputPage->getContext() );

		$outputPage->addHTML(
			Html::hidden(
				'editingStatsId',
				$editingStatsId,
				[ 'id' => 'editingStatsId' ]
			)
		);

		if ( $shouldOversample ) {
			$outputPage->addHTML(
				Html::hidden(
					'editingStatsOversample',
					1,
					[ 'id' => 'editingStatsOversample' ]
				)
			);
		}
	}

	/**
	 * GetPreferences hook
	 *
	 * Adds WikiEditor-related items to the preferences
	 *
	 * @param User $user current user
	 * @param array &$defaultPreferences list of default user preference controls
	 */
	public function onGetPreferences( $user, &$defaultPreferences ) {
		// Ideally this key would be 'wikieditor-toolbar'
		$defaultPreferences['usebetatoolbar'] = [
			'type' => 'toggle',
			'label-message' => 'wikieditor-toolbar-preference',
			'help-message' => 'wikieditor-toolbar-preference-help',
			'section' => 'editing/editor',
		];
		$defaultPreferences['wikieditor-realtimepreview'] = [
			'type' => 'api',
		];
	}

	/**
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function getModuleData( RL\Context $context, Config $config ) {
		return [
			// expose magic words for use by the wikieditor toolbar
			'magicWords' => self::getMagicWords(),
			'signature' => self::getSignatureMessage( $context ),
			'realtimeDebounce' => $config->get( 'WikiEditorRealtimePreviewDebounce' ),
			'realtimeDisableDuration' => $config->get( 'WikiEditorRealtimeDisableDuration' ),
		];
	}

	/**
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function getModuleDataSummary( RL\Context $context, Config $config ) {
		return [
			'magicWords' => self::getMagicWords(),
			'signature' => self::getSignatureMessage( $context, true ),
			'realtimeDebounce' => $config->get( 'WikiEditorRealtimePreviewDebounce' ),
			'realtimeDisableDuration' => $config->get( 'WikiEditorRealtimeDisableDuration' ),
		];
	}

	/**
	 * @param MessageLocalizer $ml
	 * @param bool $raw
	 * @return string
	 */
	private static function getSignatureMessage( MessageLocalizer $ml, $raw = false ) {
		$msg = $ml->msg( 'sig-text' )->params( '~~~~' )->inContentLanguage();
		return $raw ? $msg->plain() : $msg->text();
	}

	/**
	 * Expose useful magic words which are used by the wikieditor toolbar
	 * @return string[][]
	 */
	private static function getMagicWords() {
		$requiredMagicWords = [
			'redirect',
			'img_alt',
			'img_right',
			'img_left',
			'img_none',
			'img_center',
			'img_thumbnail',
			'img_framed',
			'img_frameless',
		];
		$magicWords = [];
		$factory = MediaWikiServices::getInstance()->getMagicWordFactory();
		foreach ( $requiredMagicWords as $name ) {
			$magicWords[$name] = $factory->get( $name )->getSynonyms();
		}
		return $magicWords;
	}

	/**
	 * Gets a 32 character alphanumeric random string to be used for stats.
	 * @param WebRequest $request
	 * @return string
	 */
	private static function getEditingStatsId( WebRequest $request ) {
		$fromRequest = $request->getRawVal( 'editingStatsId' );
		if ( $fromRequest !== null ) {
			return $fromRequest;
		}
		if ( !self::$statsId ) {
			self::$statsId = MWCryptRand::generateHex( 32 );
		}
		return self::$statsId;
	}

	/**
	 * This is attached to the MediaWiki 'EditPage::attemptSave' hook.
	 *
	 * @param EditPage $editPage
	 */
	public function onEditPage__attemptSave( $editPage ) {
		$article = $editPage->getArticle();
		$request = $article->getContext()->getRequest();
		$statsId = $request->getRawVal( 'editingStatsId' );
		if ( $statsId !== null ) {
			$this->doEventLogging(
				'saveAttempt',
				$article,
				[ 'editing_session_id' => $statsId ]
			);
		}
	}

	/**
	 * This is attached to the MediaWiki 'EditPage::attemptSave:after' hook.
	 *
	 * @param EditPage $editPage
	 * @param Status $status
	 * @param array $resultDetails
	 */
	public function onEditPage__attemptSave_after( $editPage, $status, $resultDetails ) {
		$article = $editPage->getArticle();
		$request = $article->getContext()->getRequest();
		$statsId = $request->getRawVal( 'editingStatsId' );
		if ( $statsId !== null ) {
			$data = [];
			$data['editing_session_id'] = $statsId;

			if ( $status->isOK() ) {
				$action = 'saveSuccess';

				if ( $request->getRawVal( 'wikieditorUsed' ) === 'yes' ) {
					$this->doVisualEditorFeatureUseLogging(
						'mwSave', 'source-has-js', $article, $statsId
					);
				}
			} else {
				$action = 'saveFailure';

				// Compare to ve.init.mw.ArticleTargetEvents.js in VisualEditor.
				$typeMap = [
					'badtoken' => 'userBadToken',
					'assertanonfailed' => 'userNewUser',
					'assertuserfailed' => 'userNewUser',
					'assertnameduserfailed' => 'userNewUser',
					'abusefilter-disallowed' => 'extensionAbuseFilter',
					'abusefilter-warning' => 'extensionAbuseFilter',
					'captcha' => 'extensionCaptcha',
					'spamblacklist' => 'extensionSpamBlacklist',
					'titleblacklist-forbidden' => 'extensionTitleBlacklist',
					'pagedeleted' => 'editPageDeleted',
					'editconflict' => 'editConflict'
				];

				$errors = $status->getErrorsArray();
				// Replicate how the API generates error codes, in order to log data that is consistent with
				// all other tools (which save changes via the API)
				if ( isset( $errors[0] ) ) {
					$code = ApiMessage::create( $errors[0] )->getApiCode();
				} else {
					$code = 'unknown';
				}

				$wikiPage = $editPage->getArticle()->getPage();

				if ( ExtensionRegistry::getInstance()->isLoaded( 'ConfirmEdit' ) ) {
					$key = CacheKeyHelper::getKeyForPage( $wikiPage );
					/** @var SimpleCaptcha $captcha */
					$captcha = ConfirmEditHooks::getInstance();
					$activatedCaptchas = $captcha->getActivatedCaptchas();
					if ( isset( $activatedCaptchas[$key] ) ) {
						// TODO: :(
						$code = 'captcha';
					}
				}

				$data['save_failure_message'] = $code;
				$data['save_failure_type'] = $typeMap[ $code ] ?? 'responseUnknown';
			}

			$this->doEventLogging( $action, $article, $data );
		}
	}

	/**
	 * Log a 'preview-nonlive' action when a page is previewed via the non-ajax full-page preview.
	 *
	 * @param EditPage $editPage
	 * @param Content &$content Content object to be previewed (may be replaced by hook function)
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEditPageGetPreviewContent( $editPage, &$content ) {
		// This hook is only called for non-live previews, so we don't need to check the uselivepreview user option.
		$editingStatsId = $editPage->getContext()->getRequest()->getRawVal( 'editingStatsId' );
		if ( $editingStatsId !== null ) {
			$article = $editPage->getArticle();
			$this->doVisualEditorFeatureUseLogging( 'preview', 'preview-nonlive', $article, $editingStatsId );
		}
	}

	/**
	 * @param string[] &$tags
	 * @return bool|void
	 */
	public function onChangeTagsListActive( &$tags ) {
		$this->registerTags( $tags );
	}

	/**
	 * @param string[] &$tags
	 * @return bool|void
	 */
	public function onListDefinedTags( &$tags ) {
		$this->registerTags( $tags );
	}

	/**
	 * @param string[] &$tags
	 */
	protected function registerTags( &$tags ) {
		$tags = array_merge( $tags, static::$tags );
	}

	/**
	 * @param RecentChange $recentChange
	 * @return bool|void
	 */
	public function onRecentChange_save( $recentChange ) {
		$request = RequestContext::getMain()->getRequest();
		if ( $request->getRawVal( 'wikieditorUsed' ) === 'yes' ) {
			$recentChange->addTags( 'wikieditor' );
		}
		return true;
	}
}
