<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

/**
 * A DOM diff helper class.
 *
 * Compares two DOMs and annotates a copy of the passed-in DOM with change
 * information for the selective serializer.
 */
class DOMDiff {

	// These attributes are ignored for equality purposes if they are added to a node.
	private const IGNORE_ATTRIBUTES = [
		// Note that we are explicitly not ignoring data-parsoid even though clients
		// would never modify data-parsoid because SelectiveSerializer is wrapping text
		// nodes in spans and speculatively computes DSR offsets for these span tags
		// which are accurate for original DOM and may be inaccurate for the edited DOM.
		// By diffing data-parsoid which diffs the DSR as well, we ensure we mark such
		// nodes as modified and prevent use of those speculatively computed incorrect
		// DSR values.
		'data-parsoid-diff',
		'about',
		DOMDataUtils::DATA_OBJECT_ATTR_NAME,
	];

	/**
	 * @var Env
	 */
	public $env;

	/** @var ParsoidExtensionAPI */
	public $extApi;

	/**
	 * @var array
	 */
	public $specializedAttribHandlers;

	private function nextNonTemplateSibling( Node $node ): ?Node {
		if ( WTUtils::isEncapsulationWrapper( $node ) ) {
			return WTUtils::skipOverEncapsulatedContent( $node );
		}
		return $node->nextSibling;
	}

	/**
	 * @param mixed ...$args
	 */
	private function debug( ...$args ): void {
		$this->env->log( 'trace/domdiff', ...$args );
	}

	public function __construct( Env $env ) {
		$this->env = $env;
		$this->extApi = new ParsoidExtensionAPI( $env );
		$this->specializedAttribHandlers = [
			'data-mw' => static function ( $nodeA, $dmwA, $nodeB, $dmwB ) {
				return $dmwA == $dmwB;
			},
			'data-parsoid' => static function ( $nodeA, $dpA, $nodeB, $dpB ) {
				return $dpA == $dpB;
			},
			// TODO(T254502): This is added temporarily for backwards
			// compatibility and can be removed when versions up to 2.1.0
			// are no longer stored
			'typeof' => static function ( $nodeA, $valA, $nodeB, $valB ) {
				if ( $valA === $valB ) {
					return true;
				} elseif ( $valA === 'mw:DisplaySpace' ) {
					return $valB === 'mw:DisplaySpace mw:Placeholder';
				} elseif ( $valB === 'mw:DisplaySpace' ) {
					return $valA === 'mw:DisplaySpace mw:Placeholder';
				} else {
					return false;
				}
			}
		];
	}

	/**
	 * Diff two HTML documents, and add / update data-parsoid-diff attributes with
	 * change information.
	 *
	 * @param Element $nodeA
	 * @param Element $nodeB
	 * @return array
	 */
	public function diff( Element $nodeA, Element $nodeB ): array {
		Assert::invariant(
			$nodeA->ownerDocument !== $nodeB->ownerDocument,
			'Expected to be diff\'ing different documents.'
		);

		$this->debug( static function () use( $nodeA, $nodeB ) {
			return "ORIG:\n" .
				DOMCompat::getOuterHTML( $nodeA ) .
				"\nNEW :\n" .
				DOMCompat::getOuterHTML( $nodeB );
		} );

		// The root nodes are equal, call recursive differ
		$foundChange = $this->doDOMDiff( $nodeA, $nodeB );
		return [ 'isEmpty' => !$foundChange ];
	}

