<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait StyleSheet {

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
		'@phan-var \Wikimedia\IDLeDOM\StyleSheet $this';
		// @var \Wikimedia\IDLeDOM\StyleSheet $this
		switch ( $name ) {
			case "type":
				return $this->getType();
			case "href":
				return $this->getHref();
			case "ownerNode":
				return $this->getOwnerNode();
			case "parentStyleSheet":
				return $this->getParentStyleSheet();
			case "title":
				return $this->getTitle();
			case "media":
				return $this->getMedia();
			case "disabled":
				return $this->getDisabled();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\StyleSheet $this';
		// @var \Wikimedia\IDLeDOM\Helper\StyleSheet $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\StyleSheet $this';
		// @var \Wikimedia\IDLeDOM\StyleSheet $this
		switch ( $name ) {
			case "type":
				return true;
			case "href":
				return $this->getHref() !== null;
			case "ownerNode":
				return $this->getOwnerNode() !== null;
			case "parentStyleSheet":
				return $this->getParentStyleSheet() !== null;
			case "title":
				return $this->getTitle() !== null;
			case "media":
				return true;
			case "disabled":
				return true;
			default:
				break;
		}
		return false;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set( string $name, $value ): void {
		'@phan-var \Wikimedia\IDLeDOM\StyleSheet $this';
		// @var \Wikimedia\IDLeDOM\StyleSheet $this
		switch ( $name ) {
			case "media":
				$this->setMedia( $value );
				return;
			case "disabled":
				$this->setDisabled( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\StyleSheet $this';
		// @var \Wikimedia\IDLeDOM\Helper\StyleSheet $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\StyleSheet $this';
		// @var \Wikimedia\IDLeDOM\StyleSheet $this
		switch ( $name ) {
			case "type":
				break;
			case "href":
				break;
			case "ownerNode":
				break;
			case "parentStyleSheet":
				break;
			case "title":
				break;
			case "media":
				break;
			case "disabled":
				break;
			default:
				return;
		}
		$trace = debug_backtrace();
		while (
			count( $trace ) > 0 &&
			$trace[0]['function'] !== "__unset"
		) {
			array_shift( $trace );
		}
		trigger_error(
			'Undefined property' .
			' via ' . ( $trace[0]['function'] ?? '' ) . '(): ' . $name .
			' in ' . ( $trace[0]['file'] ?? '' ) .
			' on line ' . ( $trace[0]['line'] ?? '' ),
			E_USER_NOTICE
		);
	}

	/**
	 * @param ?string $val
	 */
	public function setMedia( ?string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\StyleSheet $this';
		// @var \Wikimedia\IDLeDOM\StyleSheet $this
		$this->getMedia()->setMediaText( $val );
	}

}
