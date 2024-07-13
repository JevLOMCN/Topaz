<?php
/**
 * DiscussionTools event dispatcher
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Notifications;

use DateInterval;
use DateTimeImmutable;
use ExtensionRegistry;
use IDBAccessObject;
use Iterator;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\DiscussionTools\CommentUtils;
use MediaWiki\Extension\DiscussionTools\ContentThreadItemSet;
use MediaWiki\Extension\DiscussionTools\Hooks\HookUtils;
use MediaWiki\Extension\DiscussionTools\SubscriptionItem;
use MediaWiki\Extension\DiscussionTools\SubscriptionStore;
use MediaWiki\Extension\DiscussionTools\ThreadItem\CommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentCommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentHeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentThreadItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\HeadingItem;
use MediaWiki\Extension\EventLogging\EventLogging;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use RequestContext;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Core\ResourceLimitExceededException;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class EventDispatcher {
	/**
	 * @throws ResourceLimitExceededException
	 */
	private static function getParsedRevision( RevisionRecord $revRecord ): ContentThreadItemSet {
		return HookUtils::parseRevisionParsoidHtml( $revRecord, __METHOD__ );
	}

	/**
	 * @throws ResourceLimitExceededException
	 */
	public static function generateEventsForRevision( array &$events, RevisionRecord $newRevRecord ): void {
		$services = MediaWikiServices::getInstance();

		$revisionStore = $services->getRevisionStore();
		$oldRevRecord = $revisionStore->getPreviousRevision( $newRevRecord, IDBAccessObject::READ_LATEST );

		$title = Title::newFromLinkTarget(
			$newRevRecord->getPageAsLinkTarget()
		);
		if ( !HookUtils::isAvailableForTitle( $title ) ) {
			// Not a talk page
			return;
		}

		$user = $newRevRecord->getUser();
		if ( !$user ) {
			// User can be null if the user is deleted, but this is unlikely
			// to be the case if the user just made an edit
			return;
		}

		if ( $oldRevRecord !== null ) {
			$oldItemSet = static::getParsedRevision( $oldRevRecord );
		} else {
			// Page creation
			$doc = DOMUtils::parseHTML( '' );
			$container = DOMCompat::getBody( $doc );
			$oldItemSet = $services->getService( 'DiscussionTools.CommentParser' )
				->parse( $container, $title->getTitleValue() );
		}
		$newItemSet = static::getParsedRevision( $newRevRecord );

		static::generateEventsFromItemSets( $events, $oldItemSet, $newItemSet, $newRevRecord, $title, $user );
	}

	/**
	 * For each level 2 heading, get a list of comments in the thread grouped by names, then IDs.
	 * (Compare by name first, as ID could be changed by a parent comment being moved/deleted.)
	 * Comments in level 3+ sub-threads are grouped together with the parent thread.
	 *
	 * For any other headings (including level 3+ before the first level 2 heading, level 1, and
	 * section zero placeholder headings), ignore comments in those threads.
	 *
	 * @param ContentThreadItem[] $items
	 * @return ContentCommentItem[][][]
	 */
	private static function groupCommentsByThreadAndName( array $items ): array {
		$comments = [];
		$threadName = null;
		foreach ( $items as $item ) {
			if ( $item instanceof HeadingItem && ( $item->getHeadingLevel() < 2 || $item->isPlaceholderHeading() ) ) {
				$threadName = null;
			} elseif ( $item instanceof HeadingItem && $item->getHeadingLevel() === 2 ) {
				$threadName = $item->getName();
			} elseif ( $item instanceof CommentItem && $threadName !== null ) {
				$comments[ $threadName ][ $item->getName() ][ $item->getId() ] = $item;
			}
		}
		return $comments;
	}

	/**
	 * Get a list of all subscribable headings, grouped by name in case there are duplicates.
	 *
	 * @param ContentHeadingItem[] $items
	 * @return ContentHeadingItem[][]
	 */
	private static function groupSubscribableHeadings( array $items ): array {
		$headings = [];
		foreach ( $items as $item ) {
			if ( $item->isSubscribable() ) {
				$headings[ $item->getName() ][ $item->getId() ] = $item;
			}
		}
		return $headings;
	}

	/**
	 * Compare two lists of thread items, return those in $new but not $old.
	 *
	 * @param ContentThreadItem[][] $old
	 * @param ContentThreadItem[][] $new
	 * @return iterable<ContentThreadItem>
	 */
	private static function findAddedItems( array $old, array $new ) {
		foreach ( $new as $itemName => $nameNewItems ) {
			// Usually, there will be 0 or 1 $nameNewItems, and 0 $nameOldItems,
			// and $addedCount will be 0 or 1.
			//
			// But when multiple replies are added in one edit, or in multiple edits within the same
			// minute, there may be more, and the complex logic below tries to make the best guess
			// as to which items are actually new. See the 'multiple' and 'sametime' test cases.
			//
			$nameOldItems = $old[ $itemName ] ?? [];
			$addedCount = count( $nameNewItems ) - count( $nameOldItems );

			if ( $addedCount > 0 ) {
				// For any name that occurs more times in new than old, report that many new items,
				// preferring IDs that did not occur in old, then preferring items lower on the page.
				foreach ( array_reverse( $nameNewItems ) as $itemId => $newItem ) {
					if ( $addedCount > 0 && !isset( $nameOldItems[ $itemId ] ) ) {
						yield $newItem;
						$addedCount--;
					}
				}
				foreach ( array_reverse( $nameNewItems ) as $itemId => $newItem ) {
					if ( $addedCount > 0 ) {
						yield $newItem;
						$addedCount--;
					}
				}
				Assert::postcondition( $addedCount === 0, 'Reported expected number of items' );
			}
		}
	}

	/**
	 * Helper for generateEventsForRevision(), separated out for easier testing.
	 */
	protected static function generateEventsFromItemSets(
		array &$events,
		ContentThreadItemSet $oldItemSet,
		ContentThreadItemSet $newItemSet,
		RevisionRecord $newRevRecord,
		PageIdentity $title,
		UserIdentity $user
	): void {
		$newComments = static::groupCommentsByThreadAndName( $newItemSet->getThreadItems() );
		$oldComments = static::groupCommentsByThreadAndName( $oldItemSet->getThreadItems() );
		$addedComments = [];
		foreach ( $newComments as $threadName => $threadNewComments ) {
			$threadOldComments = $oldComments[ $threadName ] ?? [];
			foreach ( static::findAddedItems( $threadOldComments, $threadNewComments ) as $newComment ) {
				Assert::precondition( $newComment instanceof ContentCommentItem, 'Must be ContentCommentItem' );
				$addedComments[] = $newComment;
			}
		}

		$newHeadings = static::groupSubscribableHeadings( $newItemSet->getThreads() );
		$oldHeadings = static::groupSubscribableHeadings( $oldItemSet->getThreads() );

		$addedHeadings = [];
		foreach ( static::findAddedItems( $oldHeadings, $newHeadings ) as $newHeading ) {
			Assert::precondition( $newHeading instanceof ContentHeadingItem, 'Must be ContentHeadingItem' );
			$addedHeadings[] = $newHeading;
		}

		$removedHeadings = [];
		// Pass swapped parameters to findAddedItems() to find *removed* items
		foreach ( static::findAddedItems( $newHeadings, $oldHeadings ) as $oldHeading ) {
			Assert::precondition( $oldHeading instanceof ContentHeadingItem, 'Must be ContentHeadingItem' );
			$removedHeadings[] = $oldHeading;
		}

		$mentionedUsers = [];
		foreach ( $events as &$event ) {
			if ( $event['type'] === 'mention' || $event['type'] === 'mention-summary' ) {
				// Save mentioned users in our events, so that we can exclude them from our notification,
				// to avoid duplicate notifications for a single comment.
				// Array is keyed by user id so we can do a simple array merge.
				$mentionedUsers += $event['extra']['mentioned-users'];
			}

			if ( count( $addedComments ) === 1 ) {
				// If this edit was a new user talk message according to Echo,
				// and we also found exactly one new comment,
				// add some extra information to the edit-user-talk event.
				if ( $event['type'] === 'edit-user-talk' ) {
					$event['extra'] += [
						'comment-id' => $addedComments[0]->getId(),
						'comment-name' => $addedComments[0]->getName(),
						'content' => $addedComments[0]->getBodyText( true ),
					];
				}

				// Similarly for mentions.
				// We don't handle 'content' in this case, as Echo makes its own snippets.
				if ( $event['type'] === 'mention' ) {
					$event['extra'] += [
						'comment-id' => $addedComments[0]->getId(),
						'comment-name' => $addedComments[0]->getName(),
					];
				}
			}
		}

		if ( $addedComments ) {
			// It's a bit weird to do this here, in the middle of the hook handler for Echo. However:
			// * Echo calls this from a PageSaveComplete hook handler as a DeferredUpdate,
			//   which is exactly how we would do this otherwise
			// * It allows us to reuse the generated comment trees without any annoying caching
			static::addCommentChangeTag( $newRevRecord );
			// For very similar reasons, we do logging here
			static::logAddedComments( $addedComments, $newRevRecord, $title, $user );
		}

		foreach ( $addedComments as $newComment ) {
			// Ignore comments by other users, e.g. in case of reverts or a discussion being moved.
			// TODO: But what about someone signing another's comment?
			if ( $newComment->getAuthor() !== $user->getName() ) {
				continue;
			}
			// Ignore comments which are more than 10 minutes old, as this may be a user archiving
			// their own comment. (T290803)
			$revTimestamp = new DateTimeImmutable( $newRevRecord->getTimestamp() );
			$threshold = $revTimestamp->sub( new DateInterval( 'PT10M' ) );
			if ( $newComment->getTimestamp() <= $threshold ) {
				continue;
			}
			$heading = $newComment->getSubscribableHeading();
			if ( !$heading ) {
				continue;
			}
			$events[] = [
				// This probably should've been called "dt-new-comment": this code is
				// unaware if there are any subscriptions to the containing topic and
				// an event is generated for every comment posted.
				// However, changing this would require a complex migration.
				'type' => 'dt-subscribed-new-comment',
				'title' => $title,
				'extra' => [
					'subscribed-comment-name' => $heading->getName(),
					'comment-id' => $newComment->getId(),
					'comment-name' => $newComment->getName(),
					'content' => $newComment->getBodyText( true ),
					'section-title' => $heading->getLinkableTitle(),
					'revid' => $newRevRecord->getId(),
					'mentioned-users' => $mentionedUsers,
				],
				'agent' => $user,
			];

			$titleForSubscriptions = Title::castFromPageIdentity( $title )->createFragmentTarget( $heading->getText() );
			static::addAutoSubscription( $user, $titleForSubscriptions, $heading->getName() );
		}

		foreach ( $removedHeadings as $oldHeading ) {
			$events[] = [
				'type' => 'dt-removed-topic',
				'title' => $title,
				'extra' => [
					'subscribed-comment-name' => $oldHeading->getName(),
					'heading-id' => $oldHeading->getId(),
					'heading-name' => $oldHeading->getName(),
					'section-title' => $oldHeading->getLinkableTitle(),
					'revid' => $newRevRecord->getId(),
				],
				'agent' => $user,
			];
		}

		$titleObj = Title::castFromPageIdentity( $title );
		if ( $titleObj ) {
			foreach ( $addedHeadings as $newHeading ) {
				// Don't use $event here as that already exists as a reference from above
				$addTopicEvent = [
					'type' => 'dt-added-topic',
					'title' => $title,
					'extra' => [
						// As no one can be subscribed to a topic before it has been created,
						// we will notify users who have subscribed to the whole page.
						'subscribed-comment-name' => CommentUtils::getNewTopicsSubscriptionId( $titleObj ),
						'heading-id' => $newHeading->getId(),
						'heading-name' => $newHeading->getName(),
						'section-title' => $newHeading->getLinkableTitle(),
						'revid' => $newRevRecord->getId(),
					],
					'agent' => $user,
				];
				// Add metadata about the accompanying comment
				$firstComment = $newHeading->getOldestReply();
				if ( $firstComment ) {
					$addTopicEvent['extra']['comment-id'] = $firstComment->getId();
					$addTopicEvent['extra']['comment-name'] = $firstComment->getName();
					$addTopicEvent['extra']['content'] = $firstComment->getBodyText( true );
				}
				$events[] = $addTopicEvent;
			}
		}
	}

	/**
	 * Add our change tag for a revision that adds new comments.
	 */
	protected static function addCommentChangeTag( RevisionRecord $newRevRecord ): void {
		// Unclear if DeferredUpdates::addCallableUpdate() is needed,
		// but every extension does it that way.
		DeferredUpdates::addCallableUpdate( static function () use ( $newRevRecord ) {
			MediaWikiServices::getInstance()->getChangeTagsStore()
				->addTags( [ 'discussiontools-added-comment' ], null, $newRevRecord->getId() );
		} );
	}

	/**
	 * Add an automatic subscription to the given item, assuming the user has automatic subscriptions
	 * enabled.
	 */
	protected static function addAutoSubscription( UserIdentity $user, Title $title, string $itemName ): void {
		$dtConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'discussiontools' );

		if (
			$dtConfig->get( 'DiscussionToolsAutoTopicSubEditor' ) === 'any' &&
			HookUtils::shouldAddAutoSubscription( $user, $title )
		) {
			/** @var SubscriptionStore $parser */
			$subscriptionStore = MediaWikiServices::getInstance()->getService( 'DiscussionTools.SubscriptionStore' );
			$subscriptionStore->addAutoSubscriptionForUser( $user, $title, $itemName );
		}
	}

	/**
	 * Return all users subscribed to a comment
	 *
	 * @param Event $event
	 * @param int $batchSize
	 * @return UserIdentity[]|Iterator<UserIdentity>
	 */
	public static function locateSubscribedUsers( Event $event, $batchSize = 500 ) {
		$commentName = $event->getExtraParam( 'subscribed-comment-name' );

		/** @var SubscriptionStore $subscriptionStore */
		$subscriptionStore = MediaWikiServices::getInstance()->getService( 'DiscussionTools.SubscriptionStore' );
		$subscriptionItems = $subscriptionStore->getSubscriptionItemsForTopic(
			$commentName,
			[ SubscriptionStore::STATE_SUBSCRIBED, SubscriptionStore::STATE_AUTOSUBSCRIBED ]
		);

		// Update notified timestamps
		$subscriptionStore->updateSubscriptionNotifiedTimestamp(
			null,
			$commentName
		);

		// TODD: Have this return an Iterator instead?
		$users = array_map( static function ( SubscriptionItem $item ) {
			return $item->getUserIdentity();
		}, $subscriptionItems );

		return $users;
	}

	/**
	 * Log stuff to EventLogging's Schema:TalkPageEvent
	 * If you don't have EventLogging installed, does nothing.
	 *
	 * @param array $addedComments
	 * @param RevisionRecord $newRevRecord
	 * @param PageIdentity $title
	 * @param UserIdentity $identity
	 * @return bool Whether events were logged
	 */
	protected static function logAddedComments(
		array $addedComments,
		RevisionRecord $newRevRecord,
		PageIdentity $title,
		UserIdentity $identity
	): bool {
		global $wgDTSchemaEditAttemptStepOversample, $wgDBname;
		$context = RequestContext::getMain();
		$request = $context->getRequest();
		// We've reached here through Echo's post-save deferredupdate, which
		// might either be after an API request from DiscussionTools or a
		// regular POST from WikiEditor. Both should have this value snuck
		// into their request if their session is being logged.
		if ( !$request->getCheck( 'editingStatsId' ) ) {
			return false;
		}
		$editingStatsId = $request->getVal( 'editingStatsId' );
		$isDiscussionTools = $request->getCheck( 'dttags' );

		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( !$extensionRegistry->isLoaded( 'EventLogging' ) ) {
			return false;
		}
		if ( !$extensionRegistry->isLoaded( 'WikimediaEvents' ) ) {
			return false;
		}
		$inSample = static::inEventSample( $editingStatsId );
		$shouldOversample = ( $isDiscussionTools && $wgDTSchemaEditAttemptStepOversample ) || (
				// @phan-suppress-next-line PhanUndeclaredClassMethod
				\WikimediaEvents\WikimediaEventsHooks::shouldSchemaEditAttemptStepOversample( $context )
			);
		if ( !$inSample && !$shouldOversample ) {
			return false;
		}

		$services = MediaWikiServices::getInstance();
		$editTracker = $services->getUserEditTracker();
		$userIdentityUtils = $services->getUserIdentityUtils();

		$commonData = [
			'$schema' => '/analytics/mediawiki/talk_page_edit/1.2.0',
			'action' => 'publish',
			'session_id' => $editingStatsId,
			'page_id' => $newRevRecord->getPageId(),
			'page_namespace' => $title->getNamespace(),
			'revision_id' => $newRevRecord->getId() ?: 0,
			'performer' => [
				// Note: we're logging the user who made the edit, not the user who's signed on the comment
				'user_id' => $identity->getId(),
				'user_edit_count' => $editTracker->getUserEditCount( $identity ) ?: 0,
				// Retention-safe values:
				'user_is_anonymous' => !$identity->isRegistered(),
				'user_is_temp' => $userIdentityUtils->isTemp( $identity ),
				'user_edit_count_bucket' => \UserBucketProvider::getUserEditCountBucket( $identity ) ?: 'N/A',
			],
			'database' => $wgDBname,
			// This is unreliable, but sufficient for our purposes; we
			// mostly just want to see the difference between DT and
			// everything-else:
			'integration' => $isDiscussionTools ? 'discussiontools' : 'page',
		];

		foreach ( $addedComments as $comment ) {
			$heading = $comment->getSubscribableHeading();
			$parent = $comment->getParent();
			if ( !$heading || !$parent ) {
				continue;
			}
			if ( $parent->getType() === 'heading' ) {
				if ( count( $heading->getReplies() ) === 1 ) {
					// A new heading was added when this comment was created
					$component_type = 'topic';
				} else {
					$component_type = 'comment';
				}
			} else {
				$component_type = 'response';
			}
			EventLogging::submit( 'mediawiki.talk_page_edit', array_merge( $commonData, [
				'component_type' => $component_type,
				'topic_id' => $heading->getId(),
				'comment_id' => $comment->getId(),
				'comment_parent_id' => $parent->getId(),
			] ) );
		}

		return true;
	}

	/**
	 * Should the current session be sampled for EventLogging?
	 *
	 * @param string $sessionId
	 * @return bool Whether to sample the session
	 */
	protected static function inEventSample( string $sessionId ): bool {
		global $wgDTSchemaEditAttemptStepSamplingRate, $wgWMESchemaEditAttemptStepSamplingRate;
		// Sample 6.25%
		$samplingRate = 0.0625;
		if ( isset( $wgDTSchemaEditAttemptStepSamplingRate ) ) {
			$samplingRate = $wgDTSchemaEditAttemptStepSamplingRate;
		}
		if ( isset( $wgWMESchemaEditAttemptStepSamplingRate ) ) {
			$samplingRate = $wgWMESchemaEditAttemptStepSamplingRate;
		}
		if ( $samplingRate === 0 ) {
			return false;
		}
		$inSample = EventLogging::sessionInSample(
			(int)( 1 / $samplingRate ), $sessionId
		);
		return $inSample;
	}

}
