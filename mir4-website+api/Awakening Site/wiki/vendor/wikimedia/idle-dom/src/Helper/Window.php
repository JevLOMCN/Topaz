<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait Window {

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
		'@phan-var \Wikimedia\IDLeDOM\Window $this';
		// @var \Wikimedia\IDLeDOM\Window $this
		switch ( $name ) {
			case "onload":
				return $this->getOnload();
			case "document":
				return $this->getDocument();
			case "name":
				return $this->getName();
			case "location":
				return $this->getLocation();
			case "status":
				return $this->getStatus();
			case "closed":
				return $this->getClosed();
			case "length":
				return $this->getLength();
			case "opener":
				return $this->getOpener();
			case "frameElement":
				return $this->getFrameElement();
			case "navigator":
				return $this->getNavigator();
			case "originAgentCluster":
				return $this->getOriginAgentCluster();
			case "event":
				return $this->getEvent();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\Window $this';
		// @var \Wikimedia\IDLeDOM\Helper\Window $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\Window $this';
		// @var \Wikimedia\IDLeDOM\Window $this
		switch ( $name ) {
			case "onload":
				return true;
			case "document":
				return true;
			case "name":
				return true;
			case "location":
				return true;
			case "status":
				return true;
			case "closed":
				return true;
			case "length":
				return true;
			case "opener":
				return true;
			case "frameElement":
				return $this->getFrameElement() !== null;
			case "navigator":
				return true;
			case "originAgentCluster":
				return true;
			case "event":
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
		'@phan-var \Wikimedia\IDLeDOM\Window $this';
		// @var \Wikimedia\IDLeDOM\Window $this
		switch ( $name ) {
			case "onload":
				$this->setOnload( $value );
				return;
			case "name":
				$this->setName( $value );
				return;
			case "location":
				$this->setLocation( $value );
				return;
			case "status":
				$this->setStatus( $value );
				return;
			case "opener":
				$this->setOpener( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\Window $this';
		// @var \Wikimedia\IDLeDOM\Helper\Window $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\Window $this';
		// @var \Wikimedia\IDLeDOM\Window $this
		switch ( $name ) {
			case "onload":
				break;
			case "document":
				break;
			case "name":
				break;
			case "location":
				break;
			case "status":
				break;
			case "closed":
				break;
			case "length":
				break;
			case "opener":
				break;
			case "frameElement":
				break;
			case "navigator":
				break;
			case "originAgentCluster":
				break;
			case "event":
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
	 * @param string $val
	 */
	public function setLocation( string $val ): void {
		'@phan-var \Wikimedia\IDLeDOM\Window $this';
		// @var \Wikimedia\IDLeDOM\Window $this
		$this->getLocation()->setHref( $val );
	}

}
