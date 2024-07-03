<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * StyleSheet
 *
 * @see https://dom.spec.whatwg.org/#interface-stylesheet
 *
 * @property string $type
 * @property ?string $href
 * @property Element|ProcessingInstruction|null $ownerNode
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property ?string $title
 * @property MediaList $media
 * @property bool $disabled
 * @phan-forbid-undeclared-magic-properties
 */
interface StyleSheet {
	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @return ?string
	 */
	public function getHref(): ?string;

	/**
	 * @return Element|ProcessingInstruction|null
	 */
	public function getOwnerNode();

	/**
	 * @return CSSStyleSheet|null
	 */
	public function getParentStyleSheet();

	/**
	 * @return ?string
	 */
	public function getTitle(): ?string;

	/**
	 * @return MediaList
	 */
	public function getMedia();

	/**
	 * @param ?string $val
	 */
	public function setMedia( ?string $val ): void;

	/**
	 * @return bool
	 */
	public function getDisabled(): bool;

	/**
	 * @param bool $val
	 */
	public function setDisabled( bool $val ): void;

}
