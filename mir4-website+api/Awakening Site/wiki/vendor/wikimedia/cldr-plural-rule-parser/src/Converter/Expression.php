<?php
/**
 * @author Niklas Laxström, Tim Starling
 * @license GPL-2.0-or-later
 * @file
 */

namespace CLDRPluralRuleParser\Converter;

use CLDRPluralRuleParser\Converter;

/**
 * Helper for Converter.
 * An expression object, representing a region of the input string (for error
 * messages), the RPN notation used to evaluate it, and the result type for
 * validation.
 */
class Expression extends Fragment {
	/** @var string */
	public $type;

	/** @var string */
	public $rpn;

	/**
	 * @param Converter $parser
	 * @param string $type
	 * @param string $rpn
	 * @param int $pos
	 * @param int $length
	 */
	public function __construct( Converter $parser, $type, $rpn, $pos, $length ) {
		parent::__construct( $parser, $pos, $length );
		$this->type = $type;
		$this->rpn = $rpn;
	}

	/**
	 * @param string $type
	 * @return bool
	 */
	public function isType( $type ) {
		if ( $type === 'range' && ( $this->type === 'range' || $this->type === 'number' ) ) {
			return true;
		}
		if ( $type === $this->type ) {
			return true;
		}

		return false;
	}
}
