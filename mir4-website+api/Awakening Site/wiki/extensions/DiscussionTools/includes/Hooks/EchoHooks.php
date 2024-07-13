<?php
/**
 * DiscussionTools echo hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use EchoUserLocator;
use MediaWiki\Extension\DiscussionTools\Notifications\AddedTopicPresentationModel;
use MediaWiki\Extension\DiscussionTools\Notifications\EnhancedEchoEditUserTalkPresentationModel;
use MediaWiki\Extension\DiscussionTools\Notifications\EnhancedEchoMentionPresentationModel;
use MediaWiki\Extension\DiscussionTools\Notifications\EventDispatcher;
use MediaWiki\Extension\DiscussionTools\Notifications\RemovedTopicPresentationModel;
use MediaWiki\Extension\DiscussionTools\Notifications\SubscribedNewCommentPresentationModel;
use MediaWiki\Extension\Notifications\Hooks\BeforeCreateEchoEventHook;
use MediaWiki\Extension\Notifications\Hooks\EchoGetBundleRulesHook;
use MediaWiki\Extension\Notifications\Hooks\EchoGetEventsForRevisionHook;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Revision\RevisionRecord;
use Wikimedia\Parsoid\Core\ResourceLimitExceededException;

class EchoHooks implements
	BeforeCreateEchoEventHook,
	EchoGetBundleRulesHook,
	EchoGetEventsForRevisionHook
{
	/**
	 * Add notification events to Echo
	 */
	public function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$icons
	) {
		// The following messages are generated upstream
		// * echo-category-title-dt-subscription
		$notificationCategories['dt-subscription'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-dt-subscription',
		];
		$notifications['dt-subscribed-new-comment'] = [
			'category' => 'dt-subscription',
			'group' => 'interactive',
			'section' => 'message',
			'user-locators' => [
				[ [ EventDispatcher::class, 'locateSubscribedUsers' ] ]
			],
			// Exclude mentioned users and talk page owner from our notification, to avoid
			// duplicate notifications for a single comment
			'user-filters' => [
				[
					[ EchoUserLocator::class, 'locateFromEventExtra' ],
					[ 'mentioned-users' ]
				],
				[ [ EchoUserLocator::class, 'locateTalkPageOwner' ] ],
			],
			'presentation-model' => SubscribedNewCommentPresentationModel::class,
			'bundle' => [
				'web' => true,
				'email' => true,
				'expandable' => true,
			],
		];

		// The following messages are generated upstream
		// * echo-category-title-dt-subscription-archiving
		$notificationCategories['dt-subscription-archiving'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-dt-subscription-archiving',
		];
		$notifications['dt-removed-topic'] = [
			'category' => 'dt-subscription-archiving',
			'group' => 'interactive',
			'section' => 'message',
			'user-locators' => [
				[ [ EventDispatcher::class, 'locateSubscribedUsers' ] ]
			],
			'presentation-model' => RemovedTopicPresentationModel::class,
			'bundle' => [
				'web' => true,
				'email' => true,
				'expandable' => true,
			],
		];
		$notifications['dt-added-topic'] = [
			'category' => 'dt-subscription',
			'group' => 'interactive',
			'section' => 'message',
			'user-locators' => [
				[ [ EventDispatcher::class, 'locateSubscribedUsers' ] ]
			],
			'presentation-model' => AddedTopicPresentationModel::class,
			'bundle' => [
				'web' => true,
				'email' => true,
				'expandable' => true,
			],
		];

		// Override default handlers
		$notifications['edit-user-talk']['presentation-model'] = EnhancedEchoEditUserTalkPresentationModel::class;
		$notifications['mention']['presentation-model'] = EnhancedEchoMentionPresentationModel::class;
	}

	public function onEchoGetBundleRules( Event $event, string &$bundleString ) {
		switch ( $event->getType() ) {
			case 'dt-subscribed-new-comment':
				$bundleString = $event->getType() . '-' . $event->getExtraParam( 'subscribed-comment-name' );
				break;
			case 'dt-added-topic':
			case 'dt-removed-topic':
				$bundleString = $event->getType() . '-' . $event->getTitle()->getNamespace()
					. '-' . $event->getTitle()->getDBkey();
				break;
		}
	}

	/**
	 * @throws ResourceLimitExceededException
	 */
	public function onEchoGetEventsForRevision( array &$events, RevisionRecord $revision, bool $isRevert ) {
		if ( $isRevert ) {
			return;
		}
		EventDispatcher::generateEventsForRevision( $events, $revision );
	}
}
