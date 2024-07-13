<?php
/**
 * @private
 */
class Less_Tree_Assignment extends Less_Tree implements Less_Tree_HasValueProperty {

	public $key;
	public $value;

	public function __construct( $key, $val ) {
		$this->key = $key;
		$this->value = $val;
	}

	public function accept( $visitor ) {
		$this->value = $visitor->visitObj( $this->value );
	}

	public function compile( $env ) {
		return new self( $this->key, $this->value->compile( $env ) );
	}

	/**
	 * @see Less_Tree::genCSS
	 */
	public function genCSS( $output ) {
		$output->add( $this->key . '=' );
		$this->value->genCSS( $output );
	}

	public function toCss() {
		return $this->key . '=' . $this->value->toCSS();
	}
}
