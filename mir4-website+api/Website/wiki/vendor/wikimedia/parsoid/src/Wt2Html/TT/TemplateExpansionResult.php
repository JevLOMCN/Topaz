<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

class TemplateExpansionResult {

	/** @var array */
	public $tokens;

	/** @var bool */
	public $shuttle;

	/** @var bool */
	public $encap;

	public function __construct( array $tokens, bool $shuttle = false, bool $encap = false ) {
		$this->tokens = $tokens;
		$this->shuttle = $shuttle;
		$this->encap = $encap;
	}
}
