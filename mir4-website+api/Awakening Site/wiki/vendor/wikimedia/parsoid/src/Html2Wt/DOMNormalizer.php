<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;

/*
 * Tag minimization
 * ----------------
 * Minimize a pair of tags in the dom tree rooted at node.
 *
 * This function merges adjacent nodes of the same type
 * and swaps nodes where possible to enable further merging.
 *
 * See examples below:
 *
 * 1. <b>X</b><b>Y</b>
 *    ==> <b>XY</b>
 *
 * 2. <i>A</i><b><i>X</i></b><b><i>Y</i></b><i>Z</i>
 *    ==> <i>A<b>XY</b>Z</i>
 *
 * 3. <a href="Football">Foot</a><a href="Football">ball</a>
 *    ==> <a href="Football">Football</a>
 */

/**
 * DOM normalization.
 *
 * DOM normalizations are performed after DOMDiff is run.
 * So, normalization routines should update diff markers appropriately.
 */
class DOMNormalizer {

	private const IGNORABLE_ATTRS = [
		'data-parsoid', 'id', 'title', DOMDataUtils::DATA_OBJECT_ATTR_NAME
	];
	private const HTML_IGNORABLE_ATTRS = [ 'data-parsoid', DOMDataUtils::DATA_OBJECT_ATTR_NAME ];

	private static $specializedAttribHandlers;

	/** @var bool */
	private $inInsertedContent;

	/** @var SerializerState */
	private $state;

	public function __construct( SerializerState $state ) {
		if ( !self::$specializedAttribHandlers ) {
			self::$specializedAttribHandlers = [
				'data-mw' => static function ( $nodeA, $dmwA, $nodeB, $dmwB ) {
					return $dmwA == $dmwB;
				}
			];
		}

		$this->state = $state;

		$this->inInsertedContent = false;
	}

	private static function similar( Node $a, Node $b ): bool {
		if ( DOMCompat::nodeName( $a ) === 'a' ) {
			// FIXME: Similar to 1ce6a98, DiffDOMUtils::nextNonDeletedSibling is being
			// used in this file where maybe DiffDOMUtils::nextNonSepSibling belongs.
			return $a instanceof Element && $b instanceof Element &&
				DiffUtils::attribsEquals( $a, $b, self::IGNORABLE_ATTRS, self::$specializedAttribHandlers );
		} else {
			$aIsHtml = WTUtils::isLiteralHTMLNode( $a );
			$bIsHtml = WTUtils::isLiteralHTMLNode( $b );
			// TODO (Anomie)
			// It looks like $ignorableAttrs is only used when $aIsHtml is true.
			// Or is that the fixme referred to in the comment below?
			$ignorableAttrs = $aIsHtml ? self::HTML_IGNORABLE_ATTRS : self::IGNORABLE_ATTRS;

			// FIXME: For non-HTML I/B tags, we seem to be dropping all attributes
			// in our tag handlers (which seems like a bug). Till that is fixed,
			// we'll preserve existing functionality here.
			return ( !$aIsHtml && !$bIsHtml ) ||
				( $aIsHtml && $bIsHtml &&
					$a instanceof Element && $b instanceof Element &&
					DiffUtils::attribsEquals( $a, $b, $ignorableAttrs, self::$specializedAttribHandlers ) );
		}
	}

	/**
	 * Can a and b be merged into a single node?
	 * @param Node $a
	 * @param Node $b
	 * @return bool
	 */
	private static function mergable( Node $a, Node $b ): bool {
		return DOMCompat::nodeName( $a ) === DOMCompat::nodeName( $b ) && self::similar( $a, $b );
	}

