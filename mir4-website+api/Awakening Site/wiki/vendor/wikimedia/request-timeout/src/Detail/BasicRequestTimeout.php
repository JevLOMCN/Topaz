<?php

namespace Wikimedia\RequestTimeout\Detail;

use Wikimedia\RequestTimeout\RequestTimeout;

/**
 * A fallback implementation used when Excimer is not available
 */
class BasicRequestTimeout extends RequestTimeout {
	/** @var float */
	private $limit;

	/** @var float */
	private $startTime;

	/** @var int The next critical section ID to use */
	private $nextCriticalId = 1;

	public function enterCriticalSection( $name, $emergencyLimit, $emergencyCallback ) {
		return $this->nextCriticalId++;
	}

	public function exitCriticalSection( $id ) {
	}

	/**
	 * Get the elapsed CPU time. This can be used to estimate when the time
	 * limit will expire.
	 *
	 * @return float
	 */
	private function getCpuTime() {
		$r = getrusage();
		return $r['ru_utime.tv_sec'] + $r['ru_stime.tv_sec']
			+ ( $r['ru_utime.tv_usec'] + $r['ru_stime.tv_usec'] ) * 1e-6;
	}

	public function setWallTimeLimit( $limit ) {
		if ( $limit === INF ) {
			$limit = 0;
		}
		$this->limit = ceil( $limit );
		$this->startTime = $this->getCpuTime();
		set_time_limit( $this->limit );
	}

	public function getWallTimeRemaining() {
		if ( $this->startTime !== null && $this->limit > 0 ) {
			return $this->limit - $this->getCpuTime() + $this->startTime;
		} else {
			return INF;
		}
	}

	public function getWallTimeLimit() {
		if ( $this->limit > 0 ) {
			return $this->limit;
		} else {
			return INF;
		}
	}
}
