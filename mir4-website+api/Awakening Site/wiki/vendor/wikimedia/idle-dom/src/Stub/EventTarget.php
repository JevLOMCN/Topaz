<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\AddEventListenerOptions;
use Wikimedia\IDLeDOM\Event;
use Wikimedia\IDLeDOM\EventListener;
use Wikimedia\IDLeDOM\EventListenerOptions;

trait EventTarget {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param string $type
	 * @param EventListener|callable|null $callback
	 * @param AddEventListenerOptions|associative-array|bool|null $options
	 * @return void
	 */
	public function addEventListener( string $type, /* ?mixed */ $callback, /* ?mixed */ $options = null ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $type
	 * @param EventListener|callable|null $callback
	 * @param EventListenerOptions|associative-array|bool|null $options
	 * @return void
	 */
	public function removeEventListener( string $type, /* ?mixed */ $callback, /* ?mixed */ $options = null ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public function dispatchEvent( /* Event */ $event ): bool {
		throw self::_unimplemented();
	}

}
