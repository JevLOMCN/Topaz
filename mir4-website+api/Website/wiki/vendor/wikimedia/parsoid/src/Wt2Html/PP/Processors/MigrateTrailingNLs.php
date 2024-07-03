<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

class MigrateTrailingNLs implements Wt2HtmlDOMProcessor {
	private static $nodesToMigrateFrom;

	private function nodeEndsLineInWT( Node $node, DataParsoid $dp ): bool {
		// These nodes either end a line in wikitext (tr, li, dd, ol, ul, dl, caption,
		// p) or have implicit closing tags that can leak newlines to those that end a
		// line (th, td)
		//
		// SSS FIXME: Given condition 2, we may not need to check th/td anymore
		// (if we can rely on auto inserted start/end tags being present always).
		self::$nodesToMigrateFrom ??= PHPUtils::makeSet( [
			'pre', 'th', 'td', 'tr', 'li', 'dd', 'ol', 'ul', 'dl', 'caption', 'p'
		] );
		return isset( self::$nodesToMigrateFrom[DOMCompat::nodeName( $node )] ) &&
			!WTUtils::hasLiteralHTMLMarker( $dp );
	}

	private function getTableParent( Node $node ): ?Node {
		$nodeName = DOMCompat::nodeName( $node );
		if ( in_array( $nodeName, [ 'td', 'th' ], true ) ) {
			$node = $node->parentNode;
			$nodeName = DOMCompat::nodeName( $node );
		}
		if ( $nodeName === 'tr' ) {
			$node = $node->parentNode;
			$nodeName = DOMCompat::nodeName( $node );
		}
		if ( in_array( $nodeName, [ 'tbody', 'thead', 'tfoot', 'caption' ], true ) ) {
			$node = $node->parentNode;
			$nodeName = DOMCompat::nodeName( $node );
		}
		return ( $nodeName === 'table' ) ? $node : null;
	}

	/**
	 * We can migrate a newline out of a node if one of the following is true:
	 * (1) The node ends a line in wikitext (=> not a literal html tag)
	 * (2) The node has an auto-closed end-tag (wikitext-generated or literal html tag)
	 * and hasn't been fostered out of a table.
	 * (3) It is the rightmost node in the DOM subtree rooted at a node
	 * that ends a line in wikitext
	 * @param Node $node
	 * @return bool
	 */
	private function canMigrateNLOutOfNode( Node $node ): bool {
		if ( DOMCompat::nodeName( $node ) === 'table' || DOMUtils::atTheTop( $node ) ) {
			return false;
		}

		// Don't allow migration out of a table if the table has had
		// content fostered out of it.
		$tableParent = $this->getTableParent( $node );
		if ( $tableParent && $tableParent->previousSibling instanceof Element ) {
			$previousSibling = $tableParent->previousSibling;
			'@phan-var Element $previousSibling'; // @var Element $previousSibling
			if ( !empty( DOMDataUtils::getDataParsoid( $previousSibling )->fostered ) ) {
				return false;
			}
		}

		DOMUtils::assertElt( $node );
		$dp = DOMDataUtils::getDataParsoid( $node );
		return empty( $dp->fostered ) &&
			( $this->nodeEndsLineInWT( $node, $dp ) ||
				!empty( $dp->autoInsertedEnd ) ||
				( !$node->nextSibling &&
					// FIXME: bug compatibility, previously the end meta caused
					// $node->nextSibling to be true for elements with end tags
					empty( $dp->tmp->endTSR ) &&
					$node->parentNode &&
					$this->canMigrateNLOutOfNode( $node->parentNode ) ) );
	}

	/**
	 * A node has zero wt width if:
	 * - tsr->start == tsr->end
	 * - only has children with zero wt width
	 * @param Element $node
	 * @return bool
	 */
	private function hasZeroWidthWT( Element $node ): bool {
		$tsr = DOMDataUtils::getDataParsoid( $node )->tsr ?? null;
		if ( !$tsr || $tsr->start === null || $tsr->start !== $tsr->end ) {
			return false;
		}

		$c = $node->firstChild;
		while ( $c instanceof Element && $this->hasZeroWidthWT( $c ) ) {
			$c = $c->nextSibling;
		}

		return $c === null;
	}

