<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

class MigrateTemplateMarkerMetas implements Wt2HtmlDOMProcessor {

	private function migrateFirstChild( Node $firstChild ): bool {
		if ( WTUtils::isTplEndMarkerMeta( $firstChild ) ) {
			return true;
		}

		if ( WTUtils::isTplStartMarkerMeta( $firstChild ) ) {
			'@phan-var Element $firstChild';  // @var Element $firstChild

			$docDataBag = DOMDataUtils::getBag( $firstChild->ownerDocument );
			$about = DOMCompat::getAttribute( $firstChild, 'about' );
			$startDepth = $docDataBag->transclusionMetaTagDepthMap[$about]['start'];
			$endDepth = $docDataBag->transclusionMetaTagDepthMap[$about]['end'];
			return $startDepth > $endDepth;
		}

		return false;
	}

	private function migrateLastChild( Node $lastChild ): bool {
		if ( WTUtils::isTplStartMarkerMeta( $lastChild ) ) {
			return true;
		}

		if ( WTUtils::isTplEndMarkerMeta( $lastChild ) ) {
			'@phan-var Element $lastChild';  // @var Element $lastChild
			$docDataBag = DOMDataUtils::getBag( $lastChild->ownerDocument );
			$about = DOMCompat::getAttribute( $lastChild, 'about' );
			$startDepth = $docDataBag->transclusionMetaTagDepthMap[$about]['start'];
			$endDepth = $docDataBag->transclusionMetaTagDepthMap[$about]['end'];
			return $startDepth < $endDepth;
		}

		return false;
	}

	private function updateDepths( Element $elt ): void {
		// Update depths
		$docDataBag = DOMDataUtils::getBag( $elt->ownerDocument );
		$about = DOMCompat::getAttribute( $elt, 'about' );
		if ( WTUtils::isTplEndMarkerMeta( $elt ) ) {
			// end depth
			$docDataBag->transclusionMetaTagDepthMap[$about]['end']--;
		} else {
			// start depth
			$docDataBag->transclusionMetaTagDepthMap[$about]['start']--;
		}
	}

	/**
	 * The goal of this pass is to assist the WrapTemplates pass
	 * by using some simple heuristics to bring the DOM into a more
	 * canonical form. There is no correctness issue with WrapTemplates
	 * wrapping a wider range of content than what a template generated.
	 * These heuristics can be evolved as needed.
	 *
	 * Given the above considerations, we are going to consider migration
	 * possibilities only where the migration won't lead to additional
	 * untemplated content getting pulled into the template wrapper.
	 *
	 * The simplest heuristics that satisfy this constraint are:
	 * - Only examine first/last child of a node.
	 * - We relax the first/last child constraint by ignoring
	 *   separator nodes (comments, whitespace) but this is
	 *   something worth revisiting in the future.
	 * - Only migrate upwards if the node's start/end tag (barrier)
	 *   comes from zero-width-wikitext.
	 * - If the start meta is the last child OR if the end meta is
	 *   the first child, migrate up.
	 * - If the start meta is the first child OR if the end meta is
	 *   the last child, there is no benefit to migrating the meta tags
	 *   up if both the start and end metas are at the same tree depth.
	 * - In some special cases, it might be possible to migrate
	 *   metas downward rather than upward. Migrating downwards has
	 *   wt2wt corruption implications if done incorrectly. So, we
	 *   aren't considering this possibility right now.
	 *
	 * @param Element|DocumentFragment $node
	 * @param Env $env
	 */
	private function doMigrate( Node $node, Env $env ): void {
		$c = $node->firstChild;
		while ( $c ) {
			$sibling = $c->nextSibling;
			if ( $c->hasChildNodes() ) {
				'@phan-var Element $c';  // @var Element $c
				$this->doMigrate( $c, $env );
			}
			$c = $sibling;
		}

		// No migration out of fragment
		if ( DOMUtils::atTheTop( $node ) ) {
			return;
		}

		// Check if $node is a fostered node
		$fostered = !empty( DOMDataUtils::getDataParsoid( $node )->fostered );

		$firstChild = DiffDOMUtils::firstNonSepChild( $node );
		if ( $firstChild && $this->migrateFirstChild( $firstChild ) ) {
			// We can migrate the meta-tag across this node's start-tag barrier only
			// if that start-tag is zero-width, or auto-inserted.
			$tagWidth = Consts::$WtTagWidths[DOMCompat::nodeName( $node )] ?? null;
			DOMUtils::assertElt( $node );
			if ( ( $tagWidth && $tagWidth[0] === 0 && !WTUtils::isLiteralHTMLNode( $node ) ) ||
				!empty( DOMDataUtils::getDataParsoid( $node )->autoInsertedStart )
			) {
				$sentinel = $firstChild;
				do {
					$firstChild = $node->firstChild;
					$node->parentNode->insertBefore( $firstChild, $node );
					if ( $fostered && $firstChild instanceof Element ) {
						// $firstChild is being migrated out of a fostered node
						// So, mark $lastChild itself fostered!
						DOMDataUtils::getDataParsoid( $firstChild )->fostered = true;
					}
				} while ( $sentinel !== $firstChild );

				$this->updateDepths( $firstChild );
			}
		}

		$lastChild = DiffDOMUtils::lastNonSepChild( $node );
		if ( $lastChild && $this->migrateLastChild( $lastChild ) ) {
			// We can migrate the meta-tag across this node's end-tag barrier only
			// if that end-tag is zero-width, or auto-inserted.
			$tagWidth = Consts::$WtTagWidths[DOMCompat::nodeName( $node )] ?? null;
			DOMUtils::assertElt( $node );
			if ( ( $tagWidth && $tagWidth[1] === 0 &&
				!WTUtils::isLiteralHTMLNode( $node ) ) ||
				( !empty( DOMDataUtils::getDataParsoid( $node )->autoInsertedEnd ) &&
				// Except, don't migrate out of a table since the end meta
				// marker may have been fostered and this is more likely to
				// result in a flipped range that isn't enclosed.
				DOMCompat::nodeName( $node ) !== 'table' )
			) {
				$sentinel = $lastChild;
				do {
					$lastChild = $node->lastChild;
					$node->parentNode->insertBefore( $lastChild, $node->nextSibling );
					if ( $fostered && $lastChild instanceof Element ) {
						// $lastChild is being migrated out of a fostered node
						// So, mark $lastChild itself fostered!
						DOMDataUtils::getDataParsoid( $lastChild )->fostered = true;
					}
				} while ( $sentinel !== $lastChild );

				$this->updateDepths( $lastChild );
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		if ( $root instanceof Element || $root instanceof DocumentFragment ) {
			$this->doMigrate( $root, $env );
		}
	}
}
