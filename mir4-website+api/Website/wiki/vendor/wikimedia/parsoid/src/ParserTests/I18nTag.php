<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class I18nTag extends ExtensionTagHandler implements ExtensionModule {
	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $content, array $args
	): DocumentFragment {
		$tag = $extApi->extTag;
		if ( $tag->getName() === 'i18ntag' ) {
			return $extApi->createPageContentI18nFragment( $content, null );
		} else {
			$frag = $extApi->getTopLevelDoc()->createDocumentFragment();
			$span = $extApi->getTopLevelDoc()->createElement( 'span' );
			$frag->appendChild( $span );
			$span->appendChild( $extApi->getTopLevelDoc()->createTextNode( $content ) );
			$extApi->addInterfaceI18nAttribute( $span, 'message', $args[0]->v, null );
			return $frag;
		}
	}

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'I18nTag',
			'tags' => [
				[ 'name' => 'i18ntag', 'handler' => self::class ],
				[ 'name' => 'i18nattr', 'handler' => self::class ],
			],
		];
	}
}
