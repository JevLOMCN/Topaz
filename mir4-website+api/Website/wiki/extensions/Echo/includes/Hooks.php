<?php

namespace MediaWiki\Extension\Notifications;

use ApiModuleManager;
use Content;
use EchoAttributeManager;
use EchoUserLocator;
use EmailNotification;
use ExtensionRegistry;
use HTMLCheckMatrix;
use IBufferingStatsdDataFactory;
use Language;
use LogEntry;
use LogicException;
use MailAddress;
use MediaWiki\Api\Hook\ApiMain__moduleManagerHook;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Config\Config;
use MediaWiki\DAO\WikiAwareEntity;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Extension\Notifications\Controller\ModerationController;
use MediaWiki\Extension\Notifications\Controller\NotificationController;
use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\Extension\Notifications\Hooks\HookRunner;
use MediaWiki\Extension\Notifications\Mapper\EventMapper;
use MediaWiki\Extension\Notifications\Mapper\NotificationMapper;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\Model\Notification;
use MediaWiki\Extension\Notifications\Push\Api\ApiEchoPushSubscriptions;
use MediaWiki\Hook\AbortTalkPageEmailNotificationHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\EmailUserCompleteHook;
use MediaWiki\Hook\GetNewMessagesAlertHook;
use MediaWiki\Hook\LinksUpdateCompleteHook;
use MediaWiki\Hook\LoginFormValidErrorMessagesHook;
use MediaWiki\Hook\OutputPageCheckLastModifiedHook;
use MediaWiki\Hook\PreferencesGetIconHook;
use MediaWiki\Hook\RecentChange_saveHook;
use MediaWiki\Hook\SendWatchlistEmailNotificationHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Hook\SpecialMuteModifyFormFieldsHook;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use MediaWiki\Page\Hook\ArticleUndeleteHook;
use MediaWiki\Page\Hook\RollbackCompleteHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Preferences\MultiTitleFilter;
use MediaWiki\Preferences\MultiUsernameFilter;
use MediaWiki\Request\WebRequest;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\Title;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\Hook\UserClearNewTalkNotificationHook;
use MediaWiki\User\Hook\UserGetDefaultOptionsHook;
use MediaWiki\User\Hook\UserGroupsChangedHook;
use MediaWiki\User\Hook\UserSaveSettingsHook;
use MediaWiki\User\Options\Hook\LoadUserOptionsHook;
use MediaWiki\User\Options\Hook\SaveUserOptionsHook;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\TalkPageNotificationManager;
use MediaWiki\User\User;
use MediaWiki\User\UserEditTracker;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use RecentChange;
use Skin;
use SkinTemplate;
use WikiPage;

