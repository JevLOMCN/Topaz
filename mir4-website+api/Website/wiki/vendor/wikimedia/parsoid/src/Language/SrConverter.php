<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\LangConv\FstReplacementMachine;
use Wikimedia\Parsoid\Utils\Utils;

class SrConverter extends LanguageConverter {

	public function loadDefaultTables() {
		# T320662: should be converted from mediawiki-internal codes
		$this->setMachine( new FstReplacementMachine( 'sr', [ 'sr-ec', 'sr-el' ] ) );
	}

	// phpcs:ignore MediaWiki.Commenting.FunctionComment.MissingDocumentationPublic
	public function findVariantLink( $link, $nt, $ignoreOtherCond ) {
		$ns = $nt->getNamespace();
		// do not try to find variants for usernames
		if ( $ns->isUser() || $ns->isUserTalk ) {
			return [ 'nt' => $nt, 'link' => $link ];
		}
		// FIXME check whether selected language is 'sr'
		return parent::findVariantLink( $link, $nt, $ignoreOtherCond );
	}

	/**
	 * Variant based on the ReplacementMachine's bracketing abilities
	 * @param string $text
	 * @param Bcp47Code $variant a language code
	 * @return bool
	 */
	public function guessVariant( $text, $variant ) {
		# T320662 This code is implemented using MW-internal codes
		$variant = Utils::bcp47ToMwCode( $variant );
		$r = [];
		$machine = $this->getMachine();
		'@phan-var FstReplacementMachine $machine'; /* @var FstReplacementMachine $machine */
		foreach ( $machine->getCodes() as $code => $ignore1 ) {
			foreach ( $machine->getCodes() as $othercode => $ignore2 ) {
				if ( $code === $othercode ) {
					return false;
				}
				$r[] = [
					'code' => $code,
					'othercode' => $othercode,
					'stats' => $machine->countBrackets( $text, $code, $othercode )
				];
			}
		}
		uasort( $r, static function ( $a, $b ) {
			return $a['stats']->unsafe - $b['stats']->unsafe;
		} );
		return $r[0]['othercode'] === $variant;
	}
}
