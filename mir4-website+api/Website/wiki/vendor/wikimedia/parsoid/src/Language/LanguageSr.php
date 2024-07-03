<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

/** Serbian (Српски / Srpski) specific code. */
class LanguageSr extends Language {

	public function __construct() {
		$variants = [ 'sr', 'sr-ec', 'sr-el' ];
		$variantfallbacks = [
			'sr' => 'sr-ec',
			'sr-ec' => 'sr',
			'sr-el' => 'sr',
		];
		$flags = [
			'S' => 'S',
			"писмо" => 'S',
			'pismo' => 'S',
			'W' => 'W',
			"реч" => 'W',
			"reč" => 'W',
			"ријеч" => 'W',
			"riječ" => 'W',
		];
		$converter = new SrConverter( $this, 'sr', $variants, $variantfallbacks, $flags );
		$this->setConverter( $converter );
	}

}
