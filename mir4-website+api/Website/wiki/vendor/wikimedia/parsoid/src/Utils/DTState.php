<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use stdClass;

/**
 * State carried while DOM Traversing
 */
class DTState {
	/**
	 * @var array
	 */
	public $options;

	/**
	 * @var bool
	 */
	public $atTopLevel;

	/**
	 * @var stdClass|null
	 */
	public $tplInfo;

	/**
	 * @param array $options
	 * @param bool $atTopLevel
	 */
	public function __construct( array $options = [], bool $atTopLevel = false ) {
		$this->options = $options;
		$this->atTopLevel = $atTopLevel;
	}
}
