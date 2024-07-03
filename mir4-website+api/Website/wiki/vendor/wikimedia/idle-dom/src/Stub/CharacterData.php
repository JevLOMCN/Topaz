<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait CharacterData {
	// use \Wikimedia\IDLeDOM\Stub\ChildNode;
	// use \Wikimedia\IDLeDOM\Stub\NonDocumentTypeChildNode;

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
	public function getData(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $val
	 */
	public function setData( ?string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $offset
	 * @param int $count
	 * @return string
	 */
	public function substringData( int $offset, int $count ): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $data
	 * @return void
	 */
	public function appendData( string $data ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param int $offset
	 * @param string $data
	 * @return void
	 */
	public function insertData( int $offset, string $data ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param int $offset
	 * @param int $count
	 * @return void
	 */
	public function deleteData( int $offset, int $count ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param int $offset
	 * @param int $count
	 * @param string $data
	 * @return void
	 */
	public function replaceData( int $offset, int $count, string $data ): void {
		throw self::_unimplemented();
	}

}
