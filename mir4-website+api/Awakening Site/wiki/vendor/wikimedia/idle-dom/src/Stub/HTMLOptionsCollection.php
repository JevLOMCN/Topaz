<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\HTMLElement;
use Wikimedia\IDLeDOM\HTMLOptGroupElement;
use Wikimedia\IDLeDOM\HTMLOptionElement;

trait HTMLOptionsCollection {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param int $index
	 * @param HTMLOptionElement|null $option
	 * @return void
	 */
	public function setItem( int $index, /* ?HTMLOptionElement */ $option ): void {
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
	 * @return void
	 */
	public function remove( int $index ): void {
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

}
