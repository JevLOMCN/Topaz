<?php

namespace Shellbox\Command;

/**
 * A command without file handling.
 *
 * This is almost the same as Command, except with a type-hinted executor.
 */
class UnboxedCommand extends Command {
	/** @var UnboxedExecutor */
	protected $executor;

	/**
	 * External callers should typically use UnboxedExecutor::createCommand()
	 *
	 * @param UnboxedExecutor $executor
	 */
	public function __construct( UnboxedExecutor $executor ) {
		$this->executor = $executor;
	}

	/**
	 * Execute the command with the current executor
	 *
	 * @return UnboxedResult
	 */
	public function execute() {
		return $this->executor->execute( $this );
	}
}
