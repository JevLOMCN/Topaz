<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

use stdClass;
use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\LangConv\FstReplacementMachine;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMPostOrder;
use Wikimedia\Parsoid\Utils\Utils;

/**
 * Use a {@Link ReplacementMachine} to predict the best "source language" for every node in a DOM.
 * Appropriate for wikis which are written in a mix of variants.
 */
class MachineLanguageGuesser extends LanguageGuesser {

	/**
	 * MachineLanguageGuesser constructor.
	 * @param FstReplacementMachine $machine
	 * @param Node $root
	 * @param Bcp47Code $destCode a language code
	 */
	public function __construct( FstReplacementMachine $machine, Node $root, $destCode ) {
		# T320662 This code uses MW-internal codes internally
		$destCode = Utils::bcp47ToMwCode( $destCode );

		$codes = [];
		foreach ( $machine->getCodes() as $invertCode => $ignore ) {
			if ( $machine->isValidCodePair( $destCode, $invertCode ) ) {
				$codes[] = $invertCode;
			}
		}
		$zeroCounts = [];
		foreach ( $codes as $invertCode ) {
			$zeroCounts[$invertCode] = 0;
		}

		DOMPostOrder::traverse(
			$root, function ( Node &$node ) use (
				$machine, $codes, $destCode, $zeroCounts
			) {
				if ( !( $node instanceof Element ) ) {
					// Elements only!
					return;
				}
				// XXX look at `lang` attribute and use it to inform guess?
				$nodeData = self::getNodeData( $node );
				$first = true;
				// Iterate over child *nodes* (not just elements)
				for ( $child = $node->firstChild;
					  $child;
					  $child = $child->nextSibling
				) {
					if ( $child instanceof Text ) {
						$countMap = [];
						foreach ( $codes as $invertCode ) {
							$countMap[$invertCode] = $machine->countBrackets(
								$child->textContent,
								$destCode,
								$invertCode
							)->safe;
						}
					} elseif ( $child instanceof Element ) {
						$countMap = self::getNodeData( $child )->countMap;
					} else {
						continue; // skip this non-element non-text node
					}
					if ( $first ) {
						$nodeData->countMap = $countMap;
						$first = false;
					} else {
						// accumulate child counts!
						foreach ( $codes as $c ) {
							$nodeData->countMap[$c] += $countMap[$c];
						}
					}
				}
				if ( $first ) {
					$nodeData->countMap = $zeroCounts;
				}
				// Compute best guess for language
				$safe = [];
				foreach ( $codes as $code ) {
					$safe[$code] = $nodeData->countMap[$code];
				}
				arsort( $safe );
				$nodeData->guessLang = array_keys( $safe )[0];
			} );
	}

	/**
	 * Helper function that namespaces all of our node data used in
	 * this class into the top-level `mw_variant` key.
	 *
	 * @param Element $node
	 * @return stdClass
	 */
	private static function getNodeData( Element $node ): stdClass {
		$nodeData = DOMDataUtils::getNodeData( $node );
		if ( !isset( $nodeData->mw_variant ) ) {
			$nodeData->mw_variant = new stdClass;
		}
		return $nodeData->mw_variant;
	}

	/** @inheritDoc */
	public function guessLang( Element $node ): Bcp47Code {
		return Utils::mwCodeToBcp47( self::getNodeData( $node )->guessLang );
	}
}
