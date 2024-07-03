<?php

namespace Wikimedia\NormalizedException;

/**
 * Trait for creating a normalized exception
 */
trait NormalizedExceptionTrait {

	/** @var string */
	protected $normalizedMessage;

	/** @var (int|float|string|bool)[] */
	protected $messageContext;

	/**
	 * Turn a PSR-3 style normalized message and context into a real message,
	 * by interpolating the context variables into the message string.
	 *
	 * Intended for use in exception constructors to construct the message
	 * that's passed to the parent constructor.
	 *
	 * @stable to call
	 * @param string $normalizedMessage A message string with zero or more
	 *   {}-wrapped tokens in it.
	 * @param array $context An array maping tokens (without the braces) to
	 *   values. Fields not used in the message are allowed. Values that are
	 *   used in the message should be scalars or have a __toString() method.
	 * @return string
	 */
	public static function getMessageFromNormalizedMessage( string $normalizedMessage, array $context ) {
		$replacements = [];
		foreach ( $context as $placeholder => $value ) {
			if ( is_bool( $value ) ) {
				$stringValue = $value ? '<true>' : '<false>';
			} elseif ( $value === null ) {
				$stringValue = '<null>';
			} else {
				$stringValue = (string)$value;
			}
			$replacements['{' . $placeholder . '}'] = $stringValue;
		}
		return strtr( $normalizedMessage, $replacements );
	}

	/** @inheritDoc */
	public function getNormalizedMessage(): string {
		return $this->normalizedMessage;
	}

	/** @inheritDoc */
	public function getMessageContext(): array {
		return $this->messageContext;
	}

}
