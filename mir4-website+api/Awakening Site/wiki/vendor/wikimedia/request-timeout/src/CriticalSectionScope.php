<?php

namespace Wikimedia\RequestTimeout;

/**
 * Class for automatically ending a critical section when a variable goes out
 * of scope.
 */
class CriticalSectionScope {
	/** @var int */
	private $id;

	/** @var callable */
	private $exitCallback;

	/** @var callable|null */
	private $implicitExitCallback;

	/** @var bool */
	private $hasExited = false;

	/**
	 * @internal
	 * @param int $id
	 * @param callable $exitCallback
	 * @param callable|null $implicitExitCallback
	 */
	public function __construct( $id, $exitCallback, $implicitExitCallback ) {
		$this->id = $id;
		$this->exitCallback = $exitCallback;
		$this->implicitExitCallback = $implicitExitCallback;
	}

	/**
	 * If the section has not already been exited, exit it and call the
	 * destruct callback.
	 *
	 * @throws TimeoutException
	 */
	public function __destruct() {
		if ( !$this->hasExited ) {
			$this->exit();
			if ( $this->implicitExitCallback ) {
				( $this->implicitExitCallback )( $this->id );
			}
		}
	}

	/**
	 * Exit the critical section
	 *
	 * @throws TimeoutException
	 */
	public function exit() {
		if ( !$this->hasExited ) {
			$this->hasExited = true;
			( $this->exitCallback )( $this->id );
		}
	}

	/**
	 * Get an integer uniquely identifying the section (within the scope of the
	 * parent RequestTimeout object).
	 *
	 * @since 1.1.0
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}
}
