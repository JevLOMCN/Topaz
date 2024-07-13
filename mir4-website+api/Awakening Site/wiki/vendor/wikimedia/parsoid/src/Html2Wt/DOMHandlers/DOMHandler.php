<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use LogicException;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Html2Wt\WTSUtils;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

/**
 * HTML -> Wikitext serialization relies on walking the DOM and delegating
 * the serialization requests to different DOM nodes.
 *
 * This class represents the interface that various DOM handlers are expected
 * to implement.
 *
 * There is the core 'handle' method that deals with converting the content
 * of the node into wikitext markup.
 *
 * Then there are 4 newline-constraint methods that specify the constraints
 * that need to be satisfied for the markup to be valid. For example, list items
 * should always start on a newline, but can only have a single newline separator.
 * Paragraphs always start on a newline and need at least 2 newlines in wikitext
 * for them to be recognized as paragraphs.
 *
 * Each of the 4 newline-constraint methods (before, after, firstChild, lastChild)
 * return an array with a 'min' and 'max' property. If a property is missing, it
 * means that the dom node doesn't have any newline constraints. Some DOM handlers
 * might therefore choose to implement none, some, or all of these methods.
 *
 * The return values of each of these methods are treated as consraints and the
 * caller will have to resolve potentially conflicting constraints between a
 * pair of nodes (siblings, parent-child). For example, if an after handler of
 * a node wants 1 newline, but the before handler of its sibling wants none.
 *
 * Ideally, there should not be any incompatible constraints, but we haven't
 * actually verified that this is the case. All consraint-hanlding code is in
 * the separators-handling methods.
 */
class DOMHandler {

	/** @var bool */
	private $forceSOL;

	public function __construct( bool $forceSOL = false ) {
		$this->forceSOL = $forceSOL;
	}

