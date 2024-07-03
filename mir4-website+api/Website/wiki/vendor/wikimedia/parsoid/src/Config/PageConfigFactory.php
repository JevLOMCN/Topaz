<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config;

/**
 * Marker interface for a PageConfig factory defined in core.
 *
 * The actual types used in the method signatures for this class are
 * not available to Parsoid, but define an empty marker interface
 * so we can pass around the class even though we can't name the
 * methods.
 */
abstract class PageConfigFactory {
}
