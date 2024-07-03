<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Indicator;

use Closure;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * Implements the php parser's `indicator` hook natively.
 */
class Indicator extends ExtensionTagHandler implements ExtensionModule {
	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'Indicator',
			'tags' => [
				[
					'name' => 'indicator',
					'handler' => self::class,
					'options' => [
						'wt2html' => [
							'embedsHTMLInAttributes' => true,
							'customizesDataMw' => true,
						],
						'outputHasCoreMwDomSpecMarkup' => true
					],
				]
			],
		];
	}

	/** @inheritDoc */
	public function processAttributeEmbeddedHTML(
		ParsoidExtensionAPI $extApi, Element $elt, Closure $proc
	): void {
		$dmw = DOMDataUtils::getDataMw( $elt );
		if ( isset( $dmw->html ) ) {
			$dmw->html = $proc( $dmw->html );
		}
	}

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $content, array $args
	): DocumentFragment {
		$dataMw = $extApi->extTag->getDefaultDataMw();
		$kvArgs = $extApi->extArgsToArray( $args );
		$name = $kvArgs['name'] ?? '';
		if ( trim( $name ) === '' ) {
			$out = $extApi->pushError( 'invalid-indicator-name' );
			DOMDataUtils::setDataMw( $out->firstChild, $dataMw );
			return $out;
		}

		// Convert indicator wikitext to DOM
		$domFragment = $extApi->extTagToDOM( [] /* No args to apply */, $content, [
				'parseOpts' => [ 'extTag' => 'indicator' ],
			] );

		// Strip an outer paragraph if it is the sole paragraph without additional attributes
		$content = $domFragment->firstChild;
		if ( $content &&
			DOMCompat::nodeName( $content ) === 'p' &&
			$content->nextSibling === null &&
			$content instanceof Element && // Needed to mollify Phan
			DOMDataUtils::noAttrs( $content )
		) {
			DOMUtils::migrateChildren( $content, $domFragment );
			$domFragment->removeChild( $content );
		}

		// Save HTML and remove content from the fragment
		$dataMw->html = $extApi->domToHtml( $domFragment, true );

		$c = $domFragment->firstChild;
		while ( $c ) {
			$domFragment->removeChild( $c );
			$c = $domFragment->firstChild;
		}

		// Use a meta tag whose data-mw we will stuff this HTML into later.
		// NOTE: Till T214994 is resolved, this HTML will not get processed
		// by all the top-level DOM passes that may need to process this (ex: linting)
		$meta = $domFragment->ownerDocument->createElement( 'meta' );

		DOMDataUtils::setDataMw( $meta, $dataMw );

		// Append meta
		$domFragment->appendChild( $meta );

		return $domFragment;
	}
}