	/**
	 * Test if two DOM nodes are equal.
	 *
	 * @param Node $nodeA
	 * @param Node $nodeB
	 * @param bool $deep
	 * @return bool
	 */
	public function treeEquals( Node $nodeA, Node $nodeB, bool $deep ): bool {
		if ( $nodeA->nodeType !== $nodeB->nodeType ) {
			return false;
		} elseif ( $nodeA instanceof Text ) {
			// In the past we've had bugs where we let non-primitive strings
			// leak into our DOM.  Safety first:
			Assert::invariant( $nodeA->nodeValue === (string)$nodeA->nodeValue, '' );
			Assert::invariant( $nodeB->nodeValue === (string)$nodeB->nodeValue, '' );
			// ok, now do the comparison.
			return $nodeA->nodeValue === $nodeB->nodeValue;
		} elseif ( $nodeA instanceof Comment ) {
			return WTUtils::decodeComment( $nodeA->nodeValue ) ===
				WTUtils::decodeComment( $nodeB->nodeValue );
		} elseif ( $nodeA instanceof Element || $nodeA instanceof DocumentFragment ) {
			if ( $nodeA instanceof DocumentFragment ) {
				if ( !( $nodeB instanceof DocumentFragment ) ) {
					return false;
				}
			} else {  // $nodeA instanceof Element
				// Compare node name and attribute length
				if (
					!( $nodeB instanceof Element ) ||
					DOMCompat::nodeName( $nodeA ) !== DOMCompat::nodeName( $nodeB ) ||
					!DiffUtils::attribsEquals(
						$nodeA,
						$nodeB,
						self::IGNORE_ATTRIBUTES,
						$this->specializedAttribHandlers
					)
				) {
					return false;
				}
			}

			// Passed all tests, node itself is equal.
			if ( $deep ) {
				$childA = null;
				$childB = null;
				// Compare # of children, since that's fast.
				// (Avoid de-optimizing DOM by using node#childNodes)
				for ( $childA = $nodeA->firstChild, $childB = $nodeB->firstChild;
					$childA && $childB;
					$childA = $childA->nextSibling, $childB = $childB->nextSibling
				) {
					/* don't look inside children yet, just look at # of children */
				}

				if ( $childA || $childB ) {
					return false; /* nodes have different # of children */
				}
				// Now actually compare the child subtrees
				for ( $childA = $nodeA->firstChild, $childB = $nodeB->firstChild;
					$childA && $childB;
					$childA = $childA->nextSibling, $childB = $childB->nextSibling
				) {
					if ( !$this->treeEquals( $childA, $childB, $deep ) ) {
						return false;
					}
				}
			}

			// Did not find a diff yet, so the trees must be equal.
			return true;
		}
		throw new UnreachableException( 'we shouldn\'t get here' );
	}