	/**
	 * Can a and b be combined into a single node
	 * if we swap a and a.firstChild?
	 *
	 * For example: A='<b><i>x</i></b>' b='<i>y</i>' => '<i><b>x</b>y</i>'.
	 * @param Node $a
	 * @param Node $b
	 * @return bool
	 */
	private static function swappable( Node $a, Node $b ): bool {
		return DiffDOMUtils::numNonDeletedChildNodes( $a ) === 1
			&& self::similar( $a, DiffDOMUtils::firstNonDeletedChild( $a ) )
			&& self::mergable( DiffDOMUtils::firstNonDeletedChild( $a ), $b );
	}

	private static function firstChild( Node $node, bool $rtl ): ?Node {
		return $rtl ? DiffDOMUtils::lastNonDeletedChild( $node ) : DiffDOMUtils::firstNonDeletedChild( $node );
	}

	private function isInsertedContent( Node $node ): bool {
		while ( true ) {
			if ( DiffUtils::hasInsertedDiffMark( $node ) ) {
				return true;
			}
			if ( DOMUtils::atTheTop( $node ) ) {
				return false;
			}
			$node = $node->parentNode;
		}
	}

	private function rewriteablePair( Node $a, Node $b ): bool {
		if ( isset( Consts::$WTQuoteTags[DOMCompat::nodeName( $a )] ) ) {
			// For <i>/<b> pair, we need not check whether the node being transformed
			// are new / edited, etc. since these minimization scenarios can
			// never show up in HTML that came from parsed wikitext.
			//
			// <i>..</i><i>..</i> can never show up without a <nowiki/> in between.
			// Similarly for <b>..</b><b>..</b> and <b><i>..</i></b><i>..</i>.
			//
			// This is because a sequence of 4 quotes is not parsed as ..</i><i>..
			// Neither is a sequence of 7 quotes parsed as ..</i></b><i>..
			//
			// So, if we see a minimizable pair of nodes, it is because the HTML
			// didn't originate from wikitext OR the HTML has been subsequently edited.
			// In both cases, we want to transform the DOM.

			return isset( Consts::$WTQuoteTags[DOMCompat::nodeName( $b )] );
		} elseif ( DOMCompat::nodeName( $a ) === 'a' ) {
			// For <a> tags, we require at least one of the two tags
			// to be a newly created element.
			return DOMCompat::nodeName( $b ) === 'a' && ( WTUtils::isNewElt( $a ) || WTUtils::isNewElt( $b ) );
		}
		return false;
	}

	public function addDiffMarks( Node $node, string $mark, bool $dontRecurse = false ): void {
		if ( !$this->state->selserMode || DiffUtils::hasDiffMark( $node, $mark ) ) {
			return;
		}

		// Don't introduce nested inserted markers
		if ( $this->inInsertedContent && $mark === DiffMarkers::INSERTED ) {
			return;
		}

		$env = $this->state->getEnv();

		// Newly added elements don't need diff marks
		if ( !WTUtils::isNewElt( $node ) ) {
			DiffUtils::addDiffMark( $node, $env, $mark );
			if ( $mark === DiffMarkers::INSERTED || $mark === DiffMarkers::DELETED ) {
				DiffUtils::addDiffMark( $node->parentNode, $env, DiffMarkers::CHILDREN_CHANGED );
			}
		}

		if ( $dontRecurse ) {
			return;
		}

		// Walk up the subtree and add 'subtree-changed' markers
		$node = $node->parentNode;
		while ( $node instanceof Element && !DOMUtils::atTheTop( $node ) ) {
			if ( DiffUtils::hasDiffMark( $node, DiffMarkers::SUBTREE_CHANGED ) ) {
				return;
			}
			if ( !WTUtils::isNewElt( $node ) ) {
				DiffUtils::addDiffMark( $node, $env, DiffMarkers::SUBTREE_CHANGED );
			}
			$node = $node->parentNode;
		}
	}

