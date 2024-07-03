<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Attr;

trait NamedNodeMap {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return Attr|null
	 */
	public function item( int $index ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @return Attr|null
	 */
	public function getNamedItem( string $qualifiedName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return Attr|null
	 */
	public function getNamedItemNS( ?string $namespace, string $localName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setNamedItem( /* Attr */ $attr ) {
		throw self::_unimplemented();
	}

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setNamedItemNS( /* Attr */ $attr ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $qualifiedName
	 * @return Attr
	 */
	public function removeNamedItem( string $qualifiedName ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return Attr
	 */
	public function removeNamedItemNS( ?string $namespace, string $localName ) {
		throw self::_unimplemented();
	}

}
