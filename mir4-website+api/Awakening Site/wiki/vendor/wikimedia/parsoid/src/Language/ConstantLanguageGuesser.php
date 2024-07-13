<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Parsoid\DOM\Element;

/**
 * A simple {@link LanguageGuesser} that returns the same "source language" for every node.
 * Appropriate for wikis which by convention are written in a single variant.
 */
class ConstantLanguageGuesser extends LanguageGuesser {

	/** @var Bcp47Code */
	private $langCode;

	/**
	 * @param Bcp47Code $langCode a language code
	 */
	public function __construct( Bcp47Code $langCode ) {
		$this->langCode = $langCode;
	}

	/** @inheritDoc */
	public function guessLang( Element $node ): Bcp47Code {
		return $this->langCode;
	}

}
