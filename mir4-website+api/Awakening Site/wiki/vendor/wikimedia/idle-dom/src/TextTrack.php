<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * TextTrack
 *
 * @see https://dom.spec.whatwg.org/#interface-texttrack
 *
 * @property string $kind
 * @property string $label
 * @property string $language
 * @property string $id
 * @property string $inBandMetadataTrackDispatchType
 * @property TextTrackCueList|null $cues
 * @property TextTrackCueList|null $activeCues
 * @property EventHandlerNonNull|callable|null $oncuechange
 * @phan-forbid-undeclared-magic-properties
 */
interface TextTrack extends EventTarget {
	// Direct parent: EventTarget

	/**
	 * @return string
	 */
	public function getKind(): /* TextTrackKind */ string;

	/**
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * @return string
	 */
	public function getLanguage(): string;

	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * @return string
	 */
	public function getInBandMetadataTrackDispatchType(): string;

	/**
	 * @return TextTrackCueList|null
	 */
	public function getCues();

	/**
	 * @return TextTrackCueList|null
	 */
	public function getActiveCues();

	/**
	 * @param TextTrackCue $cue
	 * @return void
	 */
	public function addCue( /* TextTrackCue */ $cue ): void;

	/**
	 * @param TextTrackCue $cue
	 * @return void
	 */
	public function removeCue( /* TextTrackCue */ $cue ): void;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOncuechange();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOncuechange( /* ?mixed */ $val ): void;

}
