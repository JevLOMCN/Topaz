<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * MediaList
 *
 * @see https://dom.spec.whatwg.org/#interface-medialist
 *
 * @property string $mediaText
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface MediaList extends \ArrayAccess {
	/**
	 * @return string
	 */
	public function getMediaText(): string;

	/**
	 * @param ?string $val
	 */
	public function setMediaText( ?string $val ): void;

	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $index
	 * @return ?string
	 */
	public function item( int $index ): ?string;

	/**
	 * @param string $medium
	 * @return void
	 */
	public function appendMedium( string $medium ): void;

	/**
	 * @param string $medium
	 * @return void
	 */
	public function deleteMedium( string $medium ): void;

}
