<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

use Wikimedia\IDLeDOM\AbortSignal;

trait AddEventListenerOptions {

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
	abstract protected function _getMissingProp( string $prop );

	/**
	 * Handle an attempt to set a non-existing property on this
	 * object.  The default implementation raises an exception
	 * but the implementor can choose a different behavior:
	 * ignore the operation (like JavaScript), dynamically create
	 * the property, etc.
	 * @param string $prop the name of the property requested
	 * @param mixed $value the value to set
	 */
	abstract protected function _setMissingProp( string $prop, $value ): void;

	// phpcs:enable

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get( string $name ) {
		'@phan-var \Wikimedia\IDLeDOM\AddEventListenerOptions $this';
		// @var \Wikimedia\IDLeDOM\AddEventListenerOptions $this
		switch ( $name ) {
			case "capture":
				return $this->getCapture();
			case "passive":
				return $this->getPassive();
			case "once":
				return $this->getOnce();
			case "signal":
				return $this->getSignal();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\AddEventListenerOptions $this';
		// @var \Wikimedia\IDLeDOM\Helper\AddEventListenerOptions $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\AddEventListenerOptions $this';
		// @var \Wikimedia\IDLeDOM\AddEventListenerOptions $this
		switch ( $name ) {
			case "capture":
				return true;
			case "passive":
				return true;
			case "once":
				return true;
			case "signal":
				return true;
			default:
				break;
		}
		return false;
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		switch ( $offset ) {
			case "capture":
			case "passive":
			case "once":
			case "signal":
				return true;
			default:
				break;
		}
		return false;
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->$offset;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ): void {
		$this->$offset = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->$offset );
	}

	/**
	 * Create a AddEventListenerOptions from an associative array.
	 *
	 * @param array|\Wikimedia\IDLeDOM\AddEventListenerOptions $a
	 * @return \Wikimedia\IDLeDOM\AddEventListenerOptions
	 */
	public static function cast( $a ) {
		if ( $a instanceof \Wikimedia\IDLeDOM\AddEventListenerOptions ) {
			return $a;
		}
		return new class( $a ) extends \Wikimedia\IDLeDOM\AddEventListenerOptions {
			use AddEventListenerOptions;

			/** @var array */
			private $a;

			/**
			 * @param array $a
			 */
			public function __construct( $a ) {
				$this->a = $a;
			}

			/**
			 * @return bool
			 */
			public function getCapture(): bool {
				return $this->a["capture"] ?? false;
			}

			/**
			 * @return bool
			 */
			public function getPassive(): bool {
				return $this->a["passive"] ?? false;
			}

			/**
			 * @return bool
			 */
			public function getOnce(): bool {
				return $this->a["once"] ?? false;
			}

			/**
			 * @return AbortSignal
			 */
			public function getSignal() {
				return $this->a["signal"];
			}

		};
	}

}
