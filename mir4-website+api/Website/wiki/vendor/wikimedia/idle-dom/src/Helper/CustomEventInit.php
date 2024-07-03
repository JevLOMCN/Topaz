<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait CustomEventInit {

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
		'@phan-var \Wikimedia\IDLeDOM\CustomEventInit $this';
		// @var \Wikimedia\IDLeDOM\CustomEventInit $this
		switch ( $name ) {
			case "bubbles":
				return $this->getBubbles();
			case "cancelable":
				return $this->getCancelable();
			case "composed":
				return $this->getComposed();
			case "detail":
				return $this->getDetail();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\CustomEventInit $this';
		// @var \Wikimedia\IDLeDOM\Helper\CustomEventInit $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\CustomEventInit $this';
		// @var \Wikimedia\IDLeDOM\CustomEventInit $this
		switch ( $name ) {
			case "bubbles":
				return true;
			case "cancelable":
				return true;
			case "composed":
				return true;
			case "detail":
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
			case "bubbles":
			case "cancelable":
			case "composed":
			case "detail":
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
	 * Create a CustomEventInit from an associative array.
	 *
	 * @param array|\Wikimedia\IDLeDOM\CustomEventInit $a
	 * @return \Wikimedia\IDLeDOM\CustomEventInit
	 */
	public static function cast( $a ) {
		if ( $a instanceof \Wikimedia\IDLeDOM\CustomEventInit ) {
			return $a;
		}
		return new class( $a ) extends \Wikimedia\IDLeDOM\CustomEventInit {
			use CustomEventInit;

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
			public function getBubbles(): bool {
				return $this->a["bubbles"] ?? false;
			}

			/**
			 * @return bool
			 */
			public function getCancelable(): bool {
				return $this->a["cancelable"] ?? false;
			}

			/**
			 * @return bool
			 */
			public function getComposed(): bool {
				return $this->a["composed"] ?? false;
			}

			/**
			 * @return mixed|null
			 */
			public function getDetail() {
				return $this->a["detail"] ?? null;
			}

		};
	}

}
