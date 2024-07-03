<?php

namespace MediaWiki\Extension\DiscussionTools\ThreadItem;

trait HeadingItemTrait {
	// phpcs:disable Squiz.WhiteSpace, MediaWiki.Commenting
	// Required ThreadItem methods (listed for Phan)
	abstract public function getName(): string;
	// Required HeadingItem methods (listed for Phan)
	abstract public function getHeadingLevel(): int;
	abstract public function isPlaceholderHeading(): bool;
	// phpcs:enable

	/**
	 * @inheritDoc
	 * @suppress PhanTraitParentReference
	 */
	public function jsonSerialize( bool $deep = false, ?callable $callback = null ): array {
		return array_merge( [
			'headingLevel' => $this->isPlaceholderHeading() ? null : $this->getHeadingLevel(),
			// Used for topic subscriptions. Not added to CommentItem's yet as there is
			// no use case for it.
			'name' => $this->getName(),
		], parent::jsonSerialize( $deep, $callback ) );
	}

	/**
	 * Check whether this heading can be used for topic subscriptions.
	 */
	public function isSubscribable(): bool {
		return (
			// Placeholder headings have nothing to attach the button to.
			!$this->isPlaceholderHeading() &&
			// We only allow subscribing to level 2 headings, because the user interface for sub-headings
			// would be difficult to present.
			$this->getHeadingLevel() === 2 &&
			// Check if the name corresponds to a section that contain no comments (only sub-sections).
			// They can't be distinguished from each other, so disallow subscribing.
			$this->getName() !== 'h-'
		);
	}
}
