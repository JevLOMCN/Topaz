<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Handlers;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\DTState;
use Wikimedia\Parsoid\Utils\WTUtils;

class LiFixups {

	private static function getMigrationInfo( Node $c ): array {
		$tplRoot = WTUtils::findFirstEncapsulationWrapperNode( $c );
		if ( $tplRoot !== null ) {
			// Check if everything between tplRoot and c is migratable.
			$prev = $tplRoot->previousSibling;
			while ( $c !== $prev ) {
				if ( !WTUtils::isCategoryLink( $c ) &&
					!( DOMCompat::nodeName( $c ) === 'span' && preg_match( '/^\s*$/D', $c->textContent ) )
				) {
					return [ 'tplRoot' => $tplRoot, 'migratable' => false ];
				}

				$c = $c->previousSibling;
			}
		}

		return [ 'tplRoot' => $tplRoot, 'migratable' => true ];
	}

	private static function findLastMigratableNode( Node $li ): ?Node {
		$sentinel = null;
		$c = DiffDOMUtils::lastNonSepChild( $li );
		// c is known to be a category link.
		// fail fast in parser tests if something changes.
		Assert::invariant( WTUtils::isCategoryLink( $c ), 'c is known to be a category link' );
		while ( $c ) {
			// Handle template units first
			$info = self::getMigrationInfo( $c );
			if ( !$info['migratable'] ) {
				break;
			} elseif ( $info['tplRoot'] !== null ) {
				$c = $info['tplRoot'];
			}

			if ( $c instanceof Text ) {
				// Update sentinel if we hit a newline.
				// We want to migrate these newlines and
				// everything following them out of 'li'.
				if ( preg_match( '/\n\s*$/D', $c->nodeValue ) ) {
					$sentinel = $c;
				}

				// If we didn't hit pure whitespace, we are done!
				if ( !preg_match( '/^\s*$/D', $c->nodeValue ) ) {
					break;
				}
			} elseif ( $c instanceof Comment ) {
				$sentinel = $c;
			} elseif ( !WTUtils::isCategoryLink( $c ) ) {
				// We are done if we hit anything but text
				// or category links.
				break;
			}

			$c = $c->previousSibling;
		}

		return $sentinel;
	}

	/**
	 * Earlier in the parsing pipeline, we suppress all newlines
	 * and other whitespace before categories which causes category
	 * links to be swallowed into preceding paragraphs and list items.
	 *
	 * However, with wikitext like this: `*a\n\n[[Category:Foo]]`, this
	 * could prevent proper roundtripping (because we suppress newlines
	 * when serializing list items). This needs addressing because
	 * this pattern is extremely common (some list at the end of the page
	 * followed by a list of categories for the page).
	 * @param Element $li
	 * @param DTState $state
	 * @return bool
	 */
	public static function migrateTrailingCategories( Element $li, DTState $state ): bool {
		// * Don't bother fixing up template content when processing the full page
		if ( $state->tplInfo ?? null ) {
			return true;
		}

		// If there is migratable content inside a list item
		// (categories preceded by newlines),
		// * migrate it out of the outermost list
		// * and fix up the DSR of list items and list along the rightmost path.
		if ( $li->nextSibling === null && DOMUtils::isList( $li->parentNode ) &&
			WTUtils::isCategoryLink( DiffDOMUtils::lastNonSepChild( $li ) )
		) {

			// Find the outermost list -- content will be moved after it
			$outerList = $li->parentNode;
			while ( DOMUtils::isListItem( $outerList->parentNode ) ) {
				$p = $outerList->parentNode;
				// Bail if we find ourself on a path that is not the rightmost path.
				if ( $p->nextSibling !== null ) {
					return true;
				}
				$outerList = $p->parentNode;
			}

			// Find last migratable node
			$sentinel = self::findLastMigratableNode( $li );
			if ( !$sentinel ) {
				return true;
			}

			// Migrate (and update DSR)
			$c = $li->lastChild;
			$liDsr = DOMDataUtils::getDataParsoid( $li )->dsr ?? null;
			$newEndDsr = -1; // dummy to eliminate useless null checks
			while ( true ) {
				if ( $c instanceof Element ) {
					$dsr = DOMDataUtils::getDataParsoid( $c )->dsr ?? null;
					$newEndDsr = $dsr->start ?? -1;
					$outerList->parentNode->insertBefore( $c, $outerList->nextSibling );
				} elseif ( $c instanceof Text ) {
					if ( preg_match( '/^\s*$/D', $c->nodeValue ) ) {
						$newEndDsr -= strlen( $c->nodeValue );
						$outerList->parentNode->insertBefore( $c, $outerList->nextSibling );
					} else {
						// Split off the newlines into its own node and migrate it
						$nls = $c->nodeValue;
						$c->nodeValue = preg_replace( '/\s+$/D', '', $c->nodeValue, 1 );
						$nls = substr( $nls, strlen( $c->nodeValue ) );
						$nlNode = $c->ownerDocument->createTextNode( $nls );
						$outerList->parentNode->insertBefore( $nlNode, $outerList->nextSibling );
						$newEndDsr -= strlen( $nls );
					}
				} elseif ( $c instanceof Comment ) {
					$newEndDsr -= WTUtils::decodedCommentLength( $c );
					$outerList->parentNode->insertBefore( $c, $outerList->nextSibling );
				}

				if ( $c === $sentinel ) {
					break;
				}

				$c = $li->lastChild;
			}

			// Update DSR of all listitem & list nodes till
			// we hit the outermost list we started with.
			$delta = null;
			if ( $liDsr && $newEndDsr >= 0 ) {
				$delta = $liDsr->end - $newEndDsr;
			}

			// If there is no delta to adjust dsr by, we are done
			if ( !$delta ) {
				return true;
			}

			// Fix DSR along the rightmost path to outerList
			$list = null;
			while ( $outerList !== $list ) {
				$list = $li->parentNode;
				DOMUtils::assertElt( $list );

				$liDp = DOMDataUtils::getDataParsoid( $li );
				if ( !empty( $liDp->dsr ) ) {
					$liDp->dsr->end -= $delta;
				}

				$listDp = DOMDataUtils::getDataParsoid( $list );
				if ( !empty( $listDp->dsr ) ) {
					$listDp->dsr->end -= $delta;
				}
				$li = $list->parentNode;
			}
		}

		return true;
	}
}
