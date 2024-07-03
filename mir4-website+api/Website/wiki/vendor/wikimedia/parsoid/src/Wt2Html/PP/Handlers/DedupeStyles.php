<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Handlers;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\DTState;

class DedupeStyles {

	/**
	 * @param Element $node
	 * @param Env $env
	 * @param DTState $state
	 * @return bool|Element
	 */
	public static function dedupe( Element $node, Env $env, DTState $state ) {
		// Don't run on embedded docs for now since we don't want the
		// canonical styles to be introduced in embedded HTML which means
		// they will get lost wrt the top level document.
		if ( !$state->atTopLevel ) {
			return true;
		}

		$key = DOMCompat::getAttribute( $node, 'data-mw-deduplicate' );
		if ( $key === null ) {
			// Not a templatestyles <style> tag
			return true;
		}

		if ( !isset( $env->styleTagKeys[$key] ) ) {
			// Not a dupe
			$env->styleTagKeys[$key] = true;
			return true;
		}

		if ( !DOMUtils::isFosterablePosition( $node ) ) {
			// Dupe - replace with a placeholder <link> reference
			$link = $node->ownerDocument->createElement( 'link' );
			DOMUtils::addRel( $link, 'mw-deduplicated-inline-style' );
			$link->setAttribute( 'href', 'mw-data:' . $key );
			$link->setAttribute( 'about', DOMCompat::getAttribute( $node, 'about' ) ?? '' );
			$link->setAttribute( 'typeof', DOMCompat::getAttribute( $node, 'typeof' ) ?? '' );
			DOMDataUtils::setDataParsoid( $link, DOMDataUtils::getDataParsoid( $node ) );
			DOMDataUtils::setDataMw( $link, DOMDataUtils::getDataMw( $node ) );
			$node->parentNode->replaceChild( $link, $node );
			return $link;
		} else {
			$env->log( 'info/wt2html/templatestyle',
				'Duplicate style tag found in fosterable position. ' .
					'Not deduping it, but emptying out the style tag for performance reasons.'
			);
			DOMCompat::replaceChildren( $node );
			return true;
		}
	}
}
