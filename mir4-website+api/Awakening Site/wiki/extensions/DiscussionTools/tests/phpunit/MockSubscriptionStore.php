<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use MediaWiki\Extension\DiscussionTools\SubscriptionItem;
use MediaWiki\Extension\DiscussionTools\SubscriptionStore;
use MediaWiki\User\UserIdentity;

class MockSubscriptionStore extends SubscriptionStore {

	/**
	 * @param mixed ...$args Unused, required for inheritance
	 */
	public function __construct( ...$args ) {
	}

	/**
	 * @param UserIdentity $user Unused, required for inheritance
	 * @param array|null $itemNames Unused, required for inheritance
	 * @param int[]|null $state Unused, required for inheritance
	 * @param array $options Unused, required for inheritance
	 * @return SubscriptionItem[]
	 */
	public function getSubscriptionItemsForUser(
		UserIdentity $user,
		?array $itemNames = null,
		?array $state = null,
		array $options = []
	): array {
		return [];
	}

}
