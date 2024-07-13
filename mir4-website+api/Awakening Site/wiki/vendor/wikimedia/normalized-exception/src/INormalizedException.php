<?php

namespace Wikimedia\NormalizedException;

/**
 * Interface for exceptions whose error message supports PSR-3 style placeholders.
 * This allows extracting variable parts of the error message, so that the
 * remaining normalized message can be used for better grouping of related errors
 * in the logs.
 *
 * E.g. an exception message `User 'Foo' not found` could be normalized into
 * `User '{user}' not found`, with `[ 'user' => 'Foo' ]` as context data.
 *
 * @stable to implement
 */
interface INormalizedException {

	/**
	 * Returns a normalized version of the error message, with PSR-3 style placeholders.
	 * After replacing the placeholders with their values from the context data, the
	 * result should be equal to getMessage().
	 *
	 * @return string
	 */
	public function getNormalizedMessage(): string;

	/**
	 * Returns the context data. All placeholders in the normalized message must have a
	 * matching field in the array. Extra fields are allowed. Keys should match
	 * `/[a-zA-Z0-9_.]+/`, as per the PSR-3 spec. Values should be scalars.
	 *
	 * @return (int|float|string|bool)[]
	 */
	public function getMessageContext(): array;

}
