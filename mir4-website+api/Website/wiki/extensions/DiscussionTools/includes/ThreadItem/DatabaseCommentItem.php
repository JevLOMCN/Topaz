<?php

namespace MediaWiki\Extension\DiscussionTools\ThreadItem;

use DateTimeImmutable;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Revision\RevisionRecord;

class DatabaseCommentItem extends DatabaseThreadItem implements CommentItem {
	use CommentItemTrait {
		getHeading as protected traitGetHeading;
		getSubscribableHeading as protected traitGetSubscribableHeading;
	}

	private string $timestamp;
	private string $author;

	/**
	 * @param ProperPageIdentity $page
	 * @param RevisionRecord $rev
	 * @param string $name
	 * @param string $id
	 * @param DatabaseThreadItem|null $parent
	 * @param bool|string $transcludedFrom
	 * @param int $level
	 * @param string $timestamp
	 * @param string $author
	 */
	public function __construct(
		ProperPageIdentity $page, RevisionRecord $rev,
		string $name, string $id, ?DatabaseThreadItem $parent, $transcludedFrom, int $level,
		string $timestamp, string $author
	) {
		parent::__construct( $page, $rev, 'comment', $name, $id, $parent, $transcludedFrom, $level );
		$this->timestamp = $timestamp;
		$this->author = $author;
	}

	/**
	 * @inheritDoc
	 */
	public function getAuthor(): string {
		return $this->author;
	}

	/**
	 * @inheritDoc
	 */
	public function getTimestamp(): DateTimeImmutable {
		return new DateTimeImmutable( $this->timestamp );
	}

	/**
	 * @inheritDoc CommentItemTrait::getHeading
	 * @suppress PhanTypeMismatchReturnSuperType
	 */
	public function getHeading(): DatabaseHeadingItem {
		return $this->traitGetHeading();
	}

	/**
	 * @inheritDoc CommentItemTrait::getSubscribableHeading
	 */
	public function getSubscribableHeading(): ?DatabaseHeadingItem {
		return $this->traitGetSubscribableHeading();
	}
}
