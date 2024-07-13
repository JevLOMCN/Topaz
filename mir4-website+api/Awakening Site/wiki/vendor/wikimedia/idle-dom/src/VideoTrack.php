<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * VideoTrack
 *
 * @see https://dom.spec.whatwg.org/#interface-videotrack
 *
 * @property string $id
 * @property string $kind
 * @property string $label
 * @property string $language
 * @property bool $selected
 * @phan-forbid-undeclared-magic-properties
 */
interface VideoTrack {
	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * @return string
	 */
	public function getKind(): string;

	/**
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * @return string
	 */
	public function getLanguage(): string;

	/**
	 * @return bool
	 */
	public function getSelected(): bool;

	/**
	 * @param bool $val
	 */
	public function setSelected( bool $val ): void;

}
