<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\MutationObserver;
use Wikimedia\IDLeDOM\MutationRecord;

trait MutationCallback {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param list<MutationRecord> $mutations
	 * @param MutationObserver $observer
	 * @return void
	 */
	public function invoke( array $mutations, /* MutationObserver */ $observer ): void {
		throw self::_unimplemented();
	}

}
