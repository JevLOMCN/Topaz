<?php

namespace Wikimedia\RequestTimeout;

use LogicException;
use Wikimedia\NormalizedException\INormalizedException;
use Wikimedia\NormalizedException\NormalizedExceptionTrait;

/**
 * An exception thrown when CriticalSectionProvider::exit() is called with a
 * name not matching the one used in the prior CriticalSectionProvider::enter()
 * call.
 */
class CriticalSectionMismatchException extends LogicException implements INormalizedException {
	use NormalizedExceptionTrait;

	/**
	 * @param string $actual The name used in exit()
	 * @param string $expected The name used in enter(), or "[none]" if there
	 *   was no enter() call.
	 */
	public function __construct( $actual, $expected ) {
		$this->normalizedMessage = "Critical section name mismatch: expected {expected}, got $actual";
		$this->messageContext = [ 'expected' => $expected ];

		parent::__construct(
			$this->getMessageFromNormalizedMessage( $this->normalizedMessage, $this->messageContext )
		);
	}
}