	/**
	 * Transfer all of b's children to a and delete b.
	 * @param Element $a
	 * @param Element $b
	 * @return Element
	 */
	public function merge( Element $a, Element $b ): Element {
		$sentinel = $b->firstChild;

		// Migrate any intermediate nodes (usually 0 / 1 diff markers)
		// present between a and b to a
		$next = $a->nextSibling;
		if ( $next !== $b ) {
			$a->appendChild( $next );
		}

		// The real work of merging
		DOMUtils::migrateChildren( $b, $a );
		$b->parentNode->removeChild( $b );

		// Normalize the node to merge any adjacent text nodes
		DOMCompat::normalize( $a );

		// Update diff markers
		$this->addDiffMarks( $a->parentNode, DiffMarkers::CHILDREN_CHANGED ); // $b was removed
		$this->addDiffMarks( $a, DiffMarkers::CHILDREN_CHANGED ); // $a got more children
		if ( !DOMUtils::isRemoved( $sentinel ) ) {
			// Nodes starting at 'sentinal' were inserted into 'a'
			// b, which was a's sibling was deleted
			// Only addDiffMarks to sentinel, if it is still part of the dom
			// (and hasn't been deleted by the call to a.normalize() )
			if ( $sentinel->parentNode ) {
				$this->addDiffMarks( $sentinel, DiffMarkers::MOVED, true );
			}
		}
		if ( $a->nextSibling ) {
			// FIXME: Hmm .. there is an API hole here
			// about ability to add markers after last child
			$this->addDiffMarks( $a->nextSibling, DiffMarkers::MOVED, true );
		}

		return $a;
	}

	/**
	 * b is a's sole non-deleted child.  Switch them around.
	 * @param Element $a
	 * @param Element $b
	 * @return Element
	 */
	public function swap( Element $a, Element $b ): Element {
		DOMUtils::migrateChildren( $b, $a );
		$a->parentNode->insertBefore( $b, $a );
		$b->appendChild( $a );

		// Mark a's subtree, a, and b as all having moved
		if ( $a->firstChild !== null ) {
			$this->addDiffMarks( $a->firstChild, DiffMarkers::MOVED, true );
		}
		$this->addDiffMarks( $a, DiffMarkers::MOVED, true );
		$this->addDiffMarks( $b, DiffMarkers::MOVED, true );
		$this->addDiffMarks( $a, DiffMarkers::CHILDREN_CHANGED, true );
		$this->addDiffMarks( $b, DiffMarkers::CHILDREN_CHANGED, true );
		$this->addDiffMarks( $b->parentNode, DiffMarkers::CHILDREN_CHANGED );

		return $b;
	}

	public function hoistLinks( Element $node, bool $rtl ): void {
		$sibling = self::firstChild( $node, $rtl );
		$hasHoistableContent = false;

		while ( $sibling ) {
			$next = $rtl
				? DiffDOMUtils::previousNonDeletedSibling( $sibling )
				: DiffDOMUtils::nextNonDeletedSibling( $sibling );
			if ( !DiffDOMUtils::isContentNode( $sibling ) ) {
				// Nothing to do, continue.
			} elseif ( !WTUtils::isRenderingTransparentNode( $sibling ) ||
				WTUtils::isEncapsulationWrapper( $sibling )
			) {
				// Don't venture into templated content
				break;
			} else {
				$hasHoistableContent = true;
			}
			$sibling = $next;
		}

		if ( $hasHoistableContent ) {
			// soak up all the non-content nodes (exclude sibling)
			$move = self::firstChild( $node, $rtl );
			$firstNode = $move;
			while ( $move !== $sibling ) {
				$refnode = $rtl ? DiffDOMUtils::nextNonDeletedSibling( $node ) : $node;
				$node->parentNode->insertBefore( $move, $refnode );
				$move = self::firstChild( $node, $rtl );
			}

			// and drop any leading whitespace
			if ( $sibling instanceof Text ) {
				$sibling->nodeValue = $rtl ? rtrim( $sibling->nodeValue ) : ltrim( $sibling->nodeValue );
			}

			// Update diff markers
			$this->addDiffMarks( $firstNode, DiffMarkers::MOVED, true );
			if ( $sibling ) {
				$this->addDiffMarks( $sibling, DiffMarkers::MOVED, true );
			}
			$this->addDiffMarks( $node, DiffMarkers::CHILDREN_CHANGED, true );
			$this->addDiffMarks( $node->parentNode, DiffMarkers::CHILDREN_CHANGED );
		}
	}

