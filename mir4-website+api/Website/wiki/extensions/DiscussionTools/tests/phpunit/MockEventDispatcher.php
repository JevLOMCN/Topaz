<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use MediaWiki\Extension\DiscussionTools\ContentThreadItemSet;
use MediaWiki\Extension\DiscussionTools\Notifications\EventDispatcher;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

class MockEventDispatcher extends EventDispatcher {

	/**
	 * Public for testing
	 *
	 * Note that we can't use TestingAccessWrapper instead of this, because it doesn't support passing
	 * arguments by reference (causes exceptions like "PHPUnit\Framework\Error\Warning: Parameter 1 to
	 * ... expected to be a reference, value given").
	 */
	public static function generateEventsFromItemSets(
		array &$events,
		ContentThreadItemSet $oldItemSet,
		ContentThreadItemSet $newItemSet,
		RevisionRecord $newRevRecord,
		PageIdentity $title,
		UserIdentity $user
	): void {
		parent::generateEventsFromItemSets(
			$events,
			$oldItemSet,
			$newItemSet,
			$newRevRecord,
			$title,
			$user
		);
	}

	/**
	 * No-op for testing
	 */
	public static function addCommentChangeTag( RevisionRecord $newRevRecord ): void {
	}

	/**
	 * No-op for testing
	 */
	protected static function addAutoSubscription( UserIdentity $user, Title $title, string $itemName ): void {
	}

}