	/**
	 * Serialize a DOM node to wikitext.
	 * Serialized wikitext should be returned via $state::emitChunk().
	 * @param Element $node
	 * @param SerializerState $state
	 * @param bool $wrapperUnmodified
	 * @return Node|null The node to continue with (need not be an element always)
	 */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		throw new LogicException( 'Not implemented.' );
	}

	/**
	 * How many newlines should be emitted *before* this node?
	 *
	 * @param Element $node
	 * @param Node $otherNode
	 * @param SerializerState $state
	 * @return array
	 */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		return [];
	}

	/**
	 * How many newlines should be emitted *after* this node?
	 *
	 * @param Element $node
	 * @param Node $otherNode
	 * @param SerializerState $state
	 * @return array
	 */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		return [];
	}

	/**
	 * How many newlines should be emitted before the first child?
	 *
	 * @param Element|DocumentFragment $node
	 * @param Node $otherNode
	 * @param SerializerState $state
	 * @return array
	 */
	public function firstChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [];
	}

	/**
	 * How many newlines should be emitted after the last child?
	 *
	 * @param Element|DocumentFragment $node
	 * @param Node $otherNode
	 * @param SerializerState $state
	 * @return array
	 */
	public function lastChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [];
	}

	/**
	 * Put the serializer in start-of-line mode before it is handled.
	 * All non-newline whitespace found between HTML nodes is stripped
	 * to ensure SOL state is guaranteed.
	 *
	 * @return bool
	 */
	public function forceSOL(): bool {
		return $this->forceSOL;
	}

	/**
	 * List helper: This is a shared *after* newline handler for list items.
	 *
	 * @param Element $node
	 * @param Node $otherNode
	 * @return array An array in the form [ 'min' => <int>, 'max' => <int> ] or an empty array.
	 */
	protected function wtListEOL( Element $node, Node $otherNode ): array {
		if ( !( $otherNode instanceof Element ) || DOMUtils::atTheTop( $otherNode ) ) {
			return [ 'min' => 0, 'max' => 2 ];
		}
		'@phan-var Element $otherNode';/** @var Element $otherNode */

		if ( WTUtils::isFirstEncapsulationWrapperNode( $otherNode ) ) {
			return [ 'min' => DOMUtils::isList( $node ) ? 1 : 0, 'max' => 2 ];
		}

		$nextSibling = DiffDOMUtils::nextNonSepSibling( $node );
		$dp = DOMDataUtils::getDataParsoid( $otherNode );
		if ( $nextSibling === $otherNode && ( $dp->stx ?? null ) === 'html' || isset( $dp->src ) ) {
			return [ 'min' => 0, 'max' => 2 ];
		} elseif ( $nextSibling === $otherNode && DOMUtils::isListOrListItem( $otherNode ) ) {
			if ( DOMUtils::isList( $node ) && DOMCompat::nodeName( $otherNode ) === DOMCompat::nodeName( $node ) ) {
				// Adjacent lists of same type need extra newline
				return [ 'min' => 2, 'max' => 2 ];
			} elseif ( DOMUtils::isListItem( $node )
				|| in_array( DOMCompat::nodeName( $node->parentNode ), [ 'li', 'dd' ], true )
			) {
				// Top-level list
				return [ 'min' => 1, 'max' => 1 ];
			} else {
				return [ 'min' => 1, 'max' => 2 ];
			}
		} elseif ( DOMUtils::isList( $otherNode )
			|| ( $otherNode instanceof Element && ( $dp->stx ?? null ) === 'html' )
		) {
			// last child in ul/ol (the list element is our parent), defer
			// separator constraints to the list.
			return [];
		} elseif (
			DOMUtils::isWikitextBlockNode( $node->parentNode ) &&
			DiffDOMUtils::lastNonSepChild( $node->parentNode ) === $node
		) {
			// A list in a block node (<div>, <td>, etc) doesn't need a trailing empty line
			// if it is the last non-separator child (ex: <div>..</ul></div>)
			return [ 'min' => 1, 'max' => 2 ];
		} elseif ( DOMUtils::isFormattingElt( $otherNode ) ) {
			return [ 'min' => 1, 'max' => 1 ];
		} else {
			return [
				'min' => WTUtils::isNewElt( $node ) && !WTUtils::isMarkerAnnotation( $otherNode )
					? 2 : 1,
				'max' => 2
			];
		}
	}

	/**
	 * List helper: DOM-based list bullet construction.
	 * @param SerializerState $state
	 * @param Element $node
	 * @return string
	 */
	protected function getListBullets( SerializerState $state, Element $node ): string {
		$parentTypes = [
			'ul' => '*',
			'ol' => '#'
		];
		$listTypes = [
			'ul' => '',
			'ol' => '',
			'dl' => '',
			'li' => '',
			'dt' => ';',
			'dd' => ':'
		];

		// For new elements, for prettier wikitext serialization,
		// emit a space after the last bullet (if required)
		$space = $this->getLeadingSpace( $state, $node, ' ' );

		$res = '';
		while ( !DOMUtils::atTheTop( $node ) ) {
			$dp = DOMDataUtils::getDataParsoid( $node );
			$nodeName = DOMCompat::nodeName( $node );
			if ( isset( $listTypes[$nodeName] ) ) {
				if ( $nodeName === 'li' ) {
					$parentNode = $node->parentNode;
					while ( $parentNode && !( isset( $parentTypes[DOMCompat::nodeName( $parentNode )] ) ) ) {
						$parentNode = $parentNode->parentNode;
					}

					if ( $parentNode ) {
						if ( !WTUtils::isLiteralHTMLNode( $parentNode ) ) {
							$res = $parentTypes[DOMCompat::nodeName( $parentNode )] . $res;
						}
					} else {
						$state->getEnv()->log( 'error/html2wt', 'Input DOM is not well-formed.',
							"Top-level <li> found that is not nested in <ol>/<ul>\n LI-node:",
							DOMCompat::getOuterHTML( $node )
						);
					}
				} elseif ( !WTUtils::isLiteralHTMLNode( $node ) ) {
					$res = $listTypes[$nodeName] . $res;
				}
			} elseif ( !WTUtils::isLiteralHTMLNode( $node ) ||
				empty( $dp->autoInsertedStart ) || empty( $dp->autoInsertedEnd )
			) {
				break;
			}

			$node = $node->parentNode;
		}

		// Don't emit a space if we aren't returning any bullets.
		return strlen( $res ) ? $res . $space : '';
	}

	/**
	 * Helper: Newline constraint helper for table nodes
	 * @param Node $node
	 * @param Node $origNode
	 * @return int
	 */
	protected function maxNLsInTable( Node $node, Node $origNode ): int {
		return ( WTUtils::isNewElt( $node ) || WTUtils::isNewElt( $origNode ) ) ? 1 : 2;
	}

	/**
	 * Private helper for serializing table nodes
	 * @param string $symbol
	 * @param ?string $endSymbol
	 * @param SerializerState $state
	 * @param Element $node
	 * @return string
	 */
	private function serializeTableElement(
		string $symbol, ?string $endSymbol, SerializerState $state, Element $node
	): string {
		$token = WTSUtils::mkTagTk( $node );
		$sAttribs = $state->serializer->serializeAttributes( $node, $token );
		if ( $sAttribs !== '' ) {
			// IMPORTANT: use ?? not ?: in the first check because we want to preserve an
			// empty string. Use != '' in the second to avoid treating '0' as empty.
			return $symbol . ' ' . $sAttribs . ( $endSymbol ?? ' |' );
		} else {
			return $symbol . ( $endSymbol != '' ? $endSymbol : '' );
		}
	}

	/**
	 * Helper: Handles content serialization for table nodes
	 * @param string $symbol
	 * @param ?string $endSymbol
	 * @param SerializerState $state
	 * @param Element $node
	 * @param bool $wrapperUnmodified
	 * @return string
	 */
	protected function serializeTableTag(
		string $symbol,
		?string $endSymbol,
		SerializerState $state,
		Element $node,
		bool $wrapperUnmodified
	): string {
		if ( $wrapperUnmodified ) {
			$dsr = DOMDataUtils::getDataParsoid( $node )->dsr;
			return $state->getOrigSrc( $dsr->start, $dsr->innerStart() ) ?? '';
		} else {
			return $this->serializeTableElement( $symbol, $endSymbol, $state, $node );
		}
	}

	/**
	 * Helper: Checks whether syntax information in data-parsoid is valid
	 * in the presence of table edits. For example "|" is no longer valid
	 * table-cell markup if a table cell is added before this cell.
	 *
	 * @param SerializerState $state
	 * @param Element $node
	 * @return bool
	 */
	protected function stxInfoValidForTableCell( SerializerState $state, Element $node ): bool {
		// If row syntax is not set, nothing to worry about
		if ( ( DOMDataUtils::getDataParsoid( $node )->stx ?? null ) !== 'row' ) {
			return true;
		}

		// If we have an identical previous sibling, nothing to worry about
		$prev = DiffDOMUtils::previousNonDeletedSibling( $node );
		return $prev !== null && DOMCompat::nodeName( $prev ) === DOMCompat::nodeName( $node );
	}

	/**
	 * Helper for several DOM handlers: Returns whitespace that needs to be emitted
	 * between the markup for the node and its content (ex: table cells, list items)
	 * based on node state (whether the node is original or new content) and other
	 * state (HTML version, whether selective serialization is enabled or not).
	 * @param SerializerState $state
	 * @param Element $node
	 * @param string $newEltDefault
	 * @return string
	 */
	protected function getLeadingSpace(
		SerializerState $state, Element $node, string $newEltDefault
	): string {
		$space = '';
		if ( WTUtils::isNewElt( $node ) ) {
			$fc = DiffDOMUtils::firstNonDeletedChild( $node );
			// PORT-FIXME are different \s semantics going to be a problem?
			if ( $fc && ( !( $fc instanceof Text ) || !preg_match( '/^\s/', $fc->nodeValue ) ) ) {
				$space = $newEltDefault;
			}
		}
		return $space;
	}

	/**
	 * Helper for several DOM handlers: Returns whitespace that needs to be emitted
	 * between the markup for the node and its next sibling based on node state
	 * (whether the node is original or new content) and other state (HTML version,
	 * whether selective serialization is enabled or not).
	 * @param SerializerState $state
	 * @param Element $node
	 * @param string $newEltDefault
	 * @return string
	 */
	protected function getTrailingSpace(
		SerializerState $state, Element $node, string $newEltDefault
	): string {
		$space = '';
		if ( WTUtils::isNewElt( $node ) ) {
			$lc = DiffDOMUtils::lastNonDeletedChild( $node );
			// PORT-FIXME are different \s semantics going to be a problem?
			if ( $lc && ( !( $lc instanceof Text ) || !preg_match( '/\s$/D', $lc->nodeValue ) ) ) {
				$space = $newEltDefault;
			}
		}
		return $space;
	}

	/**
	 * Helper: Is this node auto-inserted by the HTML5 tree-builder
	 * during wt->html?
	 * @param Node $node
	 * @return bool
	 */
	protected function isBuilderInsertedElt( Node $node ): bool {
		if ( !( $node instanceof Element ) ) {
			return false;
		}
		'@phan-var Element $node';/** @var Element $node */
		$dp = DOMDataUtils::getDataParsoid( $node );
		return !empty( $dp->autoInsertedStart ) && !empty( $dp->autoInsertedEnd );
	}

	/**
	 * Uneditable forms wrapped with mw:Placeholder tags OR unedited nowikis
	 * N.B. We no longer emit self-closed nowikis as placeholders, so remove this
	 * once all our stored content is updated.
	 * @param Element $node
	 * @param SerializerState $state
	 */
	protected function emitPlaceholderSrc( Element $node, SerializerState $state ) {
		$dp = DOMDataUtils::getDataParsoid( $node );
		if ( preg_match( '!<nowiki\s*/>!', $dp->src ?? '' ) ) {
			$state->hasSelfClosingNowikis = true;
		}
		// FIXME: Should this also check for tabs and plain space
		// chars interspersed with newlines?
		if ( preg_match( '/^\n+$/D', $dp->src ?? '' ) ) {
			$state->appendSep( $dp->src );
		} else {
			$state->serializer->emitWikitext( $dp->src, $node );
		}
	}

}
