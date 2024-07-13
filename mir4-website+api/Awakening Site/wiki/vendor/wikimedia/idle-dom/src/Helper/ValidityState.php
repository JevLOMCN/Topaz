<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait ValidityState {

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
		'@phan-var \Wikimedia\IDLeDOM\ValidityState $this';
		// @var \Wikimedia\IDLeDOM\ValidityState $this
		switch ( $name ) {
			case "valueMissing":
				return $this->getValueMissing();
			case "typeMismatch":
				return $this->getTypeMismatch();
			case "patternMismatch":
				return $this->getPatternMismatch();
			case "tooLong":
				return $this->getTooLong();
			case "tooShort":
				return $this->getTooShort();
			case "rangeUnderflow":
				return $this->getRangeUnderflow();
			case "rangeOverflow":
				return $this->getRangeOverflow();
			case "stepMismatch":
				return $this->getStepMismatch();
			case "badInput":
				return $this->getBadInput();
			case "customError":
				return $this->getCustomError();
			case "valid":
				return $this->getValid();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\ValidityState $this';
		// @var \Wikimedia\IDLeDOM\Helper\ValidityState $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\ValidityState $this';
		// @var \Wikimedia\IDLeDOM\ValidityState $this
		switch ( $name ) {
			case "valueMissing":
				return true;
			case "typeMismatch":
				return true;
			case "patternMismatch":
				return true;
			case "tooLong":
				return true;
			case "tooShort":
				return true;
			case "rangeUnderflow":
				return true;
			case "rangeOverflow":
				return true;
			case "stepMismatch":
				return true;
			case "badInput":
				return true;
			case "customError":
				return true;
			case "valid":
				return true;
			default:
				break;
		}
		return false;
	}

}
