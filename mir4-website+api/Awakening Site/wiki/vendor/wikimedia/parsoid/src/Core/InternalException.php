<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Core;

/**
 * Parsoid internal error that we don't know how to recover from.
 * Likely a result of bad configuration / bad code / edge case.
 */
class InternalException extends \Exception {
}
