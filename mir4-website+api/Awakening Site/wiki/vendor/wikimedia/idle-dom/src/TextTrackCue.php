<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * TextTrackCue
 *
 * @see https://dom.spec.whatwg.org/#interface-texttrackcue
 *
 * @property TextTrack|null $track
 * @property string $id
 * @property float $startTime
 * @property float $endTime
 * @property bool $pauseOnExit
 * @property EventHandlerNonNull|callable|null $onenter
 * @property EventHandlerNonNull|callable|null $onexit
 * @phan-forbid-undeclared-magic-properties
 */
interface TextTrackCue extends EventTarget {
	// Direct parent: EventTarget

	/**
	 * @return TextTrack|null
	 */
	public function getTrack();

	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * @param string $val
	 */
	public function setId( string $val ): void;

	/**
	 * @return float
	 */
	public function getStartTime(): float;

	/**
	 * @param float $val
	 */
	public function setStartTime( float $val ): void;

	/**
	 * @return float
	 */
	public function getEndTime(): float;

	/**
	 * @param float $val
	 */
	public function setEndTime( float $val ): void;

	/**
	 * @return bool
	 */
	public function getPauseOnExit(): bool;

	/**
	 * @param bool $val
	 */
	public function setPauseOnExit( bool $val ): void;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnenter();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnenter( /* ?mixed */ $val ): void;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnexit();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnexit( /* ?mixed */ $val ): void;

}
