<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

class StyleTag extends ExtensionTagHandler implements ExtensionModule {
	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $content, array $args
	): DocumentFragment {
		$domFragment = $extApi->htmlToDom( '' );
		$style = $domFragment->ownerDocument->createElement( 'style' );
		DOMCompat::setInnerHTML( $style, $content );
		Sanitizer::applySanitizedArgs( $extApi->getSiteConfig(), $style, $args );
		$domFragment->appendChild( $style );
		return $domFragment;
	}

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'StyleTag',
			'tags' => [
				[ 'name' => 'style', 'handler' => self::class ],
			],
		];
	}
}
