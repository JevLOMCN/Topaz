<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait MutationObserverInit {

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
	 * @param string $name
	 * @return mixed
	 */
	public function __get( string $name ) {
		'@phan-var \Wikimedia\IDLeDOM\MutationObserverInit $this';
		// @var \Wikimedia\IDLeDOM\MutationObserverInit $this
		switch ( $name ) {
			case "childList":
				return $this->getChildList();
			case "attributes":
				return $this->getAttributes();
			case "characterData":
				return $this->getCharacterData();
			case "subtree":
				return $this->getSubtree();
			case "attributeOldValue":
				return $this->getAttributeOldValue();
			case "characterDataOldValue":
				return $this->getCharacterDataOldValue();
			case "attributeFilter":
				return $this->getAttributeFilter();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\MutationObserverInit $this';
		// @var \Wikimedia\IDLeDOM\Helper\MutationObserverInit $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\MutationObserverInit $this';
		// @var \Wikimedia\IDLeDOM\MutationObserverInit $this
		switch ( $name ) {
			case "childList":
				return true;
			case "attributes":
				return true;
			case "characterData":
				return true;
			case "subtree":
				return true;
			case "attributeOldValue":
				return true;
			case "characterDataOldValue":
				return true;
			case "attributeFilter":
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
			case "childList":
			case "attributes":
			case "characterData":
			case "subtree":
			case "attributeOldValue":
			case "characterDataOldValue":
			case "attributeFilter":
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
	 * Create a MutationObserverInit from an associative array.
	 *
	 * @param array|\Wikimedia\IDLeDOM\MutationObserverInit $a
	 * @return \Wikimedia\IDLeDOM\MutationObserverInit
	 */
	public static function cast( $a ) {
		if ( $a instanceof \Wikimedia\IDLeDOM\MutationObserverInit ) {
			return $a;
		}
		return new class( $a ) extends \Wikimedia\IDLeDOM\MutationObserverInit {
			use MutationObserverInit;

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
			public function getChildList(): bool {
				return $this->a["childList"] ?? false;
			}

			/**
			 * @return bool
			 */
			public function getAttributes(): bool {
				return $this->a["attributes"];
			}

			/**
			 * @return bool
			 */
			public function getCharacterData(): bool {
				return $this->a["characterData"];
			}

			/**
			 * @return bool
			 */
			public function getSubtree(): bool {
				return $this->a["subtree"] ?? false;
			}

			/**
			 * @return bool
			 */
			public function getAttributeOldValue(): bool {
				return $this->a["attributeOldValue"];
			}

			/**
			 * @return bool
			 */
			public function getCharacterDataOldValue(): bool {
				return $this->a["characterDataOldValue"];
			}

			/**
			 * @return list<string>
			 */
			public function getAttributeFilter(): array {
				return $this->a["attributeFilter"];
			}

		};
	}

}
