<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait CSSGroupingRule {

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
		'@phan-var \Wikimedia\IDLeDOM\CSSGroupingRule $this';
		// @var \Wikimedia\IDLeDOM\CSSGroupingRule $this
		switch ( $name ) {
			case "cssText":
				return $this->getCssText();
			case "parentRule":
				return $this->getParentRule();
			case "parentStyleSheet":
				return $this->getParentStyleSheet();
			case "type":
				return $this->getType();
			case "cssRules":
				return $this->getCssRules();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\CSSGroupingRule $this';
		// @var \Wikimedia\IDLeDOM\Helper\CSSGroupingRule $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\CSSGroupingRule $this';
		// @var \Wikimedia\IDLeDOM\CSSGroupingRule $this
		switch ( $name ) {
			case "cssText":
				return true;
			case "parentRule":
				return $this->getParentRule() !== null;
			case "parentStyleSheet":
				return $this->getParentStyleSheet() !== null;
			case "type":
				return true;
			case "cssRules":
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
		'@phan-var \Wikimedia\IDLeDOM\CSSGroupingRule $this';
		// @var \Wikimedia\IDLeDOM\CSSGroupingRule $this
		switch ( $name ) {
			case "cssText":
				$this->setCssText( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\CSSGroupingRule $this';
		// @var \Wikimedia\IDLeDOM\Helper\CSSGroupingRule $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\CSSGroupingRule $this';
		// @var \Wikimedia\IDLeDOM\CSSGroupingRule $this
		switch ( $name ) {
			case "cssText":
				break;
			case "parentRule":
				break;
			case "parentStyleSheet":
				break;
			case "type":
				break;
			case "cssRules":
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

}
