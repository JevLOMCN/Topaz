<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use RuntimeException;

/**
 * Class RangeBuilderException
 * Thrown when a DOMRangeBuilder encounters an unexpected state
 * @package Wikimedia\Parsoid\Wt2Html\PP\Processors
 */
class RangeBuilderException extends RuntimeException {
}
