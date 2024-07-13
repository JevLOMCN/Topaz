<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait TextTrack {

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
		'@phan-var \Wikimedia\IDLeDOM\TextTrack $this';
		// @var \Wikimedia\IDLeDOM\TextTrack $this
		switch ( $name ) {
			case "kind":
				return $this->getKind();
			case "label":
				return $this->getLabel();
			case "language":
				return $this->getLanguage();
			case "id":
				return $this->getId();
			case "inBandMetadataTrackDispatchType":
				return $this->getInBandMetadataTrackDispatchType();
			case "cues":
				return $this->getCues();
			case "activeCues":
				return $this->getActiveCues();
			case "oncuechange":
				return $this->getOncuechange();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\TextTrack $this';
		// @var \Wikimedia\IDLeDOM\Helper\TextTrack $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\TextTrack $this';
		// @var \Wikimedia\IDLeDOM\TextTrack $this
		switch ( $name ) {
			case "kind":
				return true;
			case "label":
				return true;
			case "language":
				return true;
			case "id":
				return true;
			case "inBandMetadataTrackDispatchType":
				return true;
			case "cues":
				return $this->getCues() !== null;
			case "activeCues":
				return $this->getActiveCues() !== null;
			case "oncuechange":
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
		'@phan-var \Wikimedia\IDLeDOM\TextTrack $this';
		// @var \Wikimedia\IDLeDOM\TextTrack $this
		switch ( $name ) {
			case "oncuechange":
				$this->setOncuechange( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\TextTrack $this';
		// @var \Wikimedia\IDLeDOM\Helper\TextTrack $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\TextTrack $this';
		// @var \Wikimedia\IDLeDOM\TextTrack $this
		switch ( $name ) {
			case "kind":
				break;
			case "label":
				break;
			case "language":
				break;
			case "id":
				break;
			case "inBandMetadataTrackDispatchType":
				break;
			case "cues":
				break;
			case "activeCues":
				break;
			case "oncuechange":
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
