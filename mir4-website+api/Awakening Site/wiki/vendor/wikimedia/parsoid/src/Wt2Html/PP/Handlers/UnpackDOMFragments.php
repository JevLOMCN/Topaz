<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Handlers;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMTraverser;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PipelineUtils;
use Wikimedia\Parsoid\Utils\Utils;

class UnpackDOMFragments {

	private static function hasBadNesting(
		Node $targetNode, DocumentFragment $fragment
	): bool {
		// T165098: This is not entirely correct. This is only
		// looking for nesting of identical tags. But, HTML tree building
		// has lot more restrictions on nesting. It seems the simplest way
		// to get all the rules right is to (serialize + reparse).

		// A-tags cannot ever be nested inside each other at any level.
		// This is the one scenario we definitely have to handle right now.
		// We need a generic robust solution for other nesting scenarios.
		//
		// In the general case, we need to be walking up the ancestor chain
		// of $targetNode to see if there is any 'A' tag there. But, since
		// all link text is handled as DOM fragments, if there is any instance
		// where that fragment generates A-tags, we'll always catch it.
		//
		// The only scenario we would miss would be if there were an A-tag whose
		// link text wasn't a fragment but which had an embedded dom-fragment
		// that generated an A-tag. Consider this example below:
		//    "<ext-X>...<div><ext-Y>..</ext-Y></div>..</ext-X>"
		// If ext-X generates an A-tag and ext-Y also generates an A-tag, then
		// when we unpack ext-Y's dom fragment, the simple check below would
		// miss the misnesting.
		return DOMCompat::nodeName( $targetNode ) === 'a' &&
			DOMUtils::treeHasElement( $fragment, 'a' );
	}

	private static function fixAbouts( Env $env, Node $node, array &$aboutIdMap = [] ): void {
		$c = $node->firstChild;
		while ( $c ) {
			if ( $c instanceof Element ) {
				$cAbout = DOMCompat::getAttribute( $c, 'about' );
				if ( $cAbout !== null ) {
					// Update about
					$newAbout = $aboutIdMap[$cAbout] ?? null;
					if ( !$newAbout ) {
						$newAbout = $env->newAboutId();
						$aboutIdMap[$cAbout] = $newAbout;
					}
					$c->setAttribute( 'about', $newAbout );
				}
				self::fixAbouts( $env, $c, $aboutIdMap );
			}
			$c = $c->nextSibling;
		}
	}

	private static function makeChildrenEncapWrappers(
		DocumentFragment $domFragment, string $about
	): void {
		PipelineUtils::addSpanWrappers( $domFragment->childNodes );

		$c = $domFragment->firstChild;
		while ( $c ) {
			/**
			 * We just span wrapped the child nodes, so it's safe to assume
			 * they're all Elements.
			 *
			 * @var Element $c
			 */
			'@phan-var Element $c';
			// FIXME: This unconditionally sets about on children
			// This is currently safe since all of them are nested
			// inside a transclusion, but do we need future-proofing?
			$c->setAttribute( 'about', $about );
			$c = $c->nextSibling;
		}
	}

	private static function markMisnested( Env $env, Element $n, ?int &$newOffset ): void {
		$dp = DOMDataUtils::getDataParsoid( $n );
		if ( $newOffset === null ) {
			// We end up here when $placeholderParent is part of encapsulated content.
			// Till we add logic to prevent that from happening, we need this fallback.
			if ( isset( $dp->dsr->start ) ) {
				$newOffset = $dp->dsr->start;
			}

			// If still null, set to some dummy value that is larger
			// than page size to avoid pointing to something in source.
			// Trying to fetch outside page source returns "".
			if ( $newOffset === null ) {
				$newOffset = strlen( $env->topFrame->getSrcText() ) + 1;
			}
		}
		$dp->dsr = new DomSourceRange( $newOffset, $newOffset, null, null );
		$dp->misnested = true;
	}