	public function doMigrateTrailingNLs( Node $elt, Env $env ): void {
		if (
			!( $elt instanceof Element ) &&
			!( $elt instanceof DocumentFragment )
		) {
			return;
		}

		// 1. Process DOM rooted at 'elt' first
		//
		// Process children backward so that a table
		// is processed before its fostered content.
		// See subtle changes in newline migration with this wikitext:
		// "<table>\n<tr> || ||\n<td> a\n</table>"
		// when walking backward vs. forward.
		//
		// Separately, walking backward also lets us ignore
		// newly added children after child (because of
		// migrated newline nodes from child's DOM tree).
		$child = $elt->lastChild;
		while ( $child !== null ) {
			$this->doMigrateTrailingNLs( $child, $env );
			$child = $child->previousSibling;
		}

		// 2. Process 'elt' itself after -- skip literal-HTML nodes
		if ( $this->canMigrateNLOutOfNode( $elt ) ) {
			$firstEltToMigrate = null;
			$migrationBarrier = null;
			$partialContent = false;
			$n = $elt->lastChild;

			// We can migrate trailing newlines across nodes that have zero-wikitext-width.
			while ( $n instanceof Element && $this->hasZeroWidthWT( $n ) ) {
				$migrationBarrier = $n;
				$n = $n->previousSibling;
			}

			$isTdTh = DOMCompat::nodeName( $elt ) === 'td' || DOMCompat::nodeName( $elt ) === 'th';

			// Find nodes that need to be migrated out:
			// - a sequence of comment and newline nodes that is preceded by
			// a non-migratable node (text node with non-white-space content
			// or an element node).
			$foundNL = false;
			$tsrCorrection = 0;
			while ( $n instanceof Text || $n instanceof Comment ) {
				if ( $n instanceof Comment ) {
					if ( $isTdTh ) {
						break;
					}
					$firstEltToMigrate = $n;
					$tsrCorrection += WTUtils::decodedCommentLength( $n );
				} else {
					if ( !$isTdTh && preg_match( '/^[ \t\r\n]*\n[ \t\r\n]*$/D', $n->nodeValue ) ) {
						$foundNL = true;
						$firstEltToMigrate = $n;
						$partialContent = false;
						// all whitespace is moved
						$tsrCorrection += strlen( $n->nodeValue );
					} elseif ( str_ends_with( $n->nodeValue, "\n" ) ) {
						$foundNL = true;
						$firstEltToMigrate = $n;
						$partialContent = true;
						// only newlines moved
						preg_match( '/\n+$/D', $n->nodeValue, $matches );
						$tsrCorrection += strlen( $matches[0] ?? '' );
						break;
					} else {
						break;
					}
				}

				$n = $n->previousSibling;
			}

			if ( $firstEltToMigrate && $foundNL ) {
				$eltParent = $elt->parentNode;
				$insertPosition = $elt->nextSibling;

				$n = $firstEltToMigrate;
				while ( $n !== $migrationBarrier ) {
					$next = $n->nextSibling;
					if ( $partialContent ) {
						$nls = $n->nodeValue;
						$n->nodeValue = preg_replace( '/\n+$/D', '', $n->nodeValue, 1 );
						$nls = substr( $nls, strlen( $n->nodeValue ) );
						$n = $n->ownerDocument->createTextNode( $nls );
						$partialContent = false;
					}
					$eltParent->insertBefore( $n, $insertPosition );
					$n = $next;
				}

				// Adjust tsr of any nodes after migrationBarrier.
				// Ex: zero-width nodes that have valid tsr on them
				// By definition (zero-width), these are synthetic nodes added by Parsoid
				// that aren't present in the original wikitext.
				$n = $migrationBarrier;
				while ( $n ) {
					// TSR is guaranteed to exist and be valid
					// (checked by hasZeroWidthWT above)
					DOMUtils::assertElt( $n );
					$dp = DOMDataUtils::getDataParsoid( $n );
					$dp->tsr = $dp->tsr->offset( -$tsrCorrection );
					$n = $n->nextSibling;
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		$this->doMigrateTrailingNLs( $root, $env );
	}
}
