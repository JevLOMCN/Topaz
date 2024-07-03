<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use RuntimeException;

/**
 * Exception thrown for invalid titles
 * @note Replaces JS TitleError, because that implies it extends Error rather than Exception
 */
class TitleException extends RuntimeException {
	public $type;
	public $title;

	public function __construct( string $message, string $type, string $title ) {
		parent::__construct( $message );
		$this->type = $type;
		$this->title = $title;
	}
}
