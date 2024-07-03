<?php

namespace Shellbox\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base class for wrappers that modify commands. Such wrappers are used by
 * UnboxedExecutor to implement restrictions.
 */
abstract class Wrapper {
	/** @var LoggerInterface */
	protected $logger;

	public function __construct() {
		$this->logger = new NullLogger;
	}

	/**
	 * Set the logger.
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Modify the command passed as a parameter
	 *
	 * @param Command $command
	 */
	abstract public function wrap( Command $command );

	/**
	 * Get an integer priority level used to determine the order in which to
	 * run multiple wrappers. Low numbers are innermost, high numbers are
	 * outermost, run last.
	 *
	 * If you nest sandboxes, it makes sense to have the most privileged
	 * hypervisor/wrapper at the outside, and the least privileged on the
	 * inside. Suggested values:
	 *
	 *    - 20: ulimit
	 *    - 40: chroot
	 *    - 60: system-level container
	 *    - 80: initial shell
	 *
	 * @return int
	 */
	abstract public function getPriority();
}
