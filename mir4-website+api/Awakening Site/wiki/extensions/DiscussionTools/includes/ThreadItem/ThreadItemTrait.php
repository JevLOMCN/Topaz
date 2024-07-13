<?php

namespace MediaWiki\Extension\DiscussionTools\ThreadItem;

trait ThreadItemTrait {
	// phpcs:disable Squiz.WhiteSpace, MediaWiki.Commenting
	// Required ThreadItem methods (listed for Phan)
	abstract public function getId(): string;
	abstract public function getType(): string;
	abstract public function getReplies(): array;
	abstract public function getLevel(): int;
	// phpcs:enable

	/**
	 * @param bool $deep Whether to include full serialized comments in the replies key
	 * @param callable|null $callback Function to call on the returned serialized array, which
	 *  will be passed into the serialized replies as well if $deep is used
	 * @return array JSON-serializable array
	 */
	public function jsonSerialize( bool $deep = false, ?callable $callback = null ): array {
		// The output of this method can end up in the HTTP cache (Varnish). Avoid changing it;
		// and when doing so, ensure that frontend code can handle both the old and new outputs.
		// See ThreadItem.static.newFromJSON in JS.

		$array = [
			'type' => $this->getType(),
			'level' => $this->getLevel(),
			'id' => $this->getId(),
			'replies' => array_map( static function ( ThreadItem $comment ) use ( $deep, $callback ) {
				return $deep ? $comment->jsonSerialize( $deep, $callback ) : $comment->getId();
			}, $this->getReplies() )
		];
		if ( $callback ) {
			$callback( $array, $this );
		}
		return $array;
	}
}
