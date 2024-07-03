<?php
declare( strict_types = 1 );

namespace MediaWiki\Extension\Poem\Parsoid;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Ext\DOMProcessor;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class PoemProcessor extends DOMProcessor {

	/**
	 * @inheritDoc
	 */
	public function wtPostprocess(
		ParsoidExtensionAPI $extApi, Node $node, array $options
	): void {
		$c = $node->firstChild;
		while ( $c ) {
			if ( $c instanceof Element ) {
				if ( DOMUtils::hasTypeOf( $c, 'mw:Extension/poem' ) ) {
					// Replace newlines found in <nowiki> fragment with <br/>s
					self::processNowikis( $c );
				} else {
					$this->wtPostprocess( $extApi, $c, $options );
				}
			}
			$c = $c->nextSibling;
		}
	}

	private function processNowikis( Element $node ): void {
		$doc = $node->ownerDocument;
		$c = $node->firstChild;
		while ( $c ) {
			if ( !$c instanceof Element ) {
				$c = $c->nextSibling;
				continue;
			}

			if ( !DOMUtils::hasTypeOf( $c, 'mw:Nowiki' ) ) {
				self::processNowikis( $c );
				$c = $c->nextSibling;
				continue;
			}

			// Replace the nowiki's text node with a combination
			// of content and <br/>s. Take care to deal with
			// entities that are still entity-wrapped (!!).
			$cc = $c->firstChild;
			while ( $cc ) {
				$next = $cc->nextSibling;
				if ( $cc instanceof Text ) {
					$pieces = preg_split( '/\n/', $cc->nodeValue ?? '' );
					$n = count( $pieces );
					$nl = '';
					for ( $i = 0;  $i < $n;  $i++ ) {
						$p = $pieces[$i];
						$c->insertBefore( $doc->createTextNode( $nl . $p ), $cc );
						if ( $i < $n - 1 ) {
							$c->insertBefore( $doc->createElement( 'br' ), $cc );
							$nl = "\n";
						}
					}
					$c->removeChild( $cc );
				}
				$cc = $next;
			}
			$c = $c->nextSibling;
		}
	}
}