	public function stripIfEmpty( Element $node ): ?Node {
		$next = DiffDOMUtils::nextNonDeletedSibling( $node );
		$dp = DOMDataUtils::getDataParsoid( $node );
		$autoInserted = isset( $dp->autoInsertedStart ) || isset( $dp->autoInsertedEnd );

		$strippable =
			DiffDOMUtils::nodeEssentiallyEmpty( $node, false );
			// Ex: "<a..>..</a><b></b>bar"
			// From [[Foo]]<b/>bar usage found on some dewiki pages.
			// FIXME: Should we enable this?
			// !( false /* used to be rt-test mode */ && ( $dp->stx ?? null ) === 'html' );

		if ( $strippable ) {
			// Update diff markers (before the deletion)
			$this->addDiffMarks( $node, DiffMarkers::DELETED, true );
			$node->parentNode->removeChild( $node );
			return $next;
		} else {
			return $node;
		}
	}

	public function moveTrailingSpacesOut( Node $node ): void {
		$next = DiffDOMUtils::nextNonDeletedSibling( $node );
		$last = DiffDOMUtils::lastNonDeletedChild( $node );
		$matches = null;
		if ( $last instanceof Text &&
			preg_match( '/\s+$/D', $last->nodeValue, $matches ) > 0
		) {
			$trailing = $matches[0];
			$last->nodeValue = substr( $last->nodeValue, 0, -strlen( $trailing ) );
			// Try to be a little smarter and drop the spaces if possible.
			if ( $next && ( !( $next instanceof Text ) || !preg_match( '/^\s+/', $next->nodeValue ) ) ) {
				if ( !( $next instanceof Text ) ) {
					$txt = $node->ownerDocument->createTextNode( '' );
					$node->parentNode->insertBefore( $txt, $next );
					$next = $txt;
				}
				$next->nodeValue = $trailing . $next->nodeValue;
				// next (a text node) is new / had new content added to it
				$this->addDiffMarks( $next, DiffMarkers::INSERTED, true );
			}
			$this->addDiffMarks( $last, DiffMarkers::INSERTED, true );
			$this->addDiffMarks( $node->parentNode, DiffMarkers::CHILDREN_CHANGED );
		}
	}

	public function stripBRs( Element $node ): void {
		$child = $node->firstChild;
		while ( $child ) {
			$next = $child->nextSibling;
			if ( DOMCompat::nodeName( $child ) === 'br' ) {
				// replace <br/> with a single space
				$node->removeChild( $child );
				$node->insertBefore( $node->ownerDocument->createTextNode( ' ' ), $next );
			} elseif ( $child instanceof Element ) {
				$this->stripBRs( $child );
			}
			$child = $next;
		}
	}

	/**
	 * FIXME see
	 * https://gerrit.wikimedia.org/r/#/c/mediawiki/services/parsoid/+/500975/7/src/Html2Wt/DOMNormalizer.php@423
	 * @param Node $node
	 * @return Node|null
	 */
	public function stripBidiCharsAroundCategories( Node $node ): ?Node {
		if ( !( $node instanceof Text ) ||
			( !WTUtils::isCategoryLink( $node->previousSibling ) &&
				!WTUtils::isCategoryLink( $node->nextSibling ) )
		) {
			// Not a text node and not adjacent to a category link
			return $node;
		}

		$next = $node->nextSibling;
		if ( !$next || WTUtils::isCategoryLink( $next ) ) {
			// The following can leave behind an empty text node.
			$oldLength = strlen( $node->nodeValue );
			$node->nodeValue = preg_replace(
				'/([\x{200e}\x{200f}]+\n)?[\x{200e}\x{200f}]+$/uD',
				'',
				$node->nodeValue
			);
			$newLength = strlen( $node->nodeValue );

			if ( $oldLength !== $newLength ) {
				// Log changes for editors benefit
				$this->state->getEnv()->log( 'warn/html2wt/bidi',
					'LRM/RLM unicode chars stripped around categories'
				);
			}

			if ( $newLength === 0 ) {
				// Remove empty text nodes to keep DOM in normalized form
				$ret = DiffDOMUtils::nextNonDeletedSibling( $node );
				$node->parentNode->removeChild( $node );
				$this->addDiffMarks( $node, DiffMarkers::DELETED );
				return $ret;
			}

			// Treat modified node as having been newly inserted
			$this->addDiffMarks( $node, DiffMarkers::INSERTED );
		}
		return $node;
	}

