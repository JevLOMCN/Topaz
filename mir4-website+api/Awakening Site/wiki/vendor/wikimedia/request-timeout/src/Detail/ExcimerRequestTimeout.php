<?php

namespace Wikimedia\RequestTimeout\Detail;

use Wikimedia\RequestTimeout\RequestTimeout;

/**
 * Excimer implementation of request timeouts.
 *
 * See the doc comment on ExcimerTimerWrapper for an explanation of the split.
 */
class ExcimerRequestTimeout extends RequestTimeout {
	/** @var ExcimerTimerWrapper */
	private $timerWrapper;

	public function __construct() {
		$this->timerWrapper = new ExcimerTimerWrapper;
	}

	/**
	 * ExcimerTimerWrapper has a circular reference in it and so the timer must
	 * be explicitly destroyed.
	 */
	public function __destruct() {
		$this->timerWrapper->stop();
	}

	public function enterCriticalSection( $name, $emergencyLimit, $emergencyCallback ) {
		return $this->timerWrapper->enterCriticalSection(
			$name, $emergencyLimit, $emergencyCallback );
	}

	public function exitCriticalSection( $id ) {
		$this->timerWrapper->exitCriticalSection( $id );
	}

	public function setWallTimeLimit( $limit ) {
		$this->timerWrapper->setWallTimeLimit( $limit );
	}

	public function getWallTimeRemaining() {
		return $this->timerWrapper->getWallTimeRemaining();
	}

	public function getWallTimeLimit() {
		return $this->timerWrapper->getWallTimeLimit();
	}
}
