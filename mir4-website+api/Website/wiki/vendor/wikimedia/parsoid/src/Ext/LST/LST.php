<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\LST;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

class LST extends ExtensionTagHandler implements ExtensionModule {

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'LST',
			'tags' => [
				[
					'name' => 'labeledsectiontransclusion',
					'handler' => self::class,
				],
				[
					'name' => 'labeledsectiontransclusion/begin',
					'handler' => self::class,
				],
				[
					'name' => 'labeledsectiontransclusion/end',
					'handler' => self::class,
				]
			]
		];
	}

	/** @inheritDoc */
	public function domToWikitext(
		ParsoidExtensionAPI $extApi, Element $node, bool $wrapperUnmodified
	) {
		// TODO: We're keeping this serial handler around to remain backwards
		// compatible with stored content version 1.3.0 and below.  Remove it
		// when those versions are no longer supported.

		$dp = DOMDataUtils::getDataParsoid( $node );
		$src = null;
		$content = DOMCompat::getAttribute( $node, 'content' ) ?? '';
		if ( isset( $dp->src ) ) {
			$src = $dp->src;
		} elseif ( DOMUtils::matchTypeOf( $node, '/begin/' ) ) {
			$src = '<section begin="' . $content . '" />';
		} elseif ( DOMUtils::matchTypeOf( $node, '/end/' ) ) {
			$src = '<section end="' . $content . '" />';
		} else {
			$extApi->log( 'error', 'LST <section> without content in: ' .
				DOMCompat::getOuterHTML( $node ) );
			$src = '<section />';
		}
		return $src;
	}

}
