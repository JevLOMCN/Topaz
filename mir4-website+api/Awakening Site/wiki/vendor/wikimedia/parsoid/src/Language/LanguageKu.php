<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

/** Kurdish conversion code. */
class LanguageKu extends Language {

	public function __construct() {
		$variants = [ 'ku', 'ku-arab', 'ku-latn' ];
		$variantfallbacks = [
			'ku'  => 'ku-latn',
			'ku-arab' => 'ku-latn',
			'ku-latn' => 'ku-arab',
		];
		$this->setConverter( new KuConverter( $this, 'ku', $variants, $variantfallbacks ) );
	}

}
