<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * RadioNodeList
 *
 * @see https://dom.spec.whatwg.org/#interface-radionodelist
 *
 * @property int $length
 * @property string $value
 * @phan-forbid-undeclared-magic-properties
 */
interface RadioNodeList extends NodeList {
	// Direct parent: NodeList

	/**
	 * @return string
	 */
	public function getValue(): string;

	/**
	 * @param string $val
	 */
	public function setValue( string $val ): void;

}
