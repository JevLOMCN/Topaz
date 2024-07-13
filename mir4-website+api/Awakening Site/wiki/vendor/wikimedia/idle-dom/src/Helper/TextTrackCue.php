<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Helper;

trait TextTrackCue {

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
		'@phan-var \Wikimedia\IDLeDOM\TextTrackCue $this';
		// @var \Wikimedia\IDLeDOM\TextTrackCue $this
		switch ( $name ) {
			case "track":
				return $this->getTrack();
			case "id":
				return $this->getId();
			case "startTime":
				return $this->getStartTime();
			case "endTime":
				return $this->getEndTime();
			case "pauseOnExit":
				return $this->getPauseOnExit();
			case "onenter":
				return $this->getOnenter();
			case "onexit":
				return $this->getOnexit();
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\TextTrackCue $this';
		// @var \Wikimedia\IDLeDOM\Helper\TextTrackCue $this
		return $this->_getMissingProp( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		'@phan-var \Wikimedia\IDLeDOM\TextTrackCue $this';
		// @var \Wikimedia\IDLeDOM\TextTrackCue $this
		switch ( $name ) {
			case "track":
				return $this->getTrack() !== null;
			case "id":
				return true;
			case "startTime":
				return true;
			case "endTime":
				return true;
			case "pauseOnExit":
				return true;
			case "onenter":
				return true;
			case "onexit":
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
		'@phan-var \Wikimedia\IDLeDOM\TextTrackCue $this';
		// @var \Wikimedia\IDLeDOM\TextTrackCue $this
		switch ( $name ) {
			case "id":
				$this->setId( $value );
				return;
			case "startTime":
				$this->setStartTime( $value );
				return;
			case "endTime":
				$this->setEndTime( $value );
				return;
			case "pauseOnExit":
				$this->setPauseOnExit( $value );
				return;
			case "onenter":
				$this->setOnenter( $value );
				return;
			case "onexit":
				$this->setOnexit( $value );
				return;
			default:
				break;
		}
		'@phan-var \Wikimedia\IDLeDOM\Helper\TextTrackCue $this';
		// @var \Wikimedia\IDLeDOM\Helper\TextTrackCue $this
		$this->_setMissingProp( $name, $value );
	}

	/**
	 * @param string $name
	 */
	public function __unset( string $name ): void {
		'@phan-var \Wikimedia\IDLeDOM\TextTrackCue $this';
		// @var \Wikimedia\IDLeDOM\TextTrackCue $this
		switch ( $name ) {
			case "track":
				break;
			case "id":
				break;
			case "startTime":
				break;
			case "endTime":
				break;
			case "pauseOnExit":
				break;
			case "onenter":
				break;
			case "onexit":
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
