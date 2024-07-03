<?php

namespace Wikimedia\Purtle;

use InvalidArgumentException;

/**
 * Helper class for generating labels for blank nodes.
 *
 * This serves as a holder for the bnode counter that can be shared between multiple RdfWriter
 * instances, to avoid conflicting ids.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class BNodeLabeler {

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var int
	 */
	private $counter;

	/**
	 * @param string $prefix
	 * @param int $start
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $prefix = 'genid', $start = 1 ) {
		if ( !is_string( $prefix ) ) {
			throw new InvalidArgumentException( '$prefix must be a string' );
		}

		if ( !is_int( $start ) || $start < 1 ) {
			throw new InvalidArgumentException( '$start must be an int >= 1' );
		}

		$this->prefix = $prefix;
		$this->counter = $start;
	}

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string
	 */
	public function getLabel( $label = null ) {
		if ( $label === null ) {
			$label = $this->prefix . $this->counter;
			$this->counter ++;
		}

		return $label;
	}

}
