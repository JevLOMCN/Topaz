<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\AbortSignal;

trait AddEventListenerOptions {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return bool
	 */
	public function getPassive(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getOnce(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return AbortSignal
	 */
	public function getSignal() {
		throw self::_unimplemented();
	}

}
