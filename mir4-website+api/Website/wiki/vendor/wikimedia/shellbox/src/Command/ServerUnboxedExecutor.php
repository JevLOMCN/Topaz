<?php

namespace Shellbox\Command;

use Shellbox\TempDirManager;

/**
 * Typically UnboxedExecutor rarely needs a TempDirManager, and so it is
 * lazy-initialised to avoid the need for secure random numbers in the usual
 * case. This ServerUnboxedExecutor instead takes a TempDirManager injected
 * into its constructor, which is convenient for the Server since it has already
 * initialised such an object.
 */
class ServerUnboxedExecutor extends UnboxedExecutor {
	/** @var TempDirManager */
	private $tempDirManager;

	/**
	 * @param TempDirManager $tempDirManager
	 */
	public function __construct( TempDirManager $tempDirManager ) {
		parent::__construct();
		$this->tempDirManager = $tempDirManager;
	}

	protected function getTempDirManager() {
		return $this->tempDirManager;
	}
}
