<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

/** Crimean Tatar (Qırımtatarca) conversion code */
class LanguageCrh extends Language {

	public function __construct() {
		$variants = [ 'crh', 'crh-cyrl', 'crh-latn' ];
		$variantfallbacks = [
			'crh' => 'crh-latn',
			'crh-cyrl' => 'crh-latn',
			'crh-latn' => 'crh-cyrl',
		];
		$converter = new CrhConverter( $this, 'crh', $variants, $variantfallbacks );
		$this->setConverter( $converter );
	}

}
