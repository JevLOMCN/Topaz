<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Event;

trait OnErrorEventHandlerNonNull {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param Event|string $event
	 * @param ?string $source
	 * @param ?int $lineno
	 * @param ?int $colno
	 * @param mixed|null $error
	 * @return mixed|null
	 */
	public function invoke( /* mixed */ $event, ?string $source = null, ?int $lineno = null, ?int $colno = null, /* any */ $error = null ) {
		throw self::_unimplemented();
	}

}
