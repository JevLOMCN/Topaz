<?php

namespace Wikimedia\RequestTimeout\Detail;

use ExcimerTimer;
use Wikimedia\RequestTimeout\RequestTimeoutException;
use Wikimedia\RequestTimeout\TimeoutException;

/**
 * It's difficult to avoid the circular reference in $this->timer due to the
 * callback closure, which means this object is not destroyed implicitly when
 * it goes out of scope. So ExcimerRequestTimeout is split into an implicitly
 * destructible part (ExcimerRequestTimeout) and a part which must be
 * explicitly destroyed (this class).
 *
 * @internal
 */
class ExcimerTimerWrapper {
	/** @var ExcimerTimer|null */
	private $timer;

	/** @var int The next critical section ID to use */
	private $nextCriticalId = 1;

	/** @var CriticalSection[] */
	private $criticalSections = [];

	/** @var array|null Data about the pending timeout, or null if no timeout is pending */
	private $pending;

	/** @var float */
	private $limit = INF;

	/**
	 * @param string $name
	 * @param float $emergencyLimit
	 * @param callable|null $emergencyCallback
	 * @return int
	 */
	public function enterCriticalSection( $name, $emergencyLimit, $emergencyCallback ) {
		$id = $this->nextCriticalId++;
		$this->criticalSections[$id] = new CriticalSection(
			$name, $emergencyLimit, $emergencyCallback );
		return $id;
	}

	/**
	 * @param int $id
	 * @throws TimeoutException
	 */
	public function exitCriticalSection( $id ) {
		if ( isset( $this->criticalSections[$id] ) ) {
			$this->criticalSections[$id]->stop();
			unset( $this->criticalSections[$id] );
		}
		if ( $this->pending ) {
			$limit = $this->pending['limit'];
			$this->pending = null;
			throw new RequestTimeoutException( $limit );
		}
	}

	/**
	 * @param float $limit The limit in seconds
	 */
	public function setWallTimeLimit( $limit ) {
		if ( $limit > 0 && $limit !== INF ) {
			$this->limit = (float)$limit;
			$this->timer = new ExcimerTimer;
			$this->timer->setInterval( $limit );
			$this->timer->setCallback( function () use ( $limit ) {
				$this->onTimeout( $limit );
			} );
			$this->timer->start();
		} else {
			$this->stop();
			$this->limit = INF;
		}
	}

	/**
	 * Callback function for the main request timeout. If any critical section
	 * is open, queue the event. Otherwise, throw the exception now.
	 *
	 * The limit is passed as a parameter, instead of using an object property,
	 * to provide greater assurance that the reported limit is the one that is
	 * actually timing out and not the result of a separate call to
	 * setWallTimeLimit().
	 *
	 * @param float $limit
	 * @throws TimeoutException
	 */
	private function onTimeout( $limit ) {
		if ( count( $this->criticalSections ) ) {
			$this->pending = [ 'limit' => $limit ];
		} else {
			throw new RequestTimeoutException( $limit );
		}
	}

	/**
	 * Get the amount of time remaining of the limit.
	 *
	 * @return float
	 */
	public function getWallTimeRemaining() {
		if ( $this->timer ) {
			return $this->timer->getTime();
		} else {
			return INF;
		}
	}

	/**
	 * Get the current wall time limit, or INF if there is no limit
	 *
	 * @return float
	 */
	public function getWallTimeLimit() {
		return $this->limit;
	}

	/**
	 * Stop and destroy the underlying timer.
	 */
	public function stop() {
		if ( $this->timer ) {
			$this->timer->stop();
		}
		$this->timer = null;
	}
}