	/**
	 * When an A tag is encountered, if there are format tags inside, move them outside
	 * Also merge a single sibling A tag that is mergable
	 * The link href and text must match for this normalization to take effect
	 *
	 * @param Element $node
	 * @return Node|null
	 */
	public function moveFormatTagOutsideATag( Element $node ): ?Node {
		if ( DOMCompat::nodeName( $node ) !== 'a' ) {
			return $node;
		}
		$sibling = DiffDOMUtils::nextNonDeletedSibling( $node );
		if ( $sibling ) {
			$this->normalizeSiblingPair( $node, $sibling );
		}

		$firstChild = DiffDOMUtils::firstNonDeletedChild( $node );
		$fcNextSibling = null;
		if ( $firstChild ) {
			$fcNextSibling = DiffDOMUtils::nextNonDeletedSibling( $firstChild );
		}

		if ( !$node->hasAttribute( 'href' ) ) {
			return $node;
		}
		$nodeHref = DOMCompat::getAttribute( $node, 'href' ) ?? '';

		// If there are no tags to swap, we are done
		if ( $firstChild instanceof Element &&
			// No reordering possible with multiple children
			$fcNextSibling === null &&
			// Do not normalize WikiLinks with these attributes
			!$firstChild->hasAttribute( 'color' ) &&
			!$firstChild->hasAttribute( 'style' ) &&
			!$firstChild->hasAttribute( 'class' ) &&
			// Compare textContent to the href, noting that this matching doesn't handle all
			// possible simple-wiki-link scenarios that isSimpleWikiLink in link handler tackles
			$node->textContent === PHPUtils::stripPrefix( $nodeHref, './' )
		) {
			for (
				$child = DiffDOMUtils::firstNonDeletedChild( $node );
				DOMUtils::isFormattingElt( $child );
				$child = DiffDOMUtils::firstNonDeletedChild( $node )
			) {
				'@phan-var Element $child'; // @var Element $child
				$this->swap( $node, $child );
			}
			return $firstChild;
		}

		return $node;
	}

