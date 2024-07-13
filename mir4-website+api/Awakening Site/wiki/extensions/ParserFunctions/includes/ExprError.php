<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace MediaWiki\Extension\ParserFunctions;

use Exception;
use Message;

class ExprError extends Exception {
	/** @var Message */
	private $mwMessage;

	/**
	 * @param string $msg
	 * @param string $parameter
	 */
	public function __construct( $msg, $parameter = '' ) {
		// Give grep a chance to find the usages:
		// pfunc_expr_stack_exhausted, pfunc_expr_unexpected_number, pfunc_expr_preg_match_failure,
		// pfunc_expr_unrecognised_word, pfunc_expr_unexpected_operator, pfunc_expr_missing_operand,
		// pfunc_expr_unexpected_closing_bracket, pfunc_expr_unrecognised_punctuation,
		// pfunc_expr_unclosed_bracket, pfunc_expr_division_by_zero, pfunc_expr_invalid_argument,
		// pfunc_expr_invalid_argument_ln, pfunc_expr_unknown_error, pfunc_expr_not_a_number
		$this->mwMessage = wfMessage( "pfunc_expr_$msg", $parameter );
	}

	/**
	 * Replacement for getMessage() to prevent message parsing during tests which initializes
	 * whole bloody MediaWiki.
	 *
	 * @return string Error message to be to be displayed to end users
	 */
	public function getUserFriendlyMessage() {
		return $this->mwMessage->inContentLanguage()->text();
	}
}
