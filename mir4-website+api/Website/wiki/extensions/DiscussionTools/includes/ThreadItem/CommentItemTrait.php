<?php

namespace MediaWiki\Extension\DiscussionTools\ThreadItem;

use DateTimeImmutable;
use MediaWiki\MediaWikiServices;
use RuntimeException;

trait CommentItemTrait {
	// phpcs:disable Squiz.WhiteSpace, MediaWiki.Commenting
	// Required ThreadItem methods (listed for Phan)
	abstract public function getParent(): ?ThreadItem;
	// Required CommentItem methods (listed for Phan)
	abstract public function getAuthor(): string;
	abstract public function getTimestamp(): DateTimeImmutable;
	// phpcs:enable

	/**
	 * @inheritDoc
	 * @suppress PhanTraitParentReference
	 */
	public function jsonSerialize( bool $deep = false, ?callable $callback = null ): array {
		return array_merge( [
			'timestamp' => $this->getTimestampString(),
			'author' => $this->getAuthor(),
		], parent::jsonSerialize( $deep, $callback ) );
	}

	/**
	 * @return array JSON-serializable array
	 */
	public function jsonSerializeForDiff(): array {
		$data = $this->jsonSerialize();

		$heading = $this->getHeading();
		$data['headingId'] = $heading->getId();
		$subscribableHeading = $this->getSubscribableHeading();
		$data['subscribableHeadingId'] = $subscribableHeading ? $subscribableHeading->getId() : null;

		return $data;
	}

	/**
	 * Get the comment timestamp in the format used in IDs and names.
	 *
	 * Depending on the date of the comment, this may use one of two formats:
	 *
	 *  - For dates prior to 'DiscussionToolsTimestampFormatSwitchTime' (by default 2022-07-12):
	 *    Uses ISO 8601 date. Almost DateTimeInterface::RFC3339_EXTENDED, but ending with 'Z' instead
	 *    of '+00:00', like Date#toISOString in JavaScript.
	 *
	 *  - For dates on or after 'DiscussionToolsTimestampFormatSwitchTime' (by default 2022-07-12):
	 *    Uses MediaWiki timestamp (TS_MW in MediaWiki PHP code).
	 *
	 * @return string Comment timestamp in standard format
	 */
	public function getTimestampString(): string {
		$dtConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'discussiontools' );
		$switchTime = new DateTimeImmutable(
			$dtConfig->get( 'DiscussionToolsTimestampFormatSwitchTime' )
		);
		$timestamp = $this->getTimestamp();
		if ( $timestamp < $switchTime ) {
			return $timestamp->format( 'Y-m-d\TH:i:s.v\Z' );
		} else {
			return $timestamp->format( 'YmdHis' );
		}
	}

	/**
	 * @return ContentHeadingItem Closest ancestor which is a HeadingItem
	 */
	public function getHeading(): HeadingItem {
		$parent = $this;
		while ( $parent instanceof CommentItem ) {
			$parent = $parent->getParent();
		}
		if ( !( $parent instanceof HeadingItem ) ) {
			throw new RuntimeException( 'Heading parent not found' );
		}
		return $parent;
	}

	/**
	 * @return ContentHeadingItem|null Closest heading that can be used for topic subscriptions
	 */
	public function getSubscribableHeading(): ?HeadingItem {
		$heading = $this->getHeading();
		while ( $heading instanceof HeadingItem && !$heading->isSubscribable() ) {
			$heading = $heading->getParent();
		}
		return $heading instanceof HeadingItem ? $heading : null;
	}
}