	/**
	 * Wikitext normalizations implemented right now:
	 *
	 * 1. Tag minimization (I/B tags) in normalizeSiblingPair
	 * 2. Strip empty headings and style tags
	 * 3. Force SOL transparent links to serialize before/after heading
	 * 4. Trailing spaces are migrated out of links
	 * 5. Space is added before escapable prefixes in table cells
	 * 6. Strip <br/> from headings
	 * 7. Strip bidi chars around categories
	 * 8. When an A tag is encountered, if there are format tags inside, move them outside
	 *
	 * The return value from this function should respect the
	 * following contract:
	 * - if input node is unmodified, return it.
	 * - if input node is modified, return the new node
	 *   that it transforms into.
	 * If you return a node other than this, normalizations may not
	 * apply cleanly and may be skipped.
	 *
	 * @param Node $node
	 * @return Node|null the normalized node
	 */
	public function normalizeNode( Node $node ): ?Node {
		$dp = null;
		$nodeName = DOMCompat::nodeName( $node );
		if ( $nodeName === 'th' || $nodeName === 'td' ) {
			'@phan-var Element $node'; // @var Element $node
			$dp = DOMDataUtils::getDataParsoid( $node );
			// Table cells (td/th) previously used the stx_v flag for single-row syntax.
			// Newer code uses stx flag since that is used everywhere else.
			// While we still have old HTML in cache / storage, accept
			// the stx_v flag as well.
			// TODO: We are at html version 1.5.0 now. Once storage
			// no longer has version 1.5.0 content, we can get rid of
			// this b/c code.
			if ( isset( $dp->stx_v ) ) {
				// HTML (stx='html') elements will not have the stx_v flag set
				// since the single-row syntax only applies to native-wikitext.
				// So, we can safely override it here.
				$dp->stx = $dp->stx_v;
			}
		}

		$next = null;

		if ( $this->state->getEnv()->getSiteConfig()->scrubBidiChars() ) {
			// Strip bidirectional chars around categories
			// Note that this is being done everywhere,
			// not just in selser mode
			$next = $this->stripBidiCharsAroundCategories( $node );
			if ( $next !== $node ) {
				return $next;
			}
		}

		// Skip unmodified content
		if ( $this->state->selserMode && !DOMUtils::atTheTop( $node ) &&
			!$this->inInsertedContent &&
			!DiffUtils::hasDiffMarkers( $node ) &&
			// If orig-src is not valid, this in effect becomes
			// an edited node and needs normalizations applied to it.
			WTSUtils::origSrcValidInEditedContext( $this->state, $node )
		) {
			return $node;
		}

		// Headings
		if ( DOMUtils::isHeading( $node ) ) {
			'@phan-var Element $node'; // @var Element $node
			$this->hoistLinks( $node, false );
			$this->hoistLinks( $node, true );
			$this->stripBRs( $node );

			return $this->stripIfEmpty( $node );

			// Quote tags
		} elseif ( isset( Consts::$WTQuoteTags[$nodeName] ) ) {
			return $this->stripIfEmpty( $node );

			// Anchors
		} elseif ( $nodeName === 'a' ) {
			'@phan-var Element $node'; // @var Element $node
			$next = DiffDOMUtils::nextNonDeletedSibling( $node );
			// We could have checked for !mw:ExtLink but in
			// the case of links without any annotations,
			// the positive test is semantically safer than the
			// negative test.
			if ( DOMUtils::hasRel( $node, 'mw:WikiLink' ) &&
				$this->stripIfEmpty( $node ) !== $node
			) {
				return $next;
			}
			$this->moveTrailingSpacesOut( $node );

			return $this->moveFormatTagOutsideATag( $node );

			// Table cells
		} elseif ( $nodeName === 'td' ) {
			'@phan-var Element $node'; // @var Element $node
			$dp = DOMDataUtils::getDataParsoid( $node );
			// * HTML <td>s won't have escapable prefixes
			// * First cell should always be checked for escapable prefixes
			// * Second and later cells in a wikitext td row (with stx='row' flag)
			// won't have escapable prefixes.
			$stx = $dp->stx ?? null;
			if ( $stx === 'html' ||
				( DiffDOMUtils::firstNonSepChild( $node->parentNode ) !== $node && $stx === 'row' ) ) {
				return $node;
			}

			$first = DiffDOMUtils::firstNonDeletedChild( $node );
			// Emit a space before escapable prefix
			// This is preferable to serializing with a nowiki.
			if ( $first instanceof Text && strspn( $first->nodeValue, '-+}', 0, 1 ) ) {
				$first->nodeValue = ' ' . $first->nodeValue;
				$this->addDiffMarks( $first, DiffMarkers::INSERTED, true );
			}

			return $node;

			// Font tags without any attributes
		} elseif ( $nodeName === 'font' && DOMDataUtils::noAttrs( $node ) ) {
			$next = DiffDOMUtils::nextNonDeletedSibling( $node );
			DOMUtils::migrateChildren( $node, $node->parentNode, $node );
			$node->parentNode->removeChild( $node );

			return $next;
		} elseif ( $node instanceof Element && $nodeName === 'p'
			&& !WTUtils::isLiteralHTMLNode( $node ) ) {
			$next = DiffDOMUtils::nextNonSepSibling( $node );
			// Normalization of <p></p>, <p><br/></p>, <p><meta/></p> and the like to avoid
			// extraneous new lines
			if ( DiffDOMUtils::hasNChildren( $node, 1 ) &&
				WTUtils::isMarkerAnnotation( $node->firstChild )
			) {
				// Converts <p><meta /></p> (where meta is an annotation tag) to <meta /> without
				// the wrapping <p> (that would typically be added by VE) to avoid getting too many
				// newlines.
				$ann = $node->firstChild;
				DOMUtils::migrateChildren( $node, $node->parentNode, $node );
				$node->parentNode->removeChild( $node );
				return $ann;
			} elseif (
				// Don't apply normalization to <p></p> nodes that
				// were generated through deletions or other normalizations.
				// FIXME: This trick fails for non-selser mode since
				// diff markers are only added in selser mode.
				DiffDOMUtils::hasNChildren( $node, 0, true ) &&
				// FIXME: Also, skip if this is the only child.
				// Eliminates spurious test failures in non-selser mode.
				!DiffDOMUtils::hasNChildren( $node->parentNode, 1 )
			) {
				// T184755: Convert sequences of <p></p> nodes to sequences of
				// <br/>, <p><br/>..other content..</p>, <p><br/><p/> to ensure
				// they serialize to as many newlines as the count of <p></p> nodes.
				// Also handles <p><meta/></p> case for annotations.
				if ( $next && DOMCompat::nodeName( $next ) === 'p' &&
					!WTUtils::isLiteralHTMLNode( $next ) ) {
					// Replace 'node' (<p></p>) with a <br/> and make it the
					// first child of 'next' (<p>..</p>). If 'next' was actually
					// a <p></p> (i.e. empty), 'next' becomes <p><br/></p>
					// which will serialize to 2 newlines.
					$br = $node->ownerDocument->createElement( 'br' );
					$next->insertBefore( $br, $next->firstChild );

					// Avoid nested insertion markers
					if ( !$this->isInsertedContent( $next ) ) {
						$this->addDiffMarks( $br, DiffMarkers::INSERTED );
					}

					// Delete node
					$this->addDiffMarks( $node->parentNode, DiffMarkers::DELETED );
					$node->parentNode->removeChild( $node );
				}
			} else {
				// We cannot merge the <br/> with 'next' because
				// it is not a <p>..</p>.
			}
			return $next;
		}
		// Default
		return $node;
	}

