<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLOrSVGElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlorsvgelement
 *
 * @property DOMStringMap $dataset
 * @property string $nonce
 * @property int $tabIndex
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLOrSVGElement {
	/**
	 * @return DOMStringMap
	 */
	public function getDataset();

	/**
	 * @return string
	 */
	public function getNonce(): string;

	/**
	 * @param string $val
	 */
	public function setNonce( string $val ): void;

	/**
	 * @return int
	 */
	public function getTabIndex(): int;

	/**
	 * @param int $val
	 */
	public function setTabIndex( int $val ): void;

	/**
	 * @return void
	 */
	public function blur(): void;

}
