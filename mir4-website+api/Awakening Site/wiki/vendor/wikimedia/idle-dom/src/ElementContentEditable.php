<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ElementContentEditable
 *
 * @see https://dom.spec.whatwg.org/#interface-elementcontenteditable
 *
 * @property string $contentEditable
 * @property string $enterKeyHint
 * @property bool $isContentEditable
 * @property string $inputMode
 * @phan-forbid-undeclared-magic-properties
 */
interface ElementContentEditable {
	/**
	 * @return string
	 */
	public function getContentEditable(): string;

	/**
	 * @param string $val
	 */
	public function setContentEditable( string $val ): void;

	/**
	 * @return string
	 */
	public function getEnterKeyHint(): string;

	/**
	 * @param string $val
	 */
	public function setEnterKeyHint( string $val ): void;

	/**
	 * @return bool
	 */
	public function getIsContentEditable(): bool;

	/**
	 * @return string
	 */
	public function getInputMode(): string;

	/**
	 * @param string $val
	 */
	public function setInputMode( string $val ): void;

}
