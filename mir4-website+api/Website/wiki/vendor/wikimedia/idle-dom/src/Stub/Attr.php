<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Element;

trait Attr {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return ?string
	 */
	public function getNamespaceURI(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return ?string
	 */
	public function getPrefix(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getLocalName(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getName(): string {
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
	 * @return Element|null
	 */
	public function getOwnerElement() {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getSpecified(): bool {
		throw self::_unimplemented();
	}

}
