<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait URL {

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
		'@phan-var \Wikimedia\IDLeDOM\URL $this';
		// @var \Wikimedia\IDLeDOM\URL $this
		switch ( $name ) {
			case "href":
				return $this->getHref();
			case "origin":
				return $this->getOrigin();
			case "protocol":
				return $this->getProtocol();
			case "username":
				return $this->getUsername();
			case "password":
				return $this->getPassword();
			case "host":
				return $this->getHost();
			case "hostname":
				return $this->getHostname();
			case "port":
				return $this->getPort();
			case "pathname":
				return $this->getPathname();
			case "search":
				return $this->getSearch();
			case "searchParams":
				return $this->getSearchParams();
			case "hash":
				return $this->getHash();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\URL $this';
		// @var \Wikimedia\IDLeDOM\Helper\URL $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\URL $this';
		// @var \Wikimedia\IDLeDOM\URL $this
		switch ( $name ) {
			case "href":
				return true;
			case "origin":
				return true;
			case "protocol":
				return true;
			case "username":
				return true;
			case "password":
				return true;
			case "host":
				return true;
			case "hostname":
				return true;
			case "port":
				return true;
			case "pathname":
				return true;
			case "search":
				return true;
			case "searchParams":
				return true;
			case "hash":
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
		'@phan-var \Wikimedia\IDLeDOM\URL $this';
		// @var \Wikimedia\IDLeDOM\URL $this
		switch ( $name ) {
			case "href":
				$this->setHref( $value );
				return;
			case "protocol":
				$this->setProtocol( $value );
				return;
			case "username":
				$this->setUsername( $value );
				return;
			case "password":
				$this->setPassword( $value );
				return;
			case "host":
				$this->setHost( $value );
				return;
			case "hostname":
				$this->setHostname( $value );
				return;
			case "port":
				$this->setPort( $value );
				return;
			case "pathname":
				$this->setPathname( $value );
				return;
			case "search":
				$this->setSearch( $value );
				return;
			case "hash":
				$this->setHash( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\URL $this';
		// @var \Wikimedia\IDLeDOM\Helper\URL $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\URL $this';
		// @var \Wikimedia\IDLeDOM\URL $this
		switch ( $name ) {
			case "href":
				break;
			case "origin":
				break;
			case "protocol":
				break;
			case "username":
				break;
			case "password":
				break;
			case "host":
				break;
			case "hostname":
				break;
			case "port":
				break;
			case "pathname":
				break;
			case "search":
				break;
			case "searchParams":
				break;
			case "hash":
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
	 * @return string
	 */
	public function __toString(): string {
		'@phan-var \Wikimedia\IDLeDOM\URL $this';
		// @var \Wikimedia\IDLeDOM\URL $this
		return $this->getHref();
	}

}
