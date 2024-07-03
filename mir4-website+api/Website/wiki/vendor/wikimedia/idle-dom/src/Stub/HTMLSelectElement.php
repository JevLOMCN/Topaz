<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Element;
use Wikimedia\IDLeDOM\HTMLCollection;
use Wikimedia\IDLeDOM\HTMLElement;
use Wikimedia\IDLeDOM\HTMLFormElement;
use Wikimedia\IDLeDOM\HTMLOptGroupElement;
use Wikimedia\IDLeDOM\HTMLOptionElement;
use Wikimedia\IDLeDOM\HTMLOptionsCollection;
use Wikimedia\IDLeDOM\NodeList;
use Wikimedia\IDLeDOM\ValidityState;

trait HTMLSelectElement {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return HTMLFormElement|null
	 */
	public function getForm() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getSize(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $val
	 */
	public function setSize( int $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLOptionsCollection
	 */
	public function getOptions() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $val
	 */
	public function setLength( int $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return Element|null
	 */
	public function item( int $index ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @return HTMLOptionElement|null
	 */
	public function namedItem( string $name ) {
		throw self::_unimplemented();
	}

	/**
	 * @param HTMLOptionElement|HTMLOptGroupElement $element
	 * @param HTMLElement|int|null $before
	 * @return void
	 */
	public function add( /* mixed */ $element, /* ?mixed */ $before = null ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @param HTMLOptionElement|null $option
	 * @return void
	 */
	public function setItem( int $index, /* ?HTMLOptionElement */ $option ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getSelectedOptions() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getSelectedIndex(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $val
	 */
	public function setSelectedIndex( int $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setValue( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getWillValidate(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return ValidityState
	 */
	public function getValidity() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getValidationMessage(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function checkValidity(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function reportValidity(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $error
	 * @return void
	 */
	public function setCustomValidity( string $error ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return NodeList
	 */
	public function getLabels() {
		throw self::_unimplemented();
	}

}
