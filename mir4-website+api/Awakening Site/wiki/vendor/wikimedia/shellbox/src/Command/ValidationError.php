<?php

namespace Shellbox\Command;

use Shellbox\ShellboxError;

class ValidationError extends ShellboxError {
	/**
	 * @param string $message
	 */
	public function __construct( $message ) {
		parent::__construct( "Shellbox command validation error: $message" );
	}
}
