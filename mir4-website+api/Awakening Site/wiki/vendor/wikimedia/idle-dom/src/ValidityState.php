<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ValidityState
 *
 * @see https://dom.spec.whatwg.org/#interface-validitystate
 *
 * @property bool $valueMissing
 * @property bool $typeMismatch
 * @property bool $patternMismatch
 * @property bool $tooLong
 * @property bool $tooShort
 * @property bool $rangeUnderflow
 * @property bool $rangeOverflow
 * @property bool $stepMismatch
 * @property bool $badInput
 * @property bool $customError
 * @property bool $valid
 * @phan-forbid-undeclared-magic-properties
 */
interface ValidityState {
	/**
	 * @return bool
	 */
	public function getValueMissing(): bool;

	/**
	 * @return bool
	 */
	public function getTypeMismatch(): bool;

	/**
	 * @return bool
	 */
	public function getPatternMismatch(): bool;

	/**
	 * @return bool
	 */
	public function getTooLong(): bool;

	/**
	 * @return bool
	 */
	public function getTooShort(): bool;

	/**
	 * @return bool
	 */
	public function getRangeUnderflow(): bool;

	/**
	 * @return bool
	 */
	public function getRangeOverflow(): bool;

	/**
	 * @return bool
	 */
	public function getStepMismatch(): bool;

	/**
	 * @return bool
	 */
	public function getBadInput(): bool;

	/**
	 * @return bool
	 */
	public function getCustomError(): bool;

	/**
	 * @return bool
	 */
	public function getValid(): bool;

}
