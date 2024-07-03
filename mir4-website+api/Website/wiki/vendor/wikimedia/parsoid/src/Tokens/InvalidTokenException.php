<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Tokens;

use RuntimeException;

/**
 * Thrown when a token is invalid
 */
class InvalidTokenException extends RuntimeException {
}
