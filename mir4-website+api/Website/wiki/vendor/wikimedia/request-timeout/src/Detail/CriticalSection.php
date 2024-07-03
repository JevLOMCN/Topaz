<?php

namespace Wikimedia\RequestTimeout\Detail;

use ExcimerTimer;
use Wikimedia\RequestTimeout\EmergencyTimeoutException;

/**
 * Internal class for tracking critical sections
 *
 * @internal
 */
class CriticalSection {
	/** @var string */
	private $name;
	/** @var ExcimerTimer|null */
	private $emergencyTimer;

	/**
	 * @see RequestTimeout::createCriticalSectionProvider
	 *
	 * @param string $name
	 * @param float $emergencyLimit
	 * @param callable|null $emergencyCallback
	 */
	public function __construct( $name, $emergencyLimit, $emergencyCallback ) {
		$this->name = $name;
		if ( !$emergencyCallback ) {
			/** @return never */
			$emergencyCallback = static function () use ( $name, $emergencyLimit ) {
				throw new EmergencyTimeoutException( $name, $emergencyLimit );
			};
		}

		if ( $emergencyLimit > 0 && $emergencyLimit !== INF ) {
			$this->emergencyTimer = new ExcimerTimer;
			$this->emergencyTimer->setInterval( $emergencyLimit );
			$this->emergencyTimer->setCallback( $emergencyCallback );
			$this->emergencyTimer->start();
		}
	}

	/**
	 * Stop the emergency timer, if it was started
	 */
	public function stop() {
		if ( $this->emergencyTimer ) {
			$this->emergencyTimer->stop();
			$this->emergencyTimer = null;
		}
	}
}
