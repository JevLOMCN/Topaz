<?php

namespace Wikimedia\Assert;

/**
 * Exception indicating that a parameter key type assertion failed.
 * This generally means a disagreement between the caller and the implementation of a function.
 *
 * @since 0.3.0
 *
 * @license MIT
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 * @copyright Wikimedia Deutschland e.V.
 */
class ParameterKeyTypeException extends ParameterAssertionException {

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @param string $parameterName
	 * @param string $type
	 *
	 * @throws ParameterTypeException
	 */
	public function __construct( $parameterName, $type ) {
		if ( !is_string( $type ) ) {
			throw new ParameterTypeException( 'type', 'string' );
		}

		parent::__construct( $parameterName, "all elements must have $type keys" );

		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

}
