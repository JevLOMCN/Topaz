<?php
/**
 * @author Tim Starling
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @file
 */

namespace CLDRPluralRuleParser;

/**
 * The exception class for all the classes in this file. This will be thrown
 * back to the caller if there is any validation error.
 */
class Error extends \Exception {
	/**
	 * @param string $message
	 */
	public function __construct( $message ) {
		parent::__construct( 'CLDR plural rule error: ' . $message );
	}
}
