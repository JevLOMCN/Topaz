<?php
/**
 * @private
 */
class Less_Tree_JavaScript extends Less_Tree {

	public $escaped;
	public $expression;
	public $index;

	/**
	 * @param string $string
	 * @param int $index
	 * @param bool $escaped
	 */
	public function __construct( $string, $index, $escaped ) {
		$this->escaped = $escaped;
		$this->expression = $string;
		$this->index = $index;
	}

	public function compile( $env ) {
		return new Less_Tree_Anonymous( '/* Sorry, can not do JavaScript evaluation in PHP... :( */' );
	}

}
