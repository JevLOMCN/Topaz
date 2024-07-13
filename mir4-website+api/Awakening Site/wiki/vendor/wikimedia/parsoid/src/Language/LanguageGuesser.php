<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Parsoid\DOM\Element;

/**
 * An oracle that gives you a predicted "source language" for every node in a DOM, which is used
 * when converting the result back to the source language during round-tripping.
 */
abstract class LanguageGuesser {

	/**
	 * @param Element $node
	 * @return Bcp47Code predicted source language
	 */
	abstract public function guessLang( Element $node ): Bcp47Code;

}
