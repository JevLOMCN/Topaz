<?php
/**
 * @private
 */
class Less_Tree_Quoted extends Less_Tree implements Less_Tree_HasValueProperty {
	public $escaped;
	/** @var string */
	public $value;
	public $quote;
	public $index;
	public $currentFileInfo;

	/**
	 * @param string $str
	 */
	public function __construct( $str, $content = '', $escaped = true, $index = false, $currentFileInfo = null ) {
		$this->escaped = $escaped;
		$this->value = $content;
		if ( $str ) {
			$this->quote = $str[0];
		}
		$this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
	}

	/**
	 * @see Less_Tree::genCSS
	 */
	public function genCSS( $output ) {
		if ( !$this->escaped ) {
			$output->add( $this->quote, $this->currentFileInfo, $this->index );
		}
		$output->add( $this->value );
		if ( !$this->escaped ) {
			$output->add( $this->quote );
		}
	}

	public function compile( $env ) {
		$value = $this->value;
		if ( preg_match_all( '/`([^`]+)`/', $this->value, $matches ) ) {
			foreach ( $matches[1] as $i => $match ) {
				$js = new Less_Tree_JavaScript( $match, $this->index, true );
				$js = $js->compile( $env )->value;
				$value = str_replace( $matches[0][$i], $js, $value );
			}
		}
		$r = $value;
		do {
			$value = $r;
			if ( preg_match_all( '/@\{([\w-]+)\}/', $value, $matches ) ) {
				foreach ( $matches[1] as $i => $match ) {
					$v = new Less_Tree_Variable( '@' . $match, $this->index, $this->currentFileInfo );
					$v = $v->compile( $env );
					$v = ( $v instanceof self ) ? $v->value : $v->toCSS();
					$r = str_replace( $matches[0][$i], $v, $r );
				}
			}
		} while ( $r != $value );

		return new self( $this->quote . $r . $this->quote, $r, $this->escaped, $this->index, $this->currentFileInfo );
	}

	/**
	 * @param mixed $other
	 * @return int|null
	 * @see less-2.5.3.js#Quoted.prototype.compare
	 */
	public function compare( $other ) {
		if ( $other instanceof self && !$this->escaped && !$other->escaped ) {
			return Less_Tree::numericCompare( $this->value, $other->value );
		} else {
			return (
				Less_Parser::is_method( $other, 'toCSS' )
				&& $this->toCSS() === $other->toCSS()
			) ? 0 : null;
		}
	}
}
