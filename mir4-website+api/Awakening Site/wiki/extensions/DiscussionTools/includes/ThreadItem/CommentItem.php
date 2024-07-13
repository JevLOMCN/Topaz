<?php

namespace MediaWiki\Extension\DiscussionTools\ThreadItem;

use DateTimeImmutable;

interface CommentItem extends ThreadItem {
	/**
	 * @return string Comment author
	 */
	public function getAuthor(): string;

	/**
	 * @return DateTimeImmutable Comment timestamp
	 */
	public function getTimestamp(): DateTimeImmutable;

	/**
	 * @return string Comment timestamp in standard format
	 */
	public function getTimestampString(): string;
}
