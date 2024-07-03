<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

/** English ( / Pig Latin) conversion code */
class LanguageEn extends Language {

	public function __construct() {
		$variants = [ 'en', 'en-x-piglatin' ];
		$this->setConverter( new EnConverter( $this, 'en', $variants ) );
	}

}
