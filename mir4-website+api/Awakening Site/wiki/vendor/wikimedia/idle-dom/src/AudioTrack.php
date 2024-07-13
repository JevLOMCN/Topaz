<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * AudioTrack
 *
 * @see https://dom.spec.whatwg.org/#interface-audiotrack
 *
 * @property string $id
 * @property string $kind
 * @property string $label
 * @property string $language
 * @property bool $enabled
 * @phan-forbid-undeclared-magic-properties
 */
interface AudioTrack {
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
	public function getEnabled(): bool;

	/**
	 * @param bool $val
	 */
	public function setEnabled( bool $val ): void;

}
