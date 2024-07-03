<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\HTMLElement;
use Wikimedia\IDLeDOM\HTMLFormControlsCollection;

trait HTMLFormElement {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return string
	 */
	public function getAction(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setAction( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLFormControlsCollection
	 */
	public function getElements() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function submit(): void {
		throw self::_unimplemented();
	}

	/**
	 * @param HTMLElement|null $submitter
	 * @return void
	 */
	public function requestSubmit( /* ?HTMLElement */ $submitter = null ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function reset(): void {
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

}
