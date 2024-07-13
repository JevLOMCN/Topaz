<?php

namespace MediaWiki\Extension\DiscussionTools\ThreadItem;

/**
 * A thread item, either a heading or a comment
 */
interface ThreadItem {
	/**
	 * @return string Thread ID
	 */
	public function getId(): string;

	/**
	 * @return string Thread item name
	 */
	public function getName(): string;

	/**
	 * @return string Thread item type
	 */
	public function getType(): string;

	/**
	 * @return ThreadItem|null Parent thread item
	 */
	public function getParent(): ?ThreadItem;

	/**
	 * @return ThreadItem[] Replies to this thread item
	 */
	public function getReplies(): array;

	/**
	 * @return string|bool `false` if this item is not transcluded. A string if it's transcluded
	 *   from a single page (the page title, in text form with spaces). `true` if it's transcluded, but
	 *   we can't determine the source.
	 */
	public function getTranscludedFrom();

	/**
	 * @return int Indentation level
	 */
	public function getLevel(): int;

	/**
	 * @param bool $deep Whether to include full serialized comments in the replies key
	 * @param callable|null $callback Function to call on the returned serialized array, which
	 *  will be passed into the serialized replies as well if $deep is used
	 * @return array JSON-serializable array
	 */
	public function jsonSerialize( bool $deep = false, ?callable $callback = null ): array;
}
