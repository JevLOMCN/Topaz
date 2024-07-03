<?php

namespace Wikimedia\Assert;

use LogicException;

/**
 * Exception indicating that a code path expected to be unreachable was reached.
 * This generally means an error in the internal logic of a function, or a
 * serious problem in the runtime environment.
 *
 * @since 0.5.1
 *
 * @license MIT
 * @author C. Scott Ananian
 */
class UnreachableException extends LogicException implements AssertionException {

}
