<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

use Wikimedia\IDLeDOM\Event;

trait OnErrorEventHandlerNonNull {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * Handle an attempt to get a non-existing property on this
	 * object.  The default implementation raises an exception
	 * but the implementor can choose a different behavior:
	 * return null (like JavaScript), dynamically create the
	 * property, etc.
	 * @param string $prop the name of the property requested
	 * @return mixed
	 */
	protected function _getMissingProp( string $prop ) {
		$trace = debug_backtrace();
		while (
			count( $trace ) > 0 &&
			$trace[0]['function'] !== "__get"
		) {
			array_shift( $trace );
		}
		trigger_error(
			'Undefined property' .
			' via ' . ( $trace[0]['function'] ?? '' ) . '(): ' . $prop .
			' in ' . ( $trace[0]['file'] ?? '' ) .
			' on line ' . ( $trace[0]['line'] ?? '' ),
			E_USER_NOTICE
		);
		return null;
	}

	/**
	 * Handle an attempt to set a non-existing property on this
	 * object.  The default implementation raises an exception
	 * but the implementor can choose a different behavior:
	 * ignore the operation (like JavaScript), dynamically create
	 * the property, etc.
	 * @param string $prop the name of the property requested
	 * @param mixed $value the value to set
	 */
	protected function _setMissingProp( string $prop, $value ): void {
		$trace = debug_backtrace();
		while (
			count( $trace ) > 0 &&
			$trace[0]['function'] !== "__set"
		) {
			array_shift( $trace );
		}
		trigger_error(
			'Undefined property' .
			' via ' . ( $trace[0]['function'] ?? '' ) . '(): ' . $prop .
			' in ' . ( $trace[0]['file'] ?? '' ) .
			' on line ' . ( $trace[0]['line'] ?? '' ),
			E_USER_NOTICE
		);
	}

	// phpcs:enable

	/**
	 * Make this callback interface callable.
	 * @param mixed ...$args
	 * @return mixed|null
	 */
	public function __invoke( ...$args ) {
		'@phan-var \Wikimedia\IDLeDOM\OnErrorEventHandlerNonNull $this';
		// @var \Wikimedia\IDLeDOM\OnErrorEventHandlerNonNull $this
		return $this->invoke( $args[0], $args[1] ?? null, $args[2] ?? null, $args[3] ?? null, $args[4] ?? null );
	}

	/**
	 * Create a OnErrorEventHandlerNonNull from a callable.
	 *
	 * @param callable|\Wikimedia\IDLeDOM\OnErrorEventHandlerNonNull $f
	 * @return \Wikimedia\IDLeDOM\OnErrorEventHandlerNonNull
	 */
	public static function cast( $f ) {
		if ( $f instanceof \Wikimedia\IDLeDOM\OnErrorEventHandlerNonNull ) {
			return $f;
		}
		return new class( $f ) implements \Wikimedia\IDLeDOM\OnErrorEventHandlerNonNull {
			use OnErrorEventHandlerNonNull;

			/** @var callable */
			private $f;

			/**
			 * @param callable $f
			 */
			public function __construct( $f ) {
				$this->f = $f;
			}

			/**
			 * @param Event|string $event
			 * @param ?string $source
			 * @param ?int $lineno
			 * @param ?int $colno
			 * @param mixed|null $error
			 * @return mixed|null
			 */
			public function invoke( /* mixed */ $event, ?string $source = null, ?int $lineno = null, ?int $colno = null, /* any */ $error = null ) {
				$f = $this->f;
				return $f( $event, $source, $lineno, $colno, $error );
			}
		};
	}
}