	/**
	 * Diff two DOM trees by comparing them node-by-node.
	 *
	 * TODO: Implement something more intelligent like
	 * http://gregory.cobena.free.fr/www/Publications/%5BICDE2002%5D%20XyDiff%20-%20published%20version.pdf,
	 * which uses hash signatures of subtrees to efficiently detect moves /
	 * wrapping.
	 *
	 * Adds / updates a data-parsoid-diff structure with change information.
	 *
	 * Returns true if subtree is changed, false otherwise.
	 *
	 * TODO:
	 * Assume typical CSS white-space, so ignore ws diffs in non-pre content.
	 *
	 * @param Node $baseParentNode
	 * @param Node $newParentNode
	 * @return bool
	 */
	public function doDOMDiff( Node $baseParentNode, Node $newParentNode ): bool {
		// Perform a relaxed version of the recursive treeEquals algorithm that
		// allows for some minor differences and tries to produce a sensible diff
		// marking using heuristics like look-ahead on siblings.
		$baseNode = $baseParentNode->firstChild;
		$newNode = $newParentNode->firstChild;
		$lookaheadNode = null;
		$foundDiffOverall = false;

		while ( $baseNode && $newNode ) {
			$dontAdvanceNewNode = false;
			$this->debugOut( $baseNode, $newNode );
			// shallow check first
			if ( !$this->treeEquals( $baseNode, $newNode, false ) ) {
				$this->debug( '-- not equal --' );
				$savedNewNode = $newNode;
				$foundDiff = false;

				// Some simplistic look-ahead, currently limited to a single level
				// in the DOM.

				// look-ahead in *new* DOM to detect insertions
				if ( DiffDOMUtils::isContentNode( $baseNode ) ) {
					$this->debug( '--lookahead in new dom--' );
					$lookaheadNode = $newNode->nextSibling;
					while ( $lookaheadNode ) {
						$this->debugOut( $baseNode, $lookaheadNode, 'new' );
						if ( DiffDOMUtils::isContentNode( $lookaheadNode ) &&
							$this->treeEquals( $baseNode, $lookaheadNode, true )
						) {
							// mark skipped-over nodes as inserted
							$markNode = $newNode;
							while ( $markNode !== $lookaheadNode ) {
								$this->debug( '--found diff: inserted--' );
								$this->markNode( $markNode, DiffMarkers::INSERTED );
								$markNode = $markNode->nextSibling;
							}
							$foundDiff = true;
							$newNode = $lookaheadNode;
							break;
						}
						$lookaheadNode = self::nextNonTemplateSibling( $lookaheadNode );
					}
				}

				// look-ahead in *base* DOM to detect deletions
				if ( !$foundDiff && DiffDOMUtils::isContentNode( $newNode ) ) {
					$isBlockNode = WTUtils::isBlockNodeWithVisibleWT( $baseNode );
					$this->debug( '--lookahead in old dom--' );
					$lookaheadNode = $baseNode->nextSibling;
					while ( $lookaheadNode ) {
						$this->debugOut( $lookaheadNode, $newNode, 'old' );
						if ( DiffDOMUtils::isContentNode( $lookaheadNode ) &&
							$this->treeEquals( $lookaheadNode, $newNode, true )
						) {
							$this->debug( '--found diff: deleted--' );
							// mark skipped-over nodes as deleted
							$this->markNode( $newNode, DiffMarkers::DELETED, $isBlockNode );
							$baseNode = $lookaheadNode;
							$foundDiff = true;
							break;
						} elseif ( !WTUtils::emitsSolTransparentSingleLineWT( $lookaheadNode ) ) {
							// We only care about the deleted node prior to the one that
							// gets a tree match (but, ignore nodes that show up in wikitext
							// but don't affect sol-state or HTML rendering -- note that
							// whitespace is being ignored, but that whitespace occurs
							// between block nodes).
							$isBlockNode = WTUtils::isBlockNodeWithVisibleWT( $lookaheadNode );
						}
						$lookaheadNode = self::nextNonTemplateSibling( $lookaheadNode );
					}
				}

				if ( !$foundDiff ) {
					if ( !( $savedNewNode instanceof Element ) ) {
						$this->debug( '--found diff: modified text/comment--' );
						$this->markNode(
							$savedNewNode, DiffMarkers::DELETED,
							WTUtils::isBlockNodeWithVisibleWT( $baseNode )
						);
					} elseif ( $baseNode instanceof Element &&
						DOMCompat::nodeName( $savedNewNode ) === DOMCompat::nodeName( $baseNode ) &&
						( DOMDataUtils::getDataParsoid( $savedNewNode )->stx ?? null ) ===
						( DOMDataUtils::getDataParsoid( $baseNode )->stx ?? null )
					) {
						// Identical wrapper-type, but modified.
						// Mark modified-wrapper, and recurse.
						$this->debug( '--found diff: modified-wrapper--' );
						$this->markNode( $savedNewNode, DiffMarkers::MODIFIED_WRAPPER );
						$this->subtreeDiffers( $baseNode, $savedNewNode );
					} else {
						// We now want to compare current newNode with the next baseNode.
						$dontAdvanceNewNode = true;

						// Since we are advancing in an old DOM without advancing
						// in the new DOM, there were deletions. Add a deletion marker
						// since this is important for accurate separator handling in selser.
						$this->markNode(
							$savedNewNode, DiffMarkers::DELETED,
							WTUtils::isBlockNodeWithVisibleWT( $baseNode )
						);
					}
				}

				// Record the fact that direct children changed in the parent node
				$this->debug( '--found diff: children-changed--' );
				$this->markNode( $newParentNode, DiffMarkers::CHILDREN_CHANGED );

				$foundDiffOverall = true;
			} elseif ( $this->subtreeDiffers( $baseNode, $newNode ) ) {
				$foundDiffOverall = true;
			}

			// And move on to the next pair (skipping over template HTML)
			if ( $baseNode && $newNode ) {
				$baseNode = self::nextNonTemplateSibling( $baseNode );
				if ( !$dontAdvanceNewNode ) {
					$newNode = self::nextNonTemplateSibling( $newNode );
				}
			}
		}

		// mark extra new nodes as inserted
		while ( $newNode ) {
			$this->debug( '--found trailing new node: inserted--' );
			$this->markNode( $newNode, DiffMarkers::INSERTED );
			$foundDiffOverall = true;
			$newNode = self::nextNonTemplateSibling( $newNode );
		}

		// If there are extra base nodes, something was deleted. Mark the parent as
		// having lost children for now.
		if ( $baseNode ) {
			$this->debug( '--found trailing base nodes: deleted--' );
			$this->markNode( $newParentNode, DiffMarkers::CHILDREN_CHANGED );
			// SSS FIXME: WTS checks for zero children in a few places
			// That code would have to be upgraded if we emit mw:DiffMarker
			// in this scenario. So, bailing out in this one case for now.
			if ( $newParentNode->hasChildNodes() ) {
				$meta = $newParentNode->ownerDocument->createElement( 'meta' );
				DOMUtils::addTypeOf( $meta, 'mw:DiffMarker/deleted' );
				if ( WTUtils::isBlockNodeWithVisibleWT( $baseNode ) ) {
					$meta->setAttribute( 'data-is-block', 'true' );
				}
				$newParentNode->appendChild( $meta );
			}
			$foundDiffOverall = true;
		}

		return $foundDiffOverall;
	}

