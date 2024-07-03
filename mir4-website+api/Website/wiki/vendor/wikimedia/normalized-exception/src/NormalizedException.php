<?php

namespace Wikimedia\NormalizedException;

use Exception;
use Throwable;

/**
 * Basic normalized exception.
 *
 * @newable
 * @stable to extend
 */
class NormalizedException extends Exception implements INormalizedException {

	use NormalizedExceptionTrait;

	/**
	 * @stable to call
	 * @param string $normalizedMessage The exception message, with PSR-3 style placeholders.
	 * @param array $messageContext Message context, with values for the placeholders.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous throwable used for the exception chaining.
	 */
	public function __construct(
		string $normalizedMessage,
		array $messageContext,
		int $code = 0,
		Throwable $previous = null
	) {
		$this->normalizedMessage = $normalizedMessage;
		$this->messageContext = $messageContext;
		parent::__construct(
			self::getMessageFromNormalizedMessage( $normalizedMessage, $messageContext ),
			$code,
			$previous
		);
	}

}
