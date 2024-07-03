<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait Navigator {

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
		'@phan-var \Wikimedia\IDLeDOM\Navigator $this';
		// @var \Wikimedia\IDLeDOM\Navigator $this
		switch ( $name ) {
			case "cookieEnabled":
				return $this->getCookieEnabled();
			case "appCodeName":
				return $this->getAppCodeName();
			case "appName":
				return $this->getAppName();
			case "appVersion":
				return $this->getAppVersion();
			case "platform":
				return $this->getPlatform();
			case "product":
				return $this->getProduct();
			case "productSub":
				return $this->getProductSub();
			case "userAgent":
				return $this->getUserAgent();
			case "vendor":
				return $this->getVendor();
			case "vendorSub":
				return $this->getVendorSub();
			case "oscpu":
				return $this->getOscpu();
			case "language":
				return $this->getLanguage();
			case "onLine":
				return $this->getOnLine();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\Navigator $this';
		// @var \Wikimedia\IDLeDOM\Helper\Navigator $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\Navigator $this';
		// @var \Wikimedia\IDLeDOM\Navigator $this
		switch ( $name ) {
			case "cookieEnabled":
				return true;
			case "appCodeName":
				return true;
			case "appName":
				return true;
			case "appVersion":
				return true;
			case "platform":
				return true;
			case "product":
				return true;
			case "productSub":
				return true;
			case "userAgent":
				return true;
			case "vendor":
				return true;
			case "vendorSub":
				return true;
			case "oscpu":
				return true;
			case "language":
				return true;
			case "onLine":
				return true;
			default:
				break;
		}
		return false;
	}

}
