<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Element;
use Wikimedia\IDLeDOM\EventHandlerNonNull;

trait ShadowRoot {
	// use \Wikimedia\IDLeDOM\Stub\DocumentOrShadowRoot;
	// use \Wikimedia\IDLeDOM\Stub\InnerHTML;

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
	public function getMode(): /* ShadowRootMode */ string {
		throw self::_unimplemented();
	}

	/**
	 * @return Element
	 */
	public function getHost() {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnslotchange() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnslotchange( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

}
