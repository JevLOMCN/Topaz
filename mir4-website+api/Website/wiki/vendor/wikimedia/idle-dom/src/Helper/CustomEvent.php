<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait CustomEvent {

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
		'@phan-var \Wikimedia\IDLeDOM\CustomEvent $this';
		// @var \Wikimedia\IDLeDOM\CustomEvent $this
		switch ( $name ) {
			case "type":
				return $this->getType();
			case "target":
				return $this->getTarget();
			case "srcElement":
				return $this->getSrcElement();
			case "currentTarget":
				return $this->getCurrentTarget();
			case "eventPhase":
				return $this->getEventPhase();
			case "cancelBubble":
				return $this->getCancelBubble();
			case "bubbles":
				return $this->getBubbles();
			case "cancelable":
				return $this->getCancelable();
			case "returnValue":
				return $this->getReturnValue();
			case "defaultPrevented":
				return $this->getDefaultPrevented();
			case "composed":
				return $this->getComposed();
			case "isTrusted":
				return $this->getIsTrusted();
			case "timeStamp":
				return $this->getTimeStamp();
			case "detail":
				return $this->getDetail();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\CustomEvent $this';
		// @var \Wikimedia\IDLeDOM\Helper\CustomEvent $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\CustomEvent $this';
		// @var \Wikimedia\IDLeDOM\CustomEvent $this
		switch ( $name ) {
			case "type":
				return true;
			case "target":
				return $this->getTarget() !== null;
			case "srcElement":
				return $this->getSrcElement() !== null;
			case "currentTarget":
				return $this->getCurrentTarget() !== null;
			case "eventPhase":
				return true;
			case "cancelBubble":
				return true;
			case "bubbles":
				return true;
			case "cancelable":
				return true;
			case "returnValue":
				return true;
			case "defaultPrevented":
				return true;
			case "composed":
				return true;
			case "isTrusted":
				return true;
			case "timeStamp":
				return true;
			case "detail":
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
		'@phan-var \Wikimedia\IDLeDOM\CustomEvent $this';
		// @var \Wikimedia\IDLeDOM\CustomEvent $this
		switch ( $name ) {
			case "cancelBubble":
				$this->setCancelBubble( $value );
				return;
			case "returnValue":
				$this->setReturnValue( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\CustomEvent $this';
		// @var \Wikimedia\IDLeDOM\Helper\CustomEvent $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\CustomEvent $this';
		// @var \Wikimedia\IDLeDOM\CustomEvent $this
		switch ( $name ) {
			case "type":
				break;
			case "target":
				break;
			case "srcElement":
				break;
			case "currentTarget":
				break;
			case "eventPhase":
				break;
			case "cancelBubble":
				break;
			case "bubbles":
				break;
			case "cancelable":
				break;
			case "returnValue":
				break;
			case "defaultPrevented":
				break;
			case "composed":
				break;
			case "isTrusted":
				break;
			case "timeStamp":
				break;
			case "detail":
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
