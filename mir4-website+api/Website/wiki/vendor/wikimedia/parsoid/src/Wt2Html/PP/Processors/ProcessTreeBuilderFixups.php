<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\Frame;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

class ProcessTreeBuilderFixups implements Wt2HtmlDOMProcessor {

	private static function removeAutoInsertedEmptyTags( Frame $frame, Node $node ): void {
		$c = $node->firstChild;
		while ( $c !== null ) {
			// FIXME: Encapsulation only happens after this phase, so you'd think
			// we wouldn't encounter any, but the html pre tag inserts extension
			// content directly, rather than passing it through as a fragment for
			// later unpacking.  Same as above.
			if ( WTUtils::isEncapsulationWrapper( $c ) ) {
				$c = WTUtils::skipOverEncapsulatedContent( $c );
				continue;
			}

			if ( $c instanceof Element ) {
				self::removeAutoInsertedEmptyTags( $frame, $c );
				$dp = DOMDataUtils::getDataParsoid( $c );

				// We do this down here for all elements since the quote transformer
				// also marks up elements as auto-inserted and we don't want to be
				// constrained by any conditions.  Further, this pass should happen
				// before paragraph wrapping on the dom, since we don't want this
				// stripping to result in empty paragraphs.

				// Delete empty auto-inserted elements
				if ( !empty( $dp->autoInsertedStart ) && !empty( $dp->autoInsertedEnd ) &&
					( !$c->hasChildNodes() ||
						( DiffDOMUtils::hasNChildren( $c, 1 ) &&
							!( $c->firstChild instanceof Element ) &&
							preg_match( '/^\s*$/D', $c->textContent )
						)
					)
				) {
					$next = $c->nextSibling;
					if ( $c->firstChild ) {
						// migrate the ws out
						$c->parentNode->insertBefore( $c->firstChild, $c );
					}
					$c->parentNode->removeChild( $c );
					$c = $next;
					continue;
				}
			}

			$c = $c->nextSibling;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		self::removeAutoInsertedEmptyTags( $options['frame'], $root );
	}
}
