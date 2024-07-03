<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\ConstrainedText;

class State {
	/** @var string */
	public $leftContext;
	/** @var string */
	public $rightContext;
	/** @var ConstrainedText[] */
	public $line;
	/** @var int */
	public $pos;

	/**
	 * @param ConstrainedText[] $line
	 */
	public function __construct( array $line ) {
		$this->leftContext = '';
		$this->rightContext = implode( '', array_map( static function ( $ct ) {
			return $ct->text;
		}, $line ) );
		$this->line = $line;
		$this->pos = 0;
	}
}
