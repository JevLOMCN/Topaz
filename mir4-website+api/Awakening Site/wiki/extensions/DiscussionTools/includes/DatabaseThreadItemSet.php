<?php

namespace MediaWiki\Extension\DiscussionTools;

use MediaWiki\Extension\DiscussionTools\ThreadItem\CommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseCommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseHeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseThreadItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\HeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ThreadItem;
use Wikimedia\Assert\Assert;

/**
 * Groups thread items (headings and comments) generated from database.
 */
class DatabaseThreadItemSet implements ThreadItemSet {

	/** @var DatabaseThreadItem[] */
	private array $threadItems = [];
	/** @var DatabaseCommentItem[] */
	private array $commentItems = [];
	/** @var DatabaseThreadItem[][] */
	private array $threadItemsByName = [];
	/** @var DatabaseThreadItem[] */
	private array $threadItemsById = [];
	/** @var DatabaseHeadingItem[] */
	private array $threads = [];

	/**
	 * @inheritDoc
	 * @param ThreadItem $item
	 */
	public function addThreadItem( ThreadItem $item ) {
		Assert::precondition( $item instanceof DatabaseThreadItem, 'Must be DatabaseThreadItem' );

		$this->threadItems[] = $item;
		if ( $item instanceof CommentItem ) {
			$this->commentItems[] = $item;
		}
		if ( $item instanceof HeadingItem ) {
			$this->threads[] = $item;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isEmpty(): bool {
		return !$this->threadItems;
	}

	/**
	 * @inheritDoc
	 * @param ThreadItem $item
	 */
	public function updateIdAndNameMaps( ThreadItem $item ) {
		Assert::precondition( $item instanceof DatabaseThreadItem, 'Must be DatabaseThreadItem' );

		$this->threadItemsByName[ $item->getName() ][] = $item;

		$this->threadItemsById[ $item->getId() ] = $item;
	}

	/**
	 * @inheritDoc
	 * @return DatabaseThreadItem[] Thread items
	 */
	public function getThreadItems(): array {
		return $this->threadItems;
	}

	/**
	 * @inheritDoc
	 * @return DatabaseCommentItem[] Comment items
	 */
	public function getCommentItems(): array {
		return $this->commentItems;
	}

	/**
	 * @inheritDoc
	 * @return DatabaseThreadItem[] Thread items, empty array if not found
	 */
	public function findCommentsByName( string $name ): array {
		return $this->threadItemsByName[$name] ?? [];
	}

	/**
	 * @inheritDoc
	 * @return DatabaseThreadItem|null Thread item, null if not found
	 */
	public function findCommentById( string $id ): ?ThreadItem {
		return $this->threadItemsById[$id] ?? null;
	}

	/**
	 * @inheritDoc
	 * @return DatabaseHeadingItem[] Tree structure of comments, top-level items are the headings.
	 */
	public function getThreads(): array {
		return $this->threads;
	}

	/**
	 * @inheritDoc
	 * @return DatabaseHeadingItem[] Tree structure of comments, top-level items are the headings.
	 */
	public function getThreadsStructured(): array {
		return array_values( array_filter( $this->getThreads(), static function ( DatabaseThreadItem $item ) {
			return $item->getParent() === null;
		} ) );
	}
}
