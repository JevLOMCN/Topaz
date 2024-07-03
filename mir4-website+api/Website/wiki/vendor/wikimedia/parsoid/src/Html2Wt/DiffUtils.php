<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use stdClass;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;

class DiffUtils {
	/**
	 * Get a node's diff marker.
	 *
	 * @param Node $node
	 * @return stdClass|null
	 */
	public static function getDiffMark( Node $node ): ?stdClass {
		if ( !( $node instanceof Element ) ) {
			return null;
		}
		$data = DOMDataUtils::getNodeData( $node );
		return $data->parsoid_diff ?? null;
	}

	/**
	 * Check that the diff markers on the node exist.
	 *
	 * @param Node $node
	 * @return bool
	 */
	public static function hasDiffMarkers( Node $node ): bool {
		return self::getDiffMark( $node ) !== null || self::isDiffMarker( $node );
	}

	public static function hasDiffMark( Node $node, string $mark ): bool {
		// For 'deletion' and 'insertion' markers on non-element nodes,
		// a mw:DiffMarker meta is added
		if ( $mark === DiffMarkers::DELETED || ( $mark === DiffMarkers::INSERTED && !( $node instanceof Element ) ) ) {
			return self::isDiffMarker( $node->previousSibling, $mark );
		} else {
			$diffMark = self::getDiffMark( $node );
			return $diffMark && in_array( $mark, $diffMark->diff, true );
		}
	}

	public static function hasInsertedDiffMark( Node $node ): bool {
		return self::hasDiffMark( $node, DiffMarkers::INSERTED );
	}

	public static function maybeDeletedNode( ?Node $node ): bool {
		return $node instanceof Element && self::isDiffMarker( $node, DiffMarkers::DELETED );
	}

	/**
	 * Is node a mw:DiffMarker node that represents a deleted block node?
	 * This annotation is added by the DOMDiff pass.
	 *
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isDeletedBlockNode( ?Node $node ): bool {
		return $node instanceof Element && self::maybeDeletedNode( $node ) &&
			$node->hasAttribute( 'data-is-block' );
	}

	public static function directChildrenChanged( Node $node ): bool {
		return self::hasDiffMark( $node, DiffMarkers::CHILDREN_CHANGED );
	}

	public static function onlySubtreeChanged( Element $node ): bool {
		$dmark = self::getDiffMark( $node );
		if ( !$dmark ) {
			return false;
		}

		foreach ( $dmark->diff as $mark ) {
			if ( $mark !== DiffMarkers::SUBTREE_CHANGED && $mark !== DiffMarkers::CHILDREN_CHANGED ) {
				return false;
			}
		}

		return true;
	}

	public static function subtreeUnchanged( Element $node ): bool {
		$dmark = self::getDiffMark( $node );
		if ( !$dmark ) {
			return true;
		}

		foreach ( $dmark->diff as $mark ) {
			if ( $mark !== DiffMarkers::MODIFIED_WRAPPER ) {
				return false;
			}
		}

		return true;
	}

	public static function addDiffMark( Node $node, Env $env, string $mark ): ?Element {
		static $ignoreableNodeTypes = [ XML_DOCUMENT_NODE, XML_DOCUMENT_TYPE_NODE, XML_DOCUMENT_FRAG_NODE ];

		if ( $mark === DiffMarkers::DELETED || $mark === DiffMarkers::MOVED ) {
			return self::prependTypedMeta( $node, 'mw:DiffMarker/' . $mark );
		} elseif ( $node instanceof Text || $node instanceof Comment ) {
			if ( $mark !== DiffMarkers::INSERTED ) {
				$env->log( 'error', 'BUG! CHANGE-marker for ', $node->nodeType, ' node is: ', $mark );
			}
			return self::prependTypedMeta( $node, 'mw:DiffMarker/' . $mark );
		} elseif ( $node instanceof Element ) {
			self::setDiffMark( $node, $mark );
		} elseif ( !in_array( $node->nodeType, $ignoreableNodeTypes, true ) ) {
			$env->log( 'error', 'Unhandled node type', $node->nodeType, 'in addDiffMark!' );
		}

		return null;
	}

	/**
	 * Set a diff marker on a node.
	 *
	 * @param Node $node
	 * @param string $change
	 */
	private static function setDiffMark( Node $node, string $change ): void {
		if ( !( $node instanceof Element ) ) {
			return;
		}
		$dpd = self::getDiffMark( $node );
		if ( !$dpd ) {
			$dpd = (object)[ // FIXME object or array?
				'diff' => [ $change ]
			];
		} elseif ( !in_array( $change, $dpd->diff, true ) ) {
			$dpd->diff[] = $change;
		}
		DOMDataUtils::getNodeData( $node )->parsoid_diff = $dpd;
	}