	public function normalizeSiblingPair( Node $a, Node $b ): Node {
		if ( !$this->rewriteablePair( $a, $b ) ) {
			return $b;
		}

		// Since 'a' and 'b' make a rewriteable tag-pair, we are good to go.
		if ( self::mergable( $a, $b ) ) {
			'@phan-var Element $a'; // @var Element $a
			'@phan-var Element $b'; // @var Element $b
			$a = $this->merge( $a, $b );
			// The new a's children have new siblings. So let's look
			// at a again. But their grandkids haven't changed,
			// so we don't need to recurse further.
			$this->processSubtree( $a, false );
			return $a;
		}

		if ( self::swappable( $a, $b ) ) {
			'@phan-var Element $a'; // @var Element $a
			'@phan-var Element $b'; // @var Element $b
			$firstNonDeletedChild = DiffDOMUtils::firstNonDeletedChild( $a );
			'@phan-var Element $firstNonDeletedChild'; // @var Element $firstNonDeletedChild
			$a = $this->merge( $this->swap( $a, $firstNonDeletedChild ), $b );
			// Again, a has new children, but the grandkids have already
			// been minimized.
			$this->processSubtree( $a, false );
			return $a;
		}

		if ( self::swappable( $b, $a ) ) {
			'@phan-var Element $a'; // @var Element $a
			'@phan-var Element $b'; // @var Element $b
			$firstNonDeletedChild = DiffDOMUtils::firstNonDeletedChild( $b );
			'@phan-var Element $firstNonDeletedChild'; // @var Element $firstNonDeletedChild
			$a = $this->merge( $a, $this->swap( $b, $firstNonDeletedChild ) );
			// Again, a has new children, but the grandkids have already
			// been minimized.
			$this->processSubtree( $a, false );
			return $a;
		}

		return $b;
	}