	/* ***************************************************
	 * Helpers
	 * ***************************************************/

	/**
	 * @param Node $baseNode
	 * @param Node $newNode
	 * @return bool
	 */
	private function subtreeDiffers( Node $baseNode, Node $newNode ): bool {
		$baseEncapsulated = WTUtils::isEncapsulationWrapper( $baseNode );
		$newEncapsulated = WTUtils::isEncapsulationWrapper( $newNode );

		if ( !$baseEncapsulated && !$newEncapsulated ) {
			$this->debug( '--shallow equal: recursing--' );
			// Recursively diff subtrees if not template-like content
			$subtreeDiffers = $this->doDOMDiff( $baseNode, $newNode );
		} elseif ( $baseEncapsulated && $newEncapsulated ) {
			'@phan-var Element $baseNode';  // @var Element $baseNode
			'@phan-var Element $newNode';  // @var Element $newNode

			$ext = null;

			$baseExtTagName = WTUtils::getExtTagName( $baseNode );
			if ( $baseExtTagName ) {
				$ext = $this->env->getSiteConfig()->getExtTagImpl( $baseExtTagName );
			}

			if ( $ext && ( $baseExtTagName === WTUtils::getExtTagName( $newNode ) ) ) {
				$this->debug( '--diffing extension content--' );
				$subtreeDiffers = $ext->diffHandler(
					$this->extApi, [ $this, 'doDOMDiff' ], $baseNode, $newNode
				);
			} else {
				// Otherwise, for encapsulated content, we don't know about the subtree.
				$subtreeDiffers = false;
			}
		} else {
			// FIXME: Maybe $editNode should be marked as inserted to avoid
			// losing any edits, at the cost of more normalization.
			// $state->inInsertedContent is only set when we're in inserted
			// content, so not sure this is currently doing all that much.
			$subtreeDiffers = true;
		}

		if ( $subtreeDiffers ) {
			$this->debug( '--found diff: subtree-changed--' );
			$this->markNode( $newNode, DiffMarkers::SUBTREE_CHANGED );
		}
		return $subtreeDiffers;
	}

	private function markNode( Node $node, string $mark, bool $blockNodeDeleted = false ): void {
		$meta = DiffUtils::addDiffMark( $node, $this->env, $mark );

		if ( $meta && $blockNodeDeleted ) {
			$meta->setAttribute( 'data-is-block', 'true' );
		}

		if ( $mark === DiffMarkers::DELETED || $mark === DiffMarkers::INSERTED ) {
			$this->markNode( $node->parentNode, DiffMarkers::CHILDREN_CHANGED );
		}

		// Clear out speculatively computed DSR values for data-mw-selser-wrapper nodes
		// since they may be incorrect. This eliminates any inadvertent use of
		// these incorrect values.
		if ( $node instanceof Element && $node->hasAttribute( 'data-mw-selser-wrapper' ) ) {
			DOMDataUtils::getDataParsoid( $node )->dsr = null;
		}
	}

	private function debugOut( Node $nodeA, Node $nodeB, string $laPrefix = '' ): void {
		$prefix = 'trace/domdiff';
		$this->env->log( $prefix,
			static fn () => '--> A' . $laPrefix . ':' .
				( $nodeA instanceof Element
					? DOMCompat::getOuterHTML( $nodeA )
					: PHPUtils::jsonEncode( $nodeA->nodeValue ) )
		);
		$this->env->log( $prefix,
			static fn () => '--> B' . $laPrefix . ':' .
				( $nodeB instanceof Element
					? DOMCompat::getOuterHTML( $nodeB )
					: PHPUtils::jsonEncode( $nodeB->nodeValue ) )
		);
	}
}
