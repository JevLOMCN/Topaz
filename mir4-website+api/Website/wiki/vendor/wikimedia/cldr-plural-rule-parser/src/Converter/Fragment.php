<?php
/**
 * @author Niklas Laxström, Tim Starling
 * @license GPL-2.0-or-later
 * @file
 */

namespace CLDRPluralRuleParser\Converter;

use CLDRPluralRuleParser\Converter;
use CLDRPluralRuleParser\Error;

/**
 * Helper for Converter.
 * The base class for operators and expressions, describing a region of the input string.
 */
class Fragment {
	/**
	 * @var Converter
	 */
	public $parser;

	/**
	 * @var int
	 */
	public $pos;

	/**
	 * @var int
	 */
	public $length;

	/**
	 * @var int
	 */
	public $end;

	/**
	 * @param Converter $parser
	 * @param int $pos
	 * @param int $length
	 */
	public function __construct( Converter $parser, $pos, $length ) {
		$this->parser = $parser;
		$this->pos = $pos;
		$this->length = $length;
		$this->end = $pos + $length;
	}

	/**
	 * @param string $message
	 *
	 * @throws Error
	 */
	public function error( $message ) {
		$text = $this->getText();
		throw new Error( "$message at position " . ( $this->pos + 1 ) . ": \"$text\"" );
	}

	/**
	 * @return string
	 */
	public function getText() {
		return substr( $this->parser->rule, $this->pos, $this->length );
	}
}
