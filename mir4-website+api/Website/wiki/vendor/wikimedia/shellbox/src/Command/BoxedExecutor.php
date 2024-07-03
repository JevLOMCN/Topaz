<?php

namespace Shellbox\Command;

/**
 * Base class for things that execute BoxedCommands
 */
abstract class BoxedExecutor {
	/** @var Validator|null */
	protected $validator;

	/**
	 * Execute a boxed command.
	 *
	 * @param BoxedCommand $command
	 * @return BoxedResult
	 */
	final public function execute( BoxedCommand $command ) {
		$this->assertIsValid( $command );
		return $this->executeValid( $command );
	}

	/**
	 * Execute a BoxedCommand that has already been validated.
	 *
	 * @param BoxedCommand $command
	 * @return BoxedResult
	 */
	abstract public function executeValid( BoxedCommand $command );

	/**
	 * Create an empty command linked to this executor
	 *
	 * @return BoxedCommand
	 */
	public function createCommand() {
		return new BoxedCommand( $this );
	}

	/**
	 * Set validation configuration
	 *
	 * @param array $config
	 */
	public function setValidationConfig( $config ) {
		$this->validator = new Validator( $config );
	}

	/**
	 * Validate the command. If it is not valid, throw an exception
	 *
	 * @param BoxedCommand $command
	 * @throws ValidationError
	 */
	protected function assertIsValid( BoxedCommand $command ) {
		if ( !$this->validator ) {
			return;
		}
		$this->validator->validate( $command );
	}
}
