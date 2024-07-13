<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait CustomEvent {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return mixed|null
	 */
	public function getDetail() {
		throw self::_unimplemented();
	}

	/**
	 * @param string $type
	 * @param bool $bubbles
	 * @param bool $cancelable
	 * @param mixed|null $detail
	 * @return void
	 */
	public function initCustomEvent( string $type, bool $bubbles = false, bool $cancelable = false, /* any */ $detail = null ): void {
		throw self::_unimplemented();
	}

}