	/**
	 * DOMTraverser handler that unpacks DOM fragments which were injected in the
	 * token pipeline.
	 * @param Node $placeholder
	 * @param Env $env
	 * @return bool|Node
	 */
	public static function handler( Node $placeholder, Env $env ) {
		if ( !$placeholder instanceof Element ) {
			return true;
		}

		// Sealed fragments shouldn't make it past this point
		if ( !DOMUtils::hasTypeOf( $placeholder, 'mw:DOMFragment' ) ) {
			return true;
		}

		$placeholderDP = DOMDataUtils::getDataParsoid( $placeholder );
		Assert::invariant( str_starts_with( $placeholderDP->html, 'mwf' ), '' );
		$fragmentDOM = $env->getDOMFragment( $placeholderDP->html );
		$fragmentContent = $fragmentDOM->firstChild;
		$placeholderParent = $placeholder->parentNode;

		// FIXME: What about mw:Param?
		$isTransclusion = DOMUtils::hasTypeOf( $placeholder, 'mw:Transclusion' );
		if ( $isTransclusion ) {
			// Ensure our `firstChild` is an element to add annotation.  At present,
			// we're unlikely to end up with translusion annotations on fragments
			// where span wrapping hasn't occurred (ie. link contents, since that's
			// placed on the anchor itself) but in the future, nowiki spans may be
			// omitted or new uses for dom fragments found.  For now, the test case
			// necessitating this is an edgy link-in-link scenario:
			//   [[Test|{{1x|[[Hmm|Something <sup>strange</sup>]]}}]]
			// A new use of dom fragments is for parser functions returning html
			// (special page transclusions) which don't do span wrapping.
			PipelineUtils::addSpanWrappers( $fragmentDOM->childNodes );
			// Reset `fragmentContent`, since the `firstChild` may have changed in
			// span wrapping.
			$fragmentContent = $fragmentDOM->firstChild;
			DOMUtils::assertElt( $fragmentContent );
			// Transfer typeof, data-mw, and param info
			// about attributes are transferred below.
			DOMDataUtils::setDataMw( $fragmentContent, Utils::clone( DOMDataUtils::getDataMw( $placeholder ) ) );
			DOMUtils::addTypeOf( $fragmentContent, 'mw:Transclusion' );
			DOMDataUtils::getDataParsoid( $fragmentContent )->pi = $placeholderDP->pi ?? null;
		}

		// Update DSR:
		//
		// - Only update DSR for content that came from cache.
		// - For new DOM fragments from this pipeline,
		//   previously-computed DSR is valid.
		// - EXCEPTION: fostered content from tables get their DSR reset
		//   to zero-width.
		// - EXCEPTION: if we just transferred a transclusion marker,
		//   bring along the associated DSR.
		// - FIXME: We seem to also be doing this for new extension content,
		//   which is the only place still using `setDSR`.
		//
		// There is currently no DSR for DOMFragments nested inside
		// transclusion / extension content (extension inside template
		// content etc).
		// FIXME: Is that always the case?  TSR info is stripped from tokens
		// in transclusion but DSR computation happens before template wrapping
		// and seems to sometimes assign DSR to DOMFragments regardless of having
		// not having TSR set.
		// TODO: Make sure that is the only reason for not having a DSR here.
		$placeholderDSR = $placeholderDP->dsr ?? null;
		if ( $placeholderDSR && (
			$placeholderDP->getTempFlag( TempData::SET_DSR ) ||
			$placeholderDP->getTempFlag( TempData::FROM_CACHE ) ||
			!empty( $placeholderDP->fostered ) ||
			$isTransclusion
		) ) {
			DOMUtils::assertElt( $fragmentContent );
			$fragmentDP = DOMDataUtils::getDataParsoid( $fragmentContent );
			if ( $isTransclusion ) {
				// FIXME: An old comment from c28f137 said we just use dsr->start and
				// dsr->end since tag-widths will be incorrect for reuse of template
				// expansions.  The comment was removed in ca9e760.
				$fragmentDP->dsr = new DomSourceRange( $placeholderDSR->start, $placeholderDSR->end, null, null );
			} elseif (
				DOMUtils::matchTypeOf( $fragmentContent, '/^mw:(Nowiki|Extension(\/\S+))$/' ) !== null
			) {
				$fragmentDP->dsr = $placeholderDSR;
			} else { // non-transcluded images
				$fragmentDP->dsr = new DomSourceRange( $placeholderDSR->start, $placeholderDSR->end, 2, 2 );
			}
		}

		if ( $placeholderDP->getTempFlag( TempData::FROM_CACHE ) ) {
			// Replace old about-id with new about-id that is
			// unique to the global page environment object.
			//
			// <figure>s are reused from cache. Note that figure captions
			// can contain multiple independent transclusions. Each one
			// of those individual transclusions should get a new unique
			// about id. Hence a need for an aboutIdMap and the need to
			// walk the entire tree.
			self::fixAbouts( $env, $fragmentDOM );
		}

		// If the fragment wrapper has an about id, it came from template
		// annotating (the wrapper was an about sibling) and should be transferred
		// to top-level nodes after span wrapping.  This should happen regardless
		// of whether we're coming `fromCache` or not.
		// FIXME: Presumably we have a nesting issue here if this is a cached
		// transclusion.
		$about = DOMCompat::getAttribute( $placeholder, 'about' );
		if ( $about !== null ) {
			// Span wrapping may not have happened for the transclusion above if
			// the fragment is not the first encapsulation wrapper node.
			PipelineUtils::addSpanWrappers( $fragmentDOM->childNodes );
			$c = $fragmentDOM->firstChild;
			while ( $c ) {
				DOMUtils::assertElt( $c );
				$c->setAttribute( 'about', $about );
				$c = $c->nextSibling;
			}
		}

		$nextNode = $placeholder->nextSibling;

		if ( self::hasBadNesting( $placeholderParent, $fragmentDOM ) ) {
			$nodeName = DOMCompat::nodeName( $placeholderParent );
			Assert::invariant( $nodeName === 'a', "Unsupported Bad Nesting scenario for $nodeName" );
			/* -----------------------------------------------------------------------
			 * If placeholderParent is an A element and fragmentDOM contains another
			 * A element, we have an invalid nesting of A elements and needs fixing up.
			 *
			 * $doc1: ... $placeholderParent -> [... $placeholder=mw:DOMFragment, ...] ...
			 *
			 * 1. Change doc1:$placeholderParent -> [... "#unique-hash-code", ...] by replacing
			 *    $placeholder with the "#unique-hash-code" text string
			 *
			 * 2. $str = $placeholderParent->str_replace(#unique-hash-code, $placeholderHTML)
			 *    We now have a HTML string with the bad nesting. We will now use the HTML5
			 *    parser to parse this HTML string and give us the fixed up DOM
			 *
			 * 3. ParseHTML(str) to get
			 *    $doc2: [BODY -> [[placeholderParent -> [...], nested-A-tag-from-placeholder, ...]]]
			 *
			 * 4. Replace $placeholderParent (in $doc1) with $doc2->body->childNodes
			 * ----------------------------------------------------------------------- */
			// FIXME: This is not the most robust hashcode function to use here.
			// With a granularity of a second, if replacements aren't done right away,
			// you can get hash conflicts. It is also conceivable that there is a use
			// of a parser function that returns the value of time and that may lead to
			// hashcode conflicts as well.
			$hashCode = (string)time();
			$placeholderParent->replaceChild( $placeholder->ownerDocument->createTextNode( $hashCode ), $placeholder );

			// If placeholderParent has an about, it presumably is nested inside a template
			// Post fixup, its children will surface to the encapsulation wrapper level.
			// So, we have to fix them up so they dont break the encapsulation.
			//
			// Ex: {{1x|[http://foo.com This is [[bad]], very bad]}}
			//
			// In this example, the <a> corresponding to Foo is placeholderParent and has an about.
			// dummyNode is the DOM corresponding to "This is [[bad]], very bad". Post-fixup
			// "[[bad]], very bad" are at encapsulation level and need about ids.
			DOMUtils::assertElt( $placeholderParent ); // satisfy phan
			$about = DOMCompat::getAttribute( $placeholderParent, 'about' );
			if ( $about !== null ) {
				self::makeChildrenEncapWrappers( $fragmentDOM, $about );
			}

			$fragmentHTML = ContentUtils::ppToXML( $fragmentDOM, [
					'innerXML' => true,
					// We just added some span wrappers and we need to keep
					// that tmp info so the unnecessary ones get stripped.
					// Should be fine since tmp was stripped before packing.
					'keepTmp' => true
				]
			);

			$markerNode = $placeholderParent->previousSibling;

			// We rely on HTML5 parser to fixup the bad nesting (see big comment above)
			$placeholderParentHTML = ContentUtils::ppToXML( $placeholderParent );
			$unpackedMisnestedHTML = str_replace( $hashCode, $fragmentHTML, $placeholderParentHTML );
			$unpackedFragment = DOMUtils::parseHTMLToFragment(
				$placeholderParent->ownerDocument, $unpackedMisnestedHTML
			);

			DOMUtils::migrateChildren(
				$unpackedFragment, $placeholderParent->parentNode, $placeholderParent
			);

			// Identify the new link node. All following siblings till placeholderParent
			// are nodes that have been hoisted out of the link.
			// - Add span wrappers where necessary
			// - Load data-attribs
			// - Zero-out DSR

			if ( $markerNode ) {
				$linkNode = $markerNode->nextSibling;
			} else {
				$linkNode = $placeholderParent->parentNode->firstChild;
			}
			PipelineUtils::addSpanWrappers(
				$linkNode->parentNode->childNodes, $linkNode->nextSibling, $placeholderParent );

			$newOffset = null;
			$node = $linkNode;
			while ( $node !== $placeholderParent ) {
				DOMDataUtils::visitAndLoadDataAttribs( $node );

				if ( $node === $linkNode ) {
					$newOffset = DOMDataUtils::getDataParsoid( $linkNode )->dsr->end ?? null;
				} else {
					$dsrFixer = new DOMTraverser();
					$dsrFixer->addHandler( null, static function ( Node $n ) use( $env, &$newOffset ) {
						if ( $n instanceof Element ) {
							self::markMisnested( $env, $n, $newOffset );
						}
						return true;
					} );
					$dsrFixer->traverse( null, $node );
				}

				$node = $node->nextSibling;
			}

			// Set nextNode to the previous-sibling of former placeholderParent (which will get deleted)
			// This will ensure that all nodes will get handled
			$nextNode = $placeholderParent->previousSibling;

			// placeholderParent itself is useless now
			$placeholderParent->parentNode->removeChild( $placeholderParent );
		} else {
			// Preserve fostered flag from DOM fragment
			if ( $fragmentContent instanceof Element ) {
				if ( !empty( $placeholderDP->fostered ) ) {
					$n = $fragmentContent;
					while ( $n ) {
						$dp = DOMDataUtils::getDataParsoid( $n );
						$dp->fostered = true;
						$n = $n->nextSibling;
					}
				}
			}

			// Move the content nodes over and delete the placeholder node
			DOMUtils::migrateChildren( $fragmentDOM, $placeholderParent, $placeholder );
			$placeholderParent->removeChild( $placeholder );

		}

		// Empty out $fragmentDOM since the call below asserts it
		DOMCompat::replaceChildren( $fragmentDOM );
		$env->removeDOMFragment( $placeholderDP->html );

		return $nextNode;
	}
}
