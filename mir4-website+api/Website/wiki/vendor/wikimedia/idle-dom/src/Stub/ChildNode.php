<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Node;

trait ChildNode {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param Node|string ...$nodes
	 * @return void
	 */
	public function before( /* mixed */ ...$nodes ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node|string ...$nodes
	 * @return void
	 */
	public function after( /* mixed */ ...$nodes ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node|string ...$nodes
	 * @return void
	 */
	public function replaceWith( /* mixed */ ...$nodes ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function remove(): void {
		throw self::_unimplemented();
	}

}