class Hooks implements
	AbortTalkPageEmailNotificationHook,
	ApiMain__moduleManagerHook,
	ArticleDeleteCompleteHook,
	ArticleUndeleteHook,
	BeforePageDisplayHook,
	EmailUserCompleteHook,
	GetNewMessagesAlertHook,
	GetPreferencesHook,
	LinksUpdateCompleteHook,
	LoadUserOptionsHook,
	LocalUserCreatedHook,
	LoginFormValidErrorMessagesHook,
	OutputPageCheckLastModifiedHook,
	PageSaveCompleteHook,
	PreferencesGetIconHook,
	RecentChange_saveHook,
	ResourceLoaderRegisterModulesHook,
	RollbackCompleteHook,
	SaveUserOptionsHook,
	SendWatchlistEmailNotificationHook,
	SkinTemplateNavigation__UniversalHook,
	UserClearNewTalkNotificationHook,
	UserGetDefaultOptionsHook,
	UserGroupsChangedHook,
	UserSaveSettingsHook,
	SpecialMuteModifyFormFieldsHook
{
	private AuthManager $authManager;
	private CentralIdLookup $centralIdLookup;
	private Config $config;
	private EchoAttributeManager $attributeManager;
	private HookContainer $hookContainer;
	private Language $contentLanguage;
	private LinkRenderer $linkRenderer;
	private NamespaceInfo $namespaceInfo;
	private PermissionManager $permissionManager;
	private RevisionStore $revisionStore;
	private IBufferingStatsdDataFactory $statsdDataFactory;
	private TalkPageNotificationManager $talkPageNotificationManager;
	private UserEditTracker $userEditTracker;
	private UserFactory $userFactory;
	private UserOptionsManager $userOptionsManager;

	private static array $revertedRevIds = [];

	public function __construct(
		AuthManager $authManager,
		CentralIdLookup $centralIdLookup,
		Config $config,
		EchoAttributeManager $attributeManager,
		HookContainer $hookContainer,
		Language $contentLanguage,
		LinkRenderer $linkRenderer,
		NamespaceInfo $namespaceInfo,
		PermissionManager $permissionManager,
		RevisionStore $revisionStore,
		IBufferingStatsdDataFactory $statsdDataFactory,
		TalkPageNotificationManager $talkPageNotificationManager,
		UserEditTracker $userEditTracker,
		UserFactory $userFactory,
		UserOptionsManager $userOptionsManager
	) {
		$this->authManager = $authManager;
		$this->centralIdLookup = $centralIdLookup;
		$this->config = $config;
		$this->attributeManager = $attributeManager;
		$this->hookContainer = $hookContainer;
		$this->contentLanguage = $contentLanguage;
		$this->linkRenderer = $linkRenderer;
		$this->namespaceInfo = $namespaceInfo;
		$this->permissionManager = $permissionManager;
		$this->revisionStore = $revisionStore;
		$this->statsdDataFactory = $statsdDataFactory;
		$this->talkPageNotificationManager = $talkPageNotificationManager;
		$this->userEditTracker = $userEditTracker;
		$this->userFactory = $userFactory;
		$this->userOptionsManager = $userOptionsManager;
	}

	/**
	 * @param array &$defaults
	 */
	public function onUserGetDefaultOptions( &$defaults ) {
		if ( $this->config->get( MainConfigNames::AllowHTMLEmail ) ) {
			$defaults['echo-email-format'] = 'html';
		} else {
			$defaults['echo-email-format'] = 'plain-text';
		}

		$presets = [
			// Set all of the events to notify by web but not email by default
			// (won't affect events that don't email)
			'default' => [
				'email' => false,
				'web' => true,
			],
			// most settings default to web on, email off, but override these
			'system' => [
				'email' => true,
			],
			'user-rights' => [
				'email' => true,
			],
			'article-linked' => [
				'web' => false,
			],
			'mention-failure' => [
				'web' => false,
			],
			'mention-success' => [
				'web' => false,
			],
			'watchlist' => [
				'web' => false,
			],
			'minor-watchlist' => [
				'web' => false,
			],
		];

		$echoPushEnabled = $this->config->get( ConfigNames::EnablePush );
		if ( $echoPushEnabled ) {
			$presets['default']['push'] = true;
			$presets['article-linked']['push'] = false;
			$presets['mention-failure']['push'] = false;
			$presets['mention-success']['push'] = false;
			$presets['watchlist']['push'] = false;
			$presets['minor-watchlist']['push'] = false;
		}

		foreach ( $this->config->get( ConfigNames::NotificationCategories ) as $category => $categoryData ) {
			if ( !isset( $defaults["echo-subscriptions-email-{$category}"] ) ) {
				$defaults["echo-subscriptions-email-{$category}"] = $presets[$category]['email']
					?? $presets['default']['email'];
			}
			if ( !isset( $defaults["echo-subscriptions-web-{$category}"] ) ) {
				$defaults["echo-subscriptions-web-{$category}"] = $presets[$category]['web']
					?? $presets['default']['web'];
			}
			if ( $echoPushEnabled && !isset( $defaults["echo-subscriptions-push-{$category}"] ) ) {
				$defaults["echo-subscriptions-push-{$category}"] = $presets[$category]['push']
					// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
					?? $presets['default']['push'];
			}
		}
	}

	/**
	 * Initialize Echo extension with necessary data, this function is invoked
	 * from $wgExtensionFunctions
	 */
	public static function initEchoExtension() {
		global $wgEchoNotifications, $wgEchoNotificationCategories, $wgEchoNotificationIcons,
			$wgEchoMentionStatusNotifications, $wgAllowArticleReminderNotification, $wgAPIModules,
			$wgEchoWatchlistNotifications, $wgEchoSeenTimeCacheType, $wgMainStash, $wgEnableEmail,
			$wgEnableUserEmail;

		// allow extensions to define their own event
		( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) )->onBeforeCreateEchoEvent(
			$wgEchoNotifications, $wgEchoNotificationCategories, $wgEchoNotificationIcons );

		// Only allow mention status notifications when enabled
		if ( !$wgEchoMentionStatusNotifications ) {
			unset( $wgEchoNotificationCategories['mention-failure'] );
			unset( $wgEchoNotificationCategories['mention-success'] );
		}

		// Only allow article reminder notifications when enabled
		if ( !$wgAllowArticleReminderNotification ) {
			unset( $wgEchoNotificationCategories['article-reminder'] );
			unset( $wgAPIModules['echoarticlereminder'] );
		}

		// Only allow watchlist notifications when enabled
		if ( !$wgEchoWatchlistNotifications ) {
			unset( $wgEchoNotificationCategories['watchlist'] );
			unset( $wgEchoNotificationCategories['minor-watchlist'] );
		}

		// Only allow user email notifications when enabled
		if ( !$wgEnableEmail || !$wgEnableUserEmail ) {
			unset( $wgEchoNotificationCategories['emailuser'] );
		}

		// Default $wgEchoSeenTimeCacheType to $wgMainStash
		if ( $wgEchoSeenTimeCacheType === null ) {
			$wgEchoSeenTimeCacheType = $wgMainStash;
		}
	}

	/**
	 * Handler for ResourceLoaderRegisterModules hook
	 * @param ResourceLoader $resourceLoader
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
		$resourceLoader->register( 'ext.echo.emailicons', [
			'class' => ResourceLoaderEchoImageModule::class,
			'icons' => $this->config->get( ConfigNames::NotificationIcons ),
			'selector' => '.mw-echo-icon-{name}',
			'localBasePath' => $this->config->get( MainConfigNames::ExtensionDirectory ),
			'remoteExtPath' => 'Echo/modules'
		] );
		$resourceLoader->register( 'ext.echo.secondaryicons', [
			'class' => ResourceLoaderEchoImageModule::class,
			'icons' => $this->config->get( ConfigNames::SecondaryIcons ),
			'selector' => '.mw-echo-icon-{name}',
			'localBasePath' => $this->config->get( MainConfigNames::ExtensionDirectory ),
			'remoteExtPath' => 'Echo/modules'
		] );
	}

	/**
	 * Handler for GetPreferences hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @param User $user User to get preferences for
	 * @param array &$preferences Preferences array
	 */
	public function onGetPreferences( $user, &$preferences ) {
		// The following messages are generated upstrem:
		// * prefs-echo
		// * prefs-description-echo

		// Show email frequency options
		$freqOptions = [
			'echo-pref-email-frequency-never' => EmailFrequency::NEVER,
			'echo-pref-email-frequency-immediately' => EmailFrequency::IMMEDIATELY,
		];
		// Only show digest options if email batch is enabled
		if ( $this->config->get( ConfigNames::EnableEmailBatch ) ) {
			$freqOptions += [
				'echo-pref-email-frequency-daily' => EmailFrequency::DAILY_DIGEST,
				'echo-pref-email-frequency-weekly' => EmailFrequency::WEEKLY_DIGEST,
			];
		}
		$preferences['echo-email-frequency'] = [
			'type' => 'select',
			'label-message' => 'echo-pref-send-me',
			// The following message is generated upstrem:
			// * prefs-emailsettings
			'section' => 'echo/emailsettings',
			'options-messages' => $freqOptions
		];

		$preferences['echo-dont-email-read-notifications'] = [
			'type' => 'toggle',
			'label-message' => 'echo-pref-dont-email-read-notifications',
			// The following message is generated upstrem:
			// * prefs-emailsettings
			'section' => 'echo/emailsettings',
			'hide-if' => [ 'OR', [ '===', 'echo-email-frequency', '-1' ], [ '===', 'echo-email-frequency', '0' ] ]
		];

		// Display information about the user's currently set email address
		$prefsTitle = SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-echo' );
		$link = $this->linkRenderer->makeLink(
			SpecialPage::getTitleFor( 'ChangeEmail' ),
			wfMessage( $user->getEmail() ? 'prefs-changeemail' : 'prefs-setemail' )->text(),
			[],
			[ 'returnto' => $prefsTitle->getFullText() ]
		);
		$emailAddress = $user->getEmail() && $this->permissionManager->userHasRight( $user, 'viewmyprivateinfo' )
			? htmlspecialchars( $user->getEmail() ) : '';
		if ( $this->permissionManager->userHasRight( $user, 'editmyprivateinfo' ) && $this->isEmailChangeAllowed() ) {
			if ( $emailAddress === '' ) {
				$emailAddress .= $link;
			} else {
				$emailAddress .= wfMessage( 'word-separator' )->escaped()
					. wfMessage( 'parentheses' )->rawParams( $link )->escaped();
			}
		}
		$preferences['echo-emailaddress'] = [
			'type' => 'info',
			'raw' => true,
			'default' => $emailAddress,
			'label-message' => 'echo-pref-send-to',
			// The following message is generated upstrem:
			// * prefs-emailsettings
			'section' => 'echo/emailsettings'
		];

		// Only show this option if html email is allowed, otherwise it is always plain text format
		if ( $this->config->get( MainConfigNames::AllowHTMLEmail ) ) {
			// Email format
			$preferences['echo-email-format'] = [
				'type' => 'select',
				'label-message' => 'echo-pref-email-format',
				// The following message is generated upstrem:
				// * prefs-emailsettings
				'section' => 'echo/emailsettings',
				'options-messages' => [
					'echo-pref-email-format-html' => EmailFormat::HTML,
					'echo-pref-email-format-plain-text' => EmailFormat::PLAIN_TEXT,
				]
			];
		}

		// Sort notification categories by priority
		$categoriesAndPriorities = [];
		foreach ( $this->attributeManager->getInternalCategoryNames() as $category ) {
			// See if the category should be hidden from preferences.
			if ( !$this->attributeManager->isCategoryDisplayedInPreferences( $category ) ) {
				continue;
			}

			// See if user is eligible to receive this notification (per user group restrictions)
			if ( $this->attributeManager->getCategoryEligibility( $user, $category ) ) {
				$categoriesAndPriorities[$category] = $this->attributeManager->getCategoryPriority( $category );
			}
		}
		asort( $categoriesAndPriorities );
		$validSortedCategories = array_keys( $categoriesAndPriorities );

		// Show subscription options.  IMPORTANT: 'echo-subscriptions-email-edit-user-talk',
		// 'echo-subscriptions-email-watchlist', and 'echo-subscriptions-email-minor-watchlist' are
		// virtual options, their values are saved to existing notification options 'enotifusertalkpages',
		// 'enotifwatchlistpages', and 'enotifminoredits', see onLoadUserOptions() and onSaveUserOptions()
		// for more information on how it is handled. Doing it in this way, we can avoid keeping running
		// massive data migration script to keep these two options synced when echo is enabled on
		// new wikis or Echo is disabled and re-enabled for some reason.  We can update the name
		// if Echo is ever merged to core

		// Build the columns (notify types)
		$columns = [];
		foreach ( $this->config->get( ConfigNames::Notifiers ) as $notifierType => $notifierData ) {
			// The following messages are generated here
			// * echo-pref-web
			// * echo-pref-email
			// * echo-pref-push
			$formatMessage = wfMessage( 'echo-pref-' . $notifierType )->escaped();
			$columns[$formatMessage] = $notifierType;
		}

		// Build the rows (notification categories)
		$rows = [];
		$tooltips = [];
		$notificationCategories = $this->config->get( ConfigNames::NotificationCategories );
		foreach ( $validSortedCategories as $category ) {
			$categoryMessage = wfMessage( 'echo-category-title-' . $category )->numParams( 1 )->escaped();
			$rows[$categoryMessage] = $category;
			if ( isset( $notificationCategories[$category]['tooltip'] ) ) {
				$tooltips[$categoryMessage] = wfMessage( $notificationCategories[$category]['tooltip'] )->text();
			}
		}

		// Figure out the individual exceptions in the matrix and make them disabled
		$forceOptionsOff = $forceOptionsOn = [];
		foreach ( $this->config->get( ConfigNames::Notifiers ) as $notifierType => $notifierData ) {
			foreach ( $validSortedCategories as $category ) {
				// See if this notify type is non-dismissable
				if ( !$this->attributeManager->isNotifyTypeDismissableForCategory( $category, $notifierType ) ) {
					$forceOptionsOn[] = "$notifierType-$category";
				}

				if ( !$this->attributeManager->isNotifyTypeAvailableForCategory( $category, $notifierType ) ) {
					$forceOptionsOff[] = "$notifierType-$category";
				}
			}
		}

		$invalid = array_intersect( $forceOptionsOff, $forceOptionsOn );
		if ( $invalid ) {
			throw new LogicException( sprintf(
				'The following notifications are both forced and removed: %s',
				implode( ', ', $invalid )
			) );
		}
		$preferences['echo-subscriptions'] = [
			'class' => HTMLCheckMatrix::class,
			// The following message is generated upstrem:
			// * prefs-echosubscriptions
			'section' => 'echo/echosubscriptions',
			'rows' => $rows,
			'columns' => $columns,
			'prefix' => 'echo-subscriptions-',
			'force-options-off' => $forceOptionsOff,
			'force-options-on' => $forceOptionsOn,
			'tooltips' => $tooltips,
		];

		if ( $this->config->get( ConfigNames::CrossWikiNotifications ) ) {
			$preferences['echo-cross-wiki-notifications'] = [
				'type' => 'toggle',
				'label-message' => 'echo-pref-cross-wiki-notifications',
				// The following message is generated upstrem:
				// * prefs-echocrosswiki
				'section' => 'echo/echocrosswiki'
			];
		}

		if ( $this->config->get( ConfigNames::PollForUpdates ) ) {
			$preferences['echo-show-poll-updates'] = [
				'type' => 'toggle',
				'label-message' => 'echo-pref-show-poll-updates',
				'help-message' => 'echo-pref-show-poll-updates-help',
				// The following message is generated upstrem:
				// * prefs-echopollupdates
				'section' => 'echo/echopollupdates'
			];
		}

		// If we're using Echo to handle user talk page post or watchlist notifications,
		// hide the old (non-Echo) preferences for them. If Echo is moved to core
		// we'll want to remove the old user options entirely. For now, though,
		// we need to keep it defined in case Echo is ever uninstalled.
		// Otherwise, that preference could be lost entirely. This hiding logic
		// is not abstracted since there are only three preferences in core
		// that are potentially made obsolete by Echo.
		$notifications = $this->config->get( ConfigNames::Notifications );
		if ( isset( $notifications['edit-user-talk'] ) ) {
			$preferences['enotifusertalkpages']['type'] = 'hidden';
			unset( $preferences['enotifusertalkpages']['section'] );
		}
		if ( $this->config->get( ConfigNames::WatchlistNotifications ) &&
			isset( $notifications['watchlist-change'] )
		) {
			$preferences['enotifwatchlistpages']['type'] = 'hidden';
			unset( $preferences['enotifusertalkpages']['section'] );
			$preferences['enotifminoredits']['type'] = 'hidden';
			unset( $preferences['enotifminoredits']['section'] );
		}

		if ( $this->config->get( ConfigNames::PerUserBlacklist ) ) {
			$preferences['echo-notifications-blacklist'] = [
				'type' => 'usersmultiselect',
				'label-message' => 'echo-pref-notifications-blacklist',
				// The following message is generated upstrem:
				// * prefs-blocknotificationslist
				'section' => 'echo/blocknotificationslist',
				'filter' => MultiUsernameFilter::class,
			];
			$preferences['echo-notifications-page-linked-title-muted-list'] = [
				'type' => 'titlesmultiselect',
				'label-message' => 'echo-pref-notifications-page-linked-title-muted-list',
				// The following message is generated upstrem:
				// * prefs-mutedpageslist
				'section' => 'echo/mutedpageslist',
				'showMissing' => false,
				'excludeDynamicNamespaces' => true,
				'filter' => new MultiTitleFilter()
			];
		}
	}

	/**
	 * Add icon for Special:Preferences mobile layout
	 *
	 * @param array &$iconNames Array of icon names for their respective sections.
	 */
	public function onPreferencesGetIcon( &$iconNames ) {
		$iconNames[ 'echo' ] = 'bell';
	}

	/**
	 * Test whether email address change is supposed to be allowed
	 * @return bool
	 */
	private function isEmailChangeAllowed() {
		return $this->authManager->allowsPropertyChange( 'emailaddress' );
	}

	/**
	 * Handler for PageSaveComplete hook
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageSaveComplete
	 *
	 * @param WikiPage $wikiPage modified WikiPage
	 * @param UserIdentity $userIdentity User who edited
	 * @param string $summary Edit summary
	 * @param int $flags Edit flags
	 * @param RevisionRecord $revisionRecord RevisionRecord for the revision that was created
	 * @param EditResult $editResult
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$userIdentity,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	) {
		if ( $editResult->isNullEdit() ) {
			return;
		}

		$title = $wikiPage->getTitle();
		$isRevert = $editResult->getRevertMethod() === EditResult::REVERT_UNDO ||
			$editResult->getRevertMethod() === EditResult::REVERT_ROLLBACK;

		// Save the revert status for the LinksUpdateComplete hook
		if ( $isRevert ) {
			self::$revertedRevIds[$revisionRecord->getId()] = true;
		}

		// Try to do this after the HTTP response
		DeferredUpdates::addCallableUpdate( static function () use ( $revisionRecord, $isRevert ) {
			DiscussionParser::generateEventsForRevision( $revisionRecord, $isRevert );
		} );

		// If the user is not an IP and this is not a null edit,
		// test for them reaching a congratulatory threshold
		$thresholds = [ 1, 10, 100, 1000, 10000, 100000, 1000000, 10000000 ];
		if ( $userIdentity->isRegistered() ) {
			$thresholdCount = $this->getEditCount( $userIdentity );
			if ( in_array( $thresholdCount, $thresholds ) ) {
				DeferredUpdates::addCallableUpdate( static function () use (
					$revisionRecord, $userIdentity, $title, $thresholdCount
				) {
					$notificationMapper = new NotificationMapper();
					$notifications = $notificationMapper->fetchByUser( $userIdentity, 10, null, [ 'thank-you-edit' ] );
					/** @var Notification $notification */
					foreach ( $notifications as $notification ) {
						if ( $notification->getEvent()->getExtraParam( 'editCount' ) === $thresholdCount ) {
							LoggerFactory::getInstance( 'Echo' )->debug(
								'{user} (id: {id}) has already been thanked for their {count} edit',
								[
									'user' => $userIdentity->getName(),
									'id' => $userIdentity->getId(),
									'count' => $thresholdCount,
								]
							);
							return;
						}
					}

					Event::create( [
						'type' => 'thank-you-edit',
						'title' => $title,
						'agent' => $userIdentity,
						// Edit threshold notifications are sent to the agent
						'extra' => [
							'editCount' => $thresholdCount,
							'revid' => $revisionRecord->getId(),
						]
					] );
				} );
			}
		}

		// Handle the case of someone undoing an edit, either through the
		// 'undo' link in the article history or via the API.
		// Reverts through the 'rollback' link (EditResult::REVERT_ROLLBACK)
		// are handled in ::onRollbackComplete().
		if ( $editResult->getRevertMethod() === EditResult::REVERT_UNDO ) {
			$undidRevId = $editResult->getUndidRevId();
			$undidRevision = $this->revisionStore->getRevisionById( $undidRevId );
			if (
				$undidRevision &&
				Title::newFromLinkTarget( $undidRevision->getPageAsLinkTarget() )->equals( $title )
			) {
				$revertedUser = $undidRevision->getUser();
				// No notifications for anonymous users
				if ( $revertedUser && $revertedUser->getId() ) {
					Event::create( [
						'type' => 'reverted',
						'title' => $title,
						'extra' => [
							'revid' => $revisionRecord->getId(),
							'reverted-user-id' => $revertedUser->getId(),
							'reverted-revision-id' => $undidRevId,
							'method' => 'undo',
							'summary' => $summary,
						],
						'agent' => $userIdentity,
					] );
				}
			}
		}
	}

	/**
	 * @param UserIdentity $user
	 * @return int
	 */
	private function getEditCount( UserIdentity $user ) {
		$editCount = $this->userEditTracker->getUserEditCount( $user ) ?: 0;
		// When this code runs from a maintenance script or unit tests
		// the deferred update incrementing edit count runs right away
		// so the edit count is right. Otherwise it lags by one.
		if ( wfIsCLI() ) {
			return $editCount;
		}
		return $editCount + 1;
	}

	/**
	 * Handler for LocalUserCreated hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 * @param User $user User object that was created.
	 * @param bool $autocreated True when account was auto-created
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		if ( !$autocreated ) {
			Event::create( [
				'type' => 'welcome',
				'agent' => $user,
			] );
		}

		$seenTime = SeenTime::newFromUser( $user );

		// Set seen time to UNIX epoch, so initially all notifications are unseen.
		$seenTime->setTime( wfTimestamp( TS_MW, 1 ), 'all' );
	}

	/**
	 * Handler for UserGroupsChanged hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 *
	 * @param UserIdentity $userId user that was changed
	 * @param string[] $add strings corresponding to groups added
	 * @param string[] $remove strings corresponding to groups removed
	 * @param User|bool $performer
	 * @param string|bool $reason Reason given by the user changing the rights
	 * @param array $oldUGMs
	 * @param array $newUGMs
	 */
	public function onUserGroupsChanged( $userId, $add, $remove, $performer, $reason, $oldUGMs, $newUGMs ) {
		if ( !$performer ) {
			// TODO: Implement support for autopromotion
			return;
		}

		if ( $userId->getWikiId() !== WikiAwareEntity::LOCAL ) {
			// TODO: Support external users
			return;
		}

		$user = $this->userFactory->newFromUserIdentity( $userId );

		if ( $user->equals( $performer ) ) {
			// Don't notify for self changes
			return;
		}

		// If any old groups are in $add, those groups are having their expiry
		// changed, not actually being added
		$expiryChanged = [];
		$reallyAdded = [];
		foreach ( $add as $group ) {
			if ( isset( $oldUGMs[$group] ) ) {
				$expiryChanged[] = $group;
			} else {
				$reallyAdded[] = $group;
			}
		}

		if ( $expiryChanged ) {
			// use a separate notification for these, so the notification text doesn't
			// get too long
			Event::create(
				[
					'type' => 'user-rights',
					'extra' => [
						'user' => $user->getId(),
						'expiry-changed' => $expiryChanged,
						'reason' => $reason,
					],
					'agent' => $performer,
				]
			);
		}

		if ( $reallyAdded || $remove ) {
			Event::create(
				[
					'type' => 'user-rights',
					'extra' => [
						'user' => $user->getId(),
						'add' => $reallyAdded,
						'remove' => $remove,
						'reason' => $reason,
					],
					'agent' => $performer,
				]
			);
		}
	}

	/**
	 * Handler for LinksUpdateComplete hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
	 * @param LinksUpdate $linksUpdate
	 * @param mixed $ticket
	 */
	public function onLinksUpdateComplete( $linksUpdate, $ticket ) {
		// Rollback or undo should not trigger link notification
		if ( $linksUpdate->getRevisionRecord() ) {
			$revId = $linksUpdate->getRevisionRecord()->getId();
			if ( isset( self::$revertedRevIds[$revId] ) ) {
				return;
			}
		}

		// Handle only
		// 1. content namespace pages &&
		// 2. non-transcluding pages &&
		// 3. non-redirect pages
		if ( !$this->namespaceInfo->isContent( $linksUpdate->getTitle()->getNamespace() )
			|| !$linksUpdate->isRecursive() || $linksUpdate->getTitle()->isRedirect()
		) {
			return;
		}

		$revRecord = $linksUpdate->getRevisionRecord();
		$revid = $revRecord ? $revRecord->getId() : null;
		$user = $revRecord ? $revRecord->getUser() : null;

		// link notification is boundless as you can include infinite number of links in a page
		// db insert is expensive, limit it to a reasonable amount, we can increase this limit
		// once the storage is on Redis
		$max = 10;
		// Only create notifications for links to content namespace pages
		// @Todo - use one big insert instead of individual insert inside foreach loop
		foreach ( $linksUpdate->getAddedLinks() as $title ) {
			if ( $this->namespaceInfo->isContent( $title->getNamespace() ) ) {
				if ( $title->isRedirect() ) {
					continue;
				}

				$linkFromPageId = $linksUpdate->getTitle()->getArticleID();
				// T318523: Don't send page-linked notifications for pages created by bot users.
				$articleAuthor = EchoUserLocator::getArticleAuthorByArticleId( $title->getArticleID() );
				if ( $articleAuthor && $articleAuthor->isBot() ) {
					continue;
				}
				Event::create( [
					'type' => 'page-linked',
					'title' => $title,
					'agent' => $user,
					'extra' => [
						'target-page' => $linkFromPageId,
						'link-from-page-id' => $linkFromPageId,
						'revid' => $revid,
					]
				] );
				$max--;
			}
			if ( $max < 0 ) {
				break;
			}
		}
	}

	/**
	 * Handler for BeforePageDisplay hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param OutputPage $out
	 * @param Skin $skin Skin being used.
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$user = $out->getUser();

		if ( !$user->isRegistered() ) {
			if ( ExtensionRegistry::getInstance()->isLoaded( 'CentralAuth' ) ) {
				$out->addModules( [ 'ext.echo.centralauth' ] );
			}
			return;
		}

		if ( $this->shouldDisplayTalkAlert( $user, $out->getTitle() ) ) {
			// Load the module for the Orange alert
			$out->addModuleStyles( 'ext.echo.styles.alert' );
		}

		// Load the module for the Notifications flyout
		$out->addModules( [ 'ext.echo.init' ] );
		// Load the styles for the Notifications badge
		$out->addModuleStyles( [
			'ext.echo.styles.badge',
			'oojs-ui.styles.icons-alerts'
		] );
	}

	private function processMarkAsRead( User $user, WebRequest $request, Title $title ) {
		$subtractions = [
			AttributeManager::ALERT => 0,
			AttributeManager::MESSAGE => 0
		];

		// Attempt to mark a notification as read when visiting a page
		$eventIds = [];
		if ( $title->getArticleID() ) {
			$eventMapper = new EventMapper();
			$events = $eventMapper->fetchUnreadByUserAndPage( $user, $title->getArticleID() );

			foreach ( $events as $event ) {
				$subtractions[$event->getSection()]++;
				$eventIds[] = $event->getId();
			}
		}

		// Attempt to mark as read the event IDs in the ?markasread= parameter, if present
		$markAsReadIds = array_filter( explode( '|', $request->getText( 'markasread' ) ) );
		$markAsReadWiki = $request->getText( 'markasreadwiki', WikiMap::getCurrentWikiId() );
		$markAsReadLocal = !$this->config->get( ConfigNames::CrossWikiNotifications ) ||
			$markAsReadWiki === WikiMap::getCurrentWikiId();
		if ( $markAsReadIds ) {
			if ( $markAsReadLocal ) {
				// gather the IDs that we didn't already find with target_pages
				$eventsToMarkAsRead = [];
				foreach ( $markAsReadIds as $markAsReadId ) {
					$markAsReadId = intval( $markAsReadId );
					if ( $markAsReadId !== 0 && !in_array( $markAsReadId, $eventIds ) ) {
						$eventsToMarkAsRead[] = $markAsReadId;
					}
				}

				if ( $eventsToMarkAsRead ) {
					// fetch the notifications to adjust the counters
					$notifMapper = new NotificationMapper();
					$notifs = $notifMapper->fetchByUserEvents( $user, $eventsToMarkAsRead );

					foreach ( $notifs as $notif ) {
						if ( !$notif->getReadTimestamp() ) {
							$subtractions[$notif->getEvent()->getSection()]++;
							$eventIds[] = intval( $notif->getEvent()->getId() );
						}
					}
				}
			} else {
				$markAsReadIds = array_map( 'intval', $markAsReadIds );
				// Look up the notifications on the foreign wiki
				$notifUser = NotifUser::newFromUser( $user );
				$notifInfo = $notifUser->getForeignNotificationInfo( $markAsReadIds, $markAsReadWiki, $request );
				foreach ( $notifInfo as $id => $info ) {
					$subtractions[$info['section']]++;
				}

				// Schedule a deferred update to mark these notifications as read on the foreign wiki
				DeferredUpdates::addCallableUpdate(
					static function () use ( $user, $markAsReadIds, $markAsReadWiki, $request ) {
						$notifUser = NotifUser::newFromUser( $user );
						$notifUser->markReadForeign( $markAsReadIds, $markAsReadWiki, $request );
					}
				);
			}
		}

		// Schedule a deferred update to mark local target_page and ?markasread= notifications as read
		if ( $eventIds ) {
			DeferredUpdates::addCallableUpdate( static function () use ( $user, $eventIds ) {
				$notifUser = NotifUser::newFromUser( $user );
				$notifUser->markRead( $eventIds );
			} );
		}

		return $subtractions;
	}

	/**
	 * Determine if a talk page alert should be displayed.
	 * We need to check:
	 * - User actually has new messages
	 * - User is not viewing their user talk page, as user_newtalk will not have been cleared yet.
	 *   (bug T107655).
	 *
	 * @param User $user
	 * @param Title $title
	 * @return bool
	 */
	private function shouldDisplayTalkAlert( $user, $title ) {
		$userHasNewMessages = $this->talkPageNotificationManager->userHasNewMessages( $user );

		return $userHasNewMessages && !$user->getTalkPage()->equals( $title );
	}

	/**
	 * Handler for SkinTemplateNavigation::Universal hook.
	 * Adds "Notifications" items to the notifications content navigation.
	 * SkinTemplate automatically merges these into the personal tools for older skins.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation::Universal
	 * @param SkinTemplate $skinTemplate
	 * @param array &$links Array of URLs to append to.
	 */
	public function onSkinTemplateNavigation__Universal( $skinTemplate, &$links ): void {
		$user = $skinTemplate->getUser();
		if ( !$user->isRegistered() ) {
			return;
		}

		$title = $skinTemplate->getTitle();
		$out = $skinTemplate->getOutput();

		$subtractions = $this->processMarkAsRead( $user, $out->getRequest(), $title );

		// Add a "My notifications" item to personal URLs
		$notifUser = NotifUser::newFromUser( $user );
		$msgCount = $notifUser->getMessageCount() - $subtractions[AttributeManager::MESSAGE];
		$alertCount = $notifUser->getAlertCount() - $subtractions[AttributeManager::ALERT];
		// But make sure we never show a negative number (T130853)
		$msgCount = max( 0, $msgCount );
		$alertCount = max( 0, $alertCount );

		$msgNotificationTimestamp = $notifUser->getLastUnreadMessageTime();
		$alertNotificationTimestamp = $notifUser->getLastUnreadAlertTime();

		$seenTime = SeenTime::newFromUser( $user );
		if ( $title->isSpecial( 'Notifications' ) ) {
			// If this is the Special:Notifications page, seenTime to now
			$seenTime->setTime( wfTimestamp( TS_MW ), AttributeManager::ALL );
		}
		$seenAlertTime = $seenTime->getTime( 'alert', TS_ISO_8601 );
		$seenMsgTime = $seenTime->getTime( 'message', TS_ISO_8601 );

		$out->addJsConfigVars( 'wgEchoSeenTime', [
			'alert' => $seenAlertTime,
			'notice' => $seenMsgTime,
		] );

		$msgFormattedCount = NotificationController::formatNotificationCount( $msgCount );
		$alertFormattedCount = NotificationController::formatNotificationCount( $alertCount );

		$url = SpecialPage::getTitleFor( 'Notifications' )->getLocalURL();

		$skinName = strtolower( $skinTemplate->getSkinName() );
		$isMinervaSkin = $skinName === 'minerva';
		// HACK: inverted icons only work in the "MediaWiki" OOUI theme
		// Avoid flashes in skins that don't use it (T111821)
		$out::setupOOUI( $skinName, $out->getLanguage()->getDir() );
		$bellIconClass = $isMinervaSkin ? 'oo-ui-icon-bellOutline' : 'oo-ui-icon-bell';

		$msgLinkClasses = [ "mw-echo-notifications-badge", "mw-echo-notification-badge-nojs", "oo-ui-icon-tray" ];
		$alertLinkClasses = [ "mw-echo-notifications-badge", "mw-echo-notification-badge-nojs", $bellIconClass ];

		$hasUnseen = false;
		if (
			// no unread notifications
			$msgCount !== 0 &&
			// should already always be false if count === 0
			$msgNotificationTimestamp !== false &&
			// there are no unseen notifications
			( $seenMsgTime === null ||
				$seenMsgTime < $msgNotificationTimestamp->getTimestamp( TS_ISO_8601 ) )
		) {
			$msgLinkClasses[] = 'mw-echo-unseen-notifications';
			$hasUnseen = true;
		} elseif ( $msgCount === 0 ) {
			$msgLinkClasses[] = 'mw-echo-notifications-badge-all-read';
		}

		if ( $msgCount > NotifUser::MAX_BADGE_COUNT ) {
			$msgLinkClasses[] = 'mw-echo-notifications-badge-long-label';
		}

		if (
			// no unread notifications
			$alertCount !== 0 &&
			// should already always be false if count === 0
			$alertNotificationTimestamp !== false &&
			// all notifications have already been seen
			( $seenAlertTime === null ||
				$seenAlertTime < $alertNotificationTimestamp->getTimestamp( TS_ISO_8601 ) )
		) {
			$alertLinkClasses[] = 'mw-echo-unseen-notifications';
			$hasUnseen = true;
		} elseif ( $alertCount === 0 ) {
			$alertLinkClasses[] = 'mw-echo-notifications-badge-all-read';
		}

		if ( $alertCount > NotifUser::MAX_BADGE_COUNT ) {
			$alertLinkClasses[] = 'mw-echo-notifications-badge-long-label';
		}

		$mytalk = $links['user-menu']['mytalk'] ?? false;
		if (
			$mytalk &&
			$this->shouldDisplayTalkAlert( $user, $title ) &&
			( new HookRunner( $this->hookContainer ) )->onBeforeDisplayOrangeAlert( $user, $title )
		) {
			// Create new talk alert inheriting from the talk link data.
			$links['notifications']['talk-alert'] = array_merge(
				$links['user-menu']['mytalk'],
				[
					// Hardcode id, which is needed to dismiss the talk alert notification
					'id' => 'pt-talk-alert',
					// If Vector hook ran anicon will have  been copied to the link class.
					// We must reset it.
					'link-class' => [],
					'text' => $skinTemplate->msg( 'echo-new-messages' )->text(),
					'class' => [ 'mw-echo-alert' ],
					// unset icon
					'icon' => null,
				]
			);

			// If there's exactly one new user talk message, then link directly to it from the alert.
			$notificationMapper = new NotificationMapper();
			$notifications = $notificationMapper->fetchUnreadByUser( $user, 2, null, [ 'edit-user-talk' ] );
			if ( count( $notifications ) === 1 ) {
				$presModel = EchoEventPresentationModel::factory(
					current( $notifications )->getEvent(),
					$out->getLanguage(),
					$user
				);
				$links['notifications']['talk-alert']['href'] = $presModel->getPrimaryLink()['url'];
			}
		}

		$links['notifications']['notifications-alert'] = [
			'href' => $url,
			'text' => $skinTemplate->msg( 'echo-notification-alert', $alertCount )->text(),
			'active' => ( $url == $title->getLocalURL() ),
			'link-class' => $alertLinkClasses,
			'icon' => 'bell',
			'data' => [
				'event-name' => 'ui.notifications',
				'counter-num' => $alertCount,
				'counter-text' => $alertFormattedCount,
			],
			// This item used to be part of personal tools, and much CSS relies on it using this id.
			'id' => 'pt-notifications-alert',
		];

		$links['notifications']['notifications-notice'] = [
			'href' => $url,
			'text' => $skinTemplate->msg( 'echo-notification-notice', $msgCount )->text(),
			'active' => ( $url == $title->getLocalURL() ),
			'link-class' => $msgLinkClasses,
			'icon' => 'tray',
			'data' => [
				'counter-num' => $msgCount,
				'counter-text' => $msgFormattedCount,
			],
			// This item used to be part of personal tools, and much CSS relies on it using this id.
			'id' => 'pt-notifications-notice',
		];

		if ( $hasUnseen ) {
			// Record that the user is going to see an indicator that they have unseen notifications
			// This is part of tracking how likely users are to click a badge with unseen notifications.
			// The other part is the 'echo.unseen.click' counter, see ext.echo.init.js.
			$this->statsdDataFactory->increment( 'echo.unseen' );
		}
	}

	/**
	 * Handler for AbortTalkPageEmailNotification hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/AbortTalkPageEmailNotification
	 * @param User $targetUser
	 * @param Title $title
	 * @return bool
	 */
	public function onAbortTalkPageEmailNotification( $targetUser, $title ) {
		// Send legacy talk page email notification if
		// 1. echo is disabled for them or
		// 2. echo talk page notification is disabled
		if ( !isset( $this->config->get( ConfigNames::Notifications )['edit-user-talk'] ) ) {
			// Legacy talk page email notification
			return true;
		}

		// Echo talk page email notification
		return false;
	}

	/**
	 * Handler for AbortWatchlistEmailNotification hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/AbortWatchlistEmailNotification
	 * @param User $targetUser
	 * @param Title $title
	 * @param EmailNotification $emailNotification The email notification object that sends non-echo notifications
	 * @return bool
	 */
	public function onSendWatchlistEmailNotification( $targetUser, $title, $emailNotification ) {
		if ( $this->config->get( ConfigNames::WatchlistNotifications ) &&
			isset( $this->config->get( ConfigNames::Notifications )["watchlist-change"] )
		) {
			// Let echo handle watchlist notifications entirely
			return false;
		}
		$eventName = false;
		// The edit-user-talk and edit-user-page events effectively duplicate watchlist notifications.
		// If we are sending Echo notification emails, suppress the watchlist notifications.
		if ( $title->inNamespace( NS_USER_TALK ) && $targetUser->getTalkPage()->equals( $title ) ) {
			$eventName = 'edit-user-talk';
		} elseif ( $title->inNamespace( NS_USER ) && $targetUser->getUserPage()->equals( $title ) ) {
			$eventName = 'edit-user-page';
		}

		if ( $eventName !== false ) {
			$events = $this->attributeManager->getUserEnabledEvents( $targetUser, 'email' );
			if ( in_array( $eventName, $events ) ) {
				// Do not send watchlist email notification, the user will receive an Echo notification
				return false;
			}
		}

		// Proceed to send watchlist email notification
		return true;
	}

	/**
	 * @param array &$modifiedTimes
	 * @param OutputPage $out
	 */
	public function onOutputPageCheckLastModified( &$modifiedTimes, $out ) {
		$req = $out->getRequest();
		if ( $req->getRawVal( 'action' ) === 'raw' || $req->getRawVal( 'action' ) === 'render' ) {
			// Optimisation: Avoid expensive SeenTime compute on non-skin responses (T279213)
			return;
		}

		$user = $out->getUser();
		if ( $user->isRegistered() ) {
			$notifUser = NotifUser::newFromUser( $user );
			$lastUpdate = $notifUser->getGlobalUpdateTime();
			if ( $lastUpdate !== false ) {
				$modifiedTimes['notifications-global'] = $lastUpdate;
			}

			$modifiedTimes['notifications-seen-alert'] = SeenTime::newFromUser( $user )->getTime( 'alert' );
			$modifiedTimes['notifications-seen-message'] = SeenTime::newFromUser( $user )->getTime( 'message' );
		}
	}

	/**
	 * Handler for GetNewMessagesAlert hook.
	 * We're using the GetNewMessagesAlert hook instead of the
	 * ArticleEditUpdateNewTalk hook since we still want the user_newtalk data
	 * to be updated and available to client-side tools and the API.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetNewMessagesAlert
	 * @param string &$newMessagesAlert An alert that the user has new messages
	 *     or an empty string if the user does not (empty by default)
	 * @param array $newtalks This will be empty if the user has no new messages
	 *     or an Array containing links and revisions if there are new messages
	 * @param User $user The user who is loading the page
	 * @param OutputPage $out
	 * @return bool Should return false to prevent the new messages alert (OBOD)
	 *     or true to allow the new messages alert
	 */
	public function onGetNewMessagesAlert( &$newMessagesAlert, $newtalks, $user, $out ) {
		// If the user has the notifications flyout turned on and is receiving
		// notifications for talk page messages, disable the new messages alert.
		if ( $user->isRegistered()
			&& isset( $this->config->get( ConfigNames::Notifications )['edit-user-talk'] )
			&& ( new HookRunner( $this->hookContainer ) )->onEchoCanAbortNewMessagesAlert()
		) {
			// hide new messages alert
			return false;
		} else {
			// show new messages alert
			return true;
		}
	}

	/**
	 * Handler for RollbackComplete hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RollbackComplete
	 *
	 * @param WikiPage $wikiPage The article that was edited
	 * @param UserIdentity $agent The user who did the rollback
	 * @param RevisionRecord $newRevision The revision the page was reverted back to
	 * @param RevisionRecord $oldRevision The revision of the top edit that was reverted
	 */
	public function onRollbackComplete(
		$wikiPage,
		$agent,
		$newRevision,
		$oldRevision
	) {
		$revertedUser = $oldRevision->getUser();
		$latestRevision = $wikiPage->getRevisionRecord();

		if (
			$revertedUser &&
			// No notifications for anonymous users
			$revertedUser->isRegistered() &&
			// No notifications for null rollbacks
			!$oldRevision->hasSameContent( $newRevision )
		) {
			Event::create( [
				'type' => 'reverted',
				'title' => $wikiPage->getTitle(),
				'extra' => [
					'revid' => $latestRevision->getId(),
					'reverted-user-id' => $revertedUser->getId(),
					'reverted-revision-id' => $oldRevision->getId(),
					'method' => 'rollback',
				],
				'agent' => $agent,
			] );
		}
	}

	/**
	 * Handler for UserSaveSettings hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserSaveSettings
	 * @param User $user whose settings were saved
	 */
	public function onUserSaveSettings( $user ) {
		// Extensions like AbuseFilter might create an account, but
		// the tables we need might not exist. Bug 57335
		if ( !defined( 'MW_UPDATER' ) ) {
			// Reset the notification count since it may have changed due to user
			// option changes. This covers both explicit changes in the preferences
			// and changes made through the options API (since both call this hook).
			DeferredUpdates::addCallableUpdate( static function () use ( $user ) {
				if ( !$user->isRegistered() ) {
					// It's possible the user account was deleted before the deferred
					// update runs (T318081)
					return;
				}
				NotifUser::newFromUser( $user )->resetNotificationCount();
			} );
		}
	}

	/**
	 * Some of Echo's subscription user preferences are mapped to existing user preferences defined in
	 * core MediaWiki. This returns the map of Echo preference names to core preference names.
	 *
	 * @return array
	 */
	public static function getVirtualUserOptions() {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$options = [];
		$options['echo-subscriptions-email-edit-user-talk'] = 'enotifusertalkpages';
		if ( $config->get( ConfigNames::WatchlistNotifications ) ) {
			$options['echo-subscriptions-email-watchlist'] = 'enotifwatchlistpages';
			$options['echo-subscriptions-email-minor-watchlist'] = 'enotifminoredits';
		}
		return $options;
	}

	/**
	 * Handler for LoadUserOptions hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadUserOptions
	 * @param UserIdentity $user User whose options were loaded
	 * @param array &$options Options can be modified
	 */
	public function onLoadUserOptions( UserIdentity $user, &$options ): void {
		foreach ( self::getVirtualUserOptions() as $echoPref => $mwPref ) {
			// Use the existing core option's value for the Echo option
			if ( isset( $options[ $mwPref ] ) ) {
				$options[ $echoPref ] = $options[ $mwPref ];
			}
		}
	}

	/**
	 * Handler for SaveUserOptions hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SaveUserOptions
	 * @param UserIdentity $user User whose options are being saved
	 * @param array &$modifiedOptions Options can be modified
	 * @param array $originalOptions
	 */
	public function onSaveUserOptions( UserIdentity $user, array &$modifiedOptions, array $originalOptions ) {
		foreach ( self::getVirtualUserOptions() as $echoPref => $mwPref ) {
			// Save virtual option values in corresponding real option values
			if ( isset( $modifiedOptions[ $echoPref ] ) ) {
				$modifiedOptions[ $mwPref ] = $modifiedOptions[ $echoPref ];
				unset( $modifiedOptions[ $echoPref ] );
			}
		}
	}

	/**
	 * Convert all values in an array to integers and filter out zeroes.
	 *
	 * @param array $numbers
	 *
	 * @return int[]
	 */
	protected static function mapToInt( array $numbers ) {
		$data = [];

		foreach ( $numbers as $value ) {
			$int = intval( $value );
			if ( $int === 0 ) {
				continue;
			}
			$data[] = $int;
		}

		return $data;
	}

	/**
	 * Handler for UserClearNewTalkNotification hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserClearNewTalkNotification
	 * @param UserIdentity $user User whose talk page notification should be marked as read
	 * @param int $oldid
	 */
	public function onUserClearNewTalkNotification( $user, $oldid ) {
		if ( $user->isRegistered() ) {
			DeferredUpdates::addCallableUpdate( static function () use ( $user ) {
				NotifUser::newFromUser( $user )->clearUserTalkNotifications();
			} );
		}
	}

	/**
	 * Handler for EmailUserComplete hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EmailUserComplete
	 * @param MailAddress $address Adress of receiving user
	 * @param MailAddress $from Adress of sending user
	 * @param string $subject Subject of the mail
	 * @param string $text Text of the mail
	 */
	public function onEmailUserComplete( $address, $from, $subject, $text ) {
		if ( $from->name === $address->name ) {
			// nothing to notify
			return;
		}
		$userTo = User::newFromName( $address->name );
		$userFrom = User::newFromName( $from->name );

		$autoSubject = wfMessage( 'defemailsubject', $from->name )->inContentLanguage()->text();
		if ( $subject === $autoSubject ) {
			$autoFooter = "\n\n-- \n" . wfMessage( 'emailuserfooter', $from->name, $address->name )
				->inContentLanguage()->text();
			$textWithoutFooter = preg_replace( '/' . preg_quote( $autoFooter, '/' ) . '$/', '', $text );
			$preview = $this->contentLanguage->truncateForVisual( $textWithoutFooter, 125 );
		} else {
			$preview = $subject;
		}

		Event::create( [
			'type' => 'emailuser',
			'extra' => [
				'to-user-id' => $userTo->getId(),
				'preview' => $preview,
			],
			'agent' => $userFrom,
		] );
	}

	/**
	 * Sets custom login message for redirect from notification page
	 *
	 * @param array &$messages
	 */
	public function onLoginFormValidErrorMessages( array &$messages ) {
		$messages[] = 'echo-notification-loginrequired';
	}

	public static function getConfigVars( RL\Context $context, Config $config ) {
		return [
			'EchoMaxNotificationCount' => NotifUser::MAX_BADGE_COUNT,
			'EchoPollForUpdates' => $config->get( ConfigNames::PollForUpdates )
		];
	}

	/**
	 * @param WikiPage $article
	 * @param User $user
	 * @param string $reason
	 * @param int $articleId
	 * @param Content|null $content
	 * @param LogEntry $logEntry
	 * @param int $archivedRevisionCount
	 */
	public function onArticleDeleteComplete(
		$article,
		$user,
		$reason,
		$articleId,
		$content,
		$logEntry,
		$archivedRevisionCount
	) {
		DeferredUpdates::addCallableUpdate( static function () use ( $articleId ) {
			$eventMapper = new EventMapper();
			$eventIds = $eventMapper->fetchIdsByPage( $articleId );
			ModerationController::moderate( $eventIds, true );
		} );
	}

	/**
	 * @param Title $title
	 * @param bool $create
	 * @param string $comment
	 * @param int $oldPageId
	 * @param array $restoredPages
	 */
	public function onArticleUndelete( $title, $create, $comment, $oldPageId, $restoredPages ) {
		if ( $create ) {
			DeferredUpdates::addCallableUpdate( static function () use ( $oldPageId ) {
				$eventMapper = new EventMapper();
				$eventIds = $eventMapper->fetchIdsByPage( $oldPageId );
				ModerationController::moderate( $eventIds, false );
			} );
		}
	}

	/**
	 * Handler for SpecialMuteModifyFormFields hook
	 *
	 * @param UserIdentity|null $target
	 * @param User $user
	 * @param array &$fields
	 */
	public function onSpecialMuteModifyFormFields( $target, $user, &$fields ) {
		$echoPerUserBlacklist = $this->config->get( ConfigNames::PerUserBlacklist );
		if ( $echoPerUserBlacklist ) {
			$id = $target ? $this->centralIdLookup->centralIdFromLocalUser( $target ) : 0;
			$list = MultiUsernameFilter::splitIds(
				$this->userOptionsManager->getOption( $user, 'echo-notifications-blacklist' )
			);
			$fields[ 'echo-notifications-blacklist'] = [
				'type' => 'check',
				'label-message' => [
					'echo-specialmute-label-mute-notifications',
					$target ? $target->getName() : ''
				],
				'default' => in_array( $id, $list, true ),
			];
		}
	}

	/**
	 * @param RecentChange $change
	 * @return bool|void
	 */
	public function onRecentChange_save( $change ) {
		if ( !$this->config->get( 'EchoWatchlistNotifications' ) ) {
			return;
		}
		if ( $change->getAttribute( 'rc_minor' ) ) {
			$type = 'minor-watchlist-change';
		} else {
			$type = 'watchlist-change';
		}
		Event::create( [
			'type' => $type,
			'title' => $change->getTitle(),
			'extra' => [
				'page_title' => $change->getPage()->getDBkey(),
				'page_namespace' => $change->getPage()->getNamespace(),
				'revid' => $change->getAttribute( "rc_this_oldid" ),
				'logid' => $change->getAttribute( "rc_logid" ),
				'status' => $change->mExtra["pageStatus"],
				'timestamp' => $change->getAttribute( "rc_timestamp" ),
				'emailonce' => $this->config->get( ConfigNames::WatchlistEmailOncePerPage ),
			],
			'agent' => $change->getPerformerIdentity(),
		] );
	}

	/**
	 * Hook handler for ApiMain::moduleManager.
	 * Used here to put the echopushsubscriptions API module behind our push feature flag.
	 * TODO: Register this the usual way in extension.json when we don't need the feature flag
	 *  anymore.
	 * @param ApiModuleManager $moduleManager
	 */
	public function onApiMain__ModuleManager( $moduleManager ) {
		$pushEnabled = $this->config->get( 'EchoEnablePush' );
		if ( $pushEnabled ) {
			$moduleManager->addModule(
				'echopushsubscriptions',
				'action',
				ApiEchoPushSubscriptions::class
			);
		}
	}

}
