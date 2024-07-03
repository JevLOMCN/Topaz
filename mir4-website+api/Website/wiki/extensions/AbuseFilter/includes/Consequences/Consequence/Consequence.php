<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;

/**
 * Base command-style class for consequences.
 */
abstract class Consequence {
	/** @var Parameters */
	protected $parameters;

	/**
	 * @param Parameters $parameters
	 */
	public function __construct( Parameters $parameters ) {
		$this->parameters = $parameters;
	}

	/**
	 * @return bool A generic success indicator, subclasses can be more specific
	 */
	abstract public function execute(): bool;
}
