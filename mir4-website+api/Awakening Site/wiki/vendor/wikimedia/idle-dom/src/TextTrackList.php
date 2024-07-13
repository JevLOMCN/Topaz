<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * TextTrackList
 *
 * @see https://dom.spec.whatwg.org/#interface-texttracklist
 *
 * @property int $length
 * @property EventHandlerNonNull|callable|null $onchange
 * @property EventHandlerNonNull|callable|null $onaddtrack
 * @property EventHandlerNonNull|callable|null $onremovetrack
 * @phan-forbid-undeclared-magic-properties
 */
interface TextTrackList extends EventTarget, \ArrayAccess {
	// Direct parent: EventTarget

	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $index
	 * @return TextTrack
	 */
	public function item( int $index );

	/**
	 * @param string $id
	 * @return TextTrack|null
	 */
	public function getTrackById( string $id );

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnchange();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnchange( /* ?mixed */ $val ): void;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnaddtrack();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnaddtrack( /* ?mixed */ $val ): void;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnremovetrack();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnremovetrack( /* ?mixed */ $val ): void;

}