	public function processSubtree( Node $node, bool $recurse ): void {
		// Process the first child outside the loop.
		$a = DiffDOMUtils::firstNonDeletedChild( $node );
		if ( !$a ) {
			return;
		}

		$a = $this->processNode( $a, $recurse );
		while ( $a ) {
			// We need a pair of adjacent siblings for tag minimization.
			$b = DiffDOMUtils::nextNonDeletedSibling( $a );
			if ( !$b ) {
				return;
			}

			// Process subtree rooted at 'b'.
			$b = $this->processNode( $b, $recurse );

			// If we skipped over a bunch of nodes in the middle,
			// we no longer have a pair of adjacent siblings.
			if ( $b && DiffDOMUtils::previousNonDeletedSibling( $b ) === $a ) {
				// Process the pair.
				$a = $this->normalizeSiblingPair( $a, $b );
			} else {
				$a = $b;
			}
		}
	}

	public function processNode( Node $node, bool $recurse ): ?Node {
		// Normalize 'node' and the subtree rooted at 'node'
		// recurse = true  => recurse and normalize subtree
		// recurse = false => assume the subtree is already normalized

		// Normalize node till it stabilizes
		$next = null;
		while ( true ) {
			// Skip templated content
			while ( $node && WTUtils::isFirstEncapsulationWrapperNode( $node ) ) {
				$node = WTUtils::skipOverEncapsulatedContent( $node );
			}

			if ( !$node ) {
				return null;
			}

			// Set insertion marker
			$insertedSubtree = DiffUtils::hasInsertedDiffMark( $node );
			if ( $insertedSubtree ) {
				if ( $this->inInsertedContent ) {
					// Dump debugging info
					$options = [ 'storeDiffMark' => true ];
					$dump = ContentUtils::dumpDOM(
						DOMCompat::getBody( $node->ownerDocument ),
						'-- DOM triggering nested inserted dom-diff flags --',
						$options
					);
					$this->state->getEnv()->log( 'error/html2wt/dom',
						"--- Nested inserted dom-diff flags ---\n",
						'Node:',
						$node instanceof Element ? ContentUtils::ppToXML( $node ) : $node->textContent,
						"\nNode's parent:",
						ContentUtils::ppToXML( $node->parentNode ),
						$dump
					);
				}
				// FIXME: If this assert is removed, the above dumping code should
				// either be removed OR fixed up to remove uses of ContentUtils.ppToXML
				Assert::invariant( !$this->inInsertedContent, 'Found nested inserted dom-diff flags!' );
				$this->inInsertedContent = true;
			}

			// Post-order traversal: Process subtree first, and current node after.
			// This lets multiple normalizations take effect cleanly.
			if ( $recurse && $node instanceof Element ) {
				$this->processSubtree( $node, true );
			}

			$next = $this->normalizeNode( $node );

			// Clear insertion marker
			if ( $insertedSubtree ) {
				$this->inInsertedContent = false;
			}

			if ( $next === $node ) {
				return $node;
			} else {
				$node = $next;
			}
		}

		// @phan-suppress-next-line PhanPluginUnreachableCode
		throw new UnreachableException( 'Control should never get here!' );
	}

	/**
	 * @param Element|DocumentFragment $node
	 */
	public function normalize( Node $node ) {
		$this->processNode( $node, true );
	}
}
