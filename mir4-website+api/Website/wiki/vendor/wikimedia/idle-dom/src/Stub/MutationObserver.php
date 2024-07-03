<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\MutationObserverInit;
use Wikimedia\IDLeDOM\MutationRecord;
use Wikimedia\IDLeDOM\Node;

trait MutationObserver {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param Node $target
	 * @param MutationObserverInit|associative-array|null $options
	 * @return void
	 */
	public function observe( /* Node */ $target, /* ?mixed */ $options = null ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function disconnect(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return list<MutationRecord>
	 */
	public function takeRecords(): array {
		throw self::_unimplemented();
	}

}
