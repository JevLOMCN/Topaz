<?php

namespace Wikimedia\Assert;

use RuntimeException;

/**
 * Exception indicating that an precondition assertion failed.
 * This generally means a disagreement between the caller and the implementation of a function.
 *
 * @since 0.1.0
 *
 * @license MIT
 * @author Daniel Kinzler
 * @copyright Wikimedia Deutschland e.V.
 */
class PreconditionException extends RuntimeException implements AssertionException {

}
