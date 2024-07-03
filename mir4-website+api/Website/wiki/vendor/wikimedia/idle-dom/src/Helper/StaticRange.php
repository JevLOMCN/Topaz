<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait StaticRange {

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
		'@phan-var \Wikimedia\IDLeDOM\StaticRange $this';
		// @var \Wikimedia\IDLeDOM\StaticRange $this
		switch ( $name ) {
			case "startContainer":
				return $this->getStartContainer();
			case "startOffset":
				return $this->getStartOffset();
			case "endContainer":
				return $this->getEndContainer();
			case "endOffset":
				return $this->getEndOffset();
			case "collapsed":
				return $this->getCollapsed();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\StaticRange $this';
		// @var \Wikimedia\IDLeDOM\Helper\StaticRange $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\StaticRange $this';
		// @var \Wikimedia\IDLeDOM\StaticRange $this
		switch ( $name ) {
			case "startContainer":
				return true;
			case "startOffset":
				return true;
			case "endContainer":
				return true;
			case "endOffset":
				return true;
			case "collapsed":
				return true;
			default:
				break;
		}
		return false;
	}

}