	/**
	 * Insert a meta element with the passed-in typeof attribute before a node.
	 *
	 * @param Node $node
	 * @param string $type
	 * @return Element
	 */
	private static function prependTypedMeta( Node $node, string $type ): Element {
		$meta = $node->ownerDocument->createElement( 'meta' );
		DOMUtils::addTypeOf( $meta, $type );
		$node->parentNode->insertBefore( $meta, $node );
		return $meta;
	}

	private static function getAttributes( Element $node, array $ignoreableAttribs ): array {
		$h = DOMUtils::attributes( $node );
		foreach ( $h as $name => $value ) {
			if ( in_array( $name, $ignoreableAttribs, true ) ) {
				unset( $h[$name] );
			}
		}
		// If there's no special attribute handler, we want a straight
		// comparison of these.
		if ( !in_array( 'data-parsoid', $ignoreableAttribs, true ) ) {
			$h['data-parsoid'] = DOMDataUtils::getDataParsoid( $node );
		}
		if ( !in_array( 'data-mw', $ignoreableAttribs, true ) && DOMDataUtils::validDataMw( $node ) ) {
			$h['data-mw'] = DOMDataUtils::getDataMw( $node );
		}
		return $h;
	}

	/**
	 * Attribute equality test.
	 *
	 * @param Element $nodeA
	 * @param Element $nodeB
	 * @param array $ignoreableAttribs
	 * @param array $specializedAttribHandlers
	 * @return bool
	 */
	public static function attribsEquals(
		Element $nodeA, Element $nodeB, array $ignoreableAttribs, array $specializedAttribHandlers
	): bool {
		$hA = self::getAttributes( $nodeA, $ignoreableAttribs );
		$hB = self::getAttributes( $nodeB, $ignoreableAttribs );

		if ( count( $hA ) !== count( $hB ) ) {
			return false;
		}

		$keysA = array_keys( $hA );
		sort( $keysA );
		$keysB = array_keys( $hB );
		sort( $keysB );

		foreach ( $keysA as $i => $k ) {
			if ( $k !== $keysB[$i] ) {
				return false;
			}

			$attribEquals = $specializedAttribHandlers[$k] ?? null;
			if ( $attribEquals ) {
				// Use a specialized compare function, if provided
				if ( !$hA[$k] || !$hB[$k] || !$attribEquals( $nodeA, $hA[$k], $nodeB, $hB[$k] ) ) {
					return false;
				}
			} elseif ( $hA[$k] !== $hB[$k] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check a node to see whether it's a diff marker.
	 *
	 * @param ?Node $node
	 * @param ?string $mark
	 * @return bool
	 */
	public static function isDiffMarker(
		?Node $node, ?string $mark = null
	): bool {
		if ( !$node ) {
			return false;
		}

		if ( $mark ) {
			return DOMUtils::isMarkerMeta( $node, 'mw:DiffMarker/' . $mark );
		} else {
			return DOMCompat::nodeName( $node ) === 'meta' &&
				DOMUtils::matchTypeOf( $node, '#^mw:DiffMarker/#' );
		}
	}
}
