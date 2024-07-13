<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TreeBuilder;

use Wikimedia\Parsoid\DOM\Element as DOMElement;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\RemexHtml\TreeBuilder\Element;
use Wikimedia\RemexHtml\TreeBuilder\RelayTreeHandler;

/**
 * This is a stage inserted between RemexHtml's TreeBuilder and our DOMBuilder
 * subclass. Any code that needs to modify the tree mutation event stream
 * should go here. It's currently used for auto-insert detection.
 */
class TreeMutationRelay extends RelayTreeHandler {
	/** @var Attributes|null */
	private $matchAttribs;

	/** @var int|null */
	private $matchEndLength;

	/** @var bool|null */
	private $matchEndIsHtml;

	/** @var DOMElement|null */
	private $matchedElement;

	public function __construct( DOMBuilder $nextHandler ) {
		parent::__construct( $nextHandler );
	}

	/**
	 * Start watching for a start tag with the given Attributes object.
	 */
	public function matchStartTag( Attributes $attribs ): void {
		$this->matchAttribs = $attribs;
		$this->matchEndLength = null;
		$this->matchEndIsHtml = null;
		$this->matchedElement = null;
	}

	/**
	 * Start watching for an end tag with the given fake source length.
	 *
	 * @param int $sourceLength
	 * @param bool $isHTML $dp->stx=='html', which helps us decide whether to
	 *   set autoInsertedEnd
	 */
	public function matchEndTag( int $sourceLength, bool $isHTML ): void {
		$this->matchAttribs = null;
		$this->matchEndLength = $sourceLength;
		$this->matchEndIsHtml = $isHTML;
		$this->matchedElement = null;
	}

	/**
	 * Stop looking for a matching element
	 */
	public function resetMatch(): void {
		$this->matchAttribs = null;
		$this->matchEndLength = null;
		$this->matchEndIsHtml = null;
		$this->matchedElement = null;
	}

	/**
	 * If an element was matched, return the element object from the DOM.
	 *
	 * @return DOMElement|null A local alias since there are two classes called
	 *   Element here.
	 */
	public function getMatchedElement(): ?DOMElement {
		return $this->matchedElement;
	}

	/**
	 * Tags that are always auto-generated conventionally do not get
	 * autoInsertedStart or autoInsertedEnd. In the case of html and body, the
	 * DataBag is not set up when they are created, so trying to mark them
	 * would cause a fatal error.
	 *
	 * @param Element $element
	 * @return bool
	 */
	private function isMarkable( Element $element ) {
		return !in_array( $element->htmlName, [
			'html',
			'head',
			'body',
			'tbody',
			'meta'
		], true );
	}

	/**
	 * Set autoInsertedStart on auto-inserted nodes and forward the event to
	 * DOMBuilder.
	 *
	 * @param int $preposition
	 * @param Element|null $ref
	 * @param Element $element
	 * @param bool $void
	 * @param int $sourceStart
	 * @param int $sourceLength
	 */
	public function insertElement(
		$preposition, $ref, Element $element, $void, $sourceStart, $sourceLength
	) {
		// Elements can be inserted twice due to reparenting by the adoption
		// agency algorithm. If this is a reparenting, we don't want to
		// override autoInsertedStart flag set the first time around.
		$isMove = (bool)$element->userData;

		$this->nextHandler->insertElement( $preposition, $ref, $element, $void,
			$sourceStart, $sourceLength );

		// Compute nesting depth of mw:Transclusion meta tags
		if ( WTUtils::isTplMarkerMeta( $element->userData ) ) {
			$meta = $element->userData;

			$about = DOMCompat::getAttribute( $meta, 'about' );
			$isEnd = WTUtils::isTplEndMarkerMeta( $meta );
			$docDataBag = DOMDataUtils::getBag( $meta->ownerDocument );
			$docDataBag->transclusionMetaTagDepthMap[$about][$isEnd ? 'end' : 'start'] =
				DOMUtils::nodeDepth( $meta );
		}

		if ( $element->attrs === $this->matchAttribs ) {
			$this->matchedElement = $element->userData;
		} elseif ( !$isMove && $this->isMarkable( $element ) ) {
			DOMDataUtils::getDataParsoid( $element->userData )->autoInsertedStart = true;
		}
	}

	/**
	 * Set autoInsertedEnd on elements that were not closed by an explicit
	 * EndTagTk in the source stream.
	 *
	 * @param Element $element
	 * @param int $sourceStart
	 * @param int $sourceLength
	 */
	public function endTag( Element $element, $sourceStart, $sourceLength ) {
		$this->nextHandler->endTag( $element, $sourceStart, $sourceLength );

		if ( $sourceLength === $this->matchEndLength ) {
			$this->matchedElement = $element->userData;
			$isMatch = true;
		} else {
			$isMatch = false;
		}
		if ( $this->isMarkable( $element ) ) {
			/** @var DOMElement $node */
			$node = $element->userData;
			$dp = DOMDataUtils::getDataParsoid( $node );
			if ( !$isMatch ) {
				// An end tag auto-inserted by TreeBuilder
				$dp->autoInsertedEnd = true;
				unset( $dp->tmp->endTSR );
			} elseif ( $this->matchEndIsHtml ) {
				// We found a matching HTML end-tag - unset any AI flags.
				// This can happen because of wikitext like this:
				// '''X</b> where the quote-transformer inserts a
				// new autoInsertedEnd tag because it doesn't track
				// HTML quote tags.
				unset( $dp->autoInsertedEndToken );
				unset( $dp->autoInsertedEnd );
			} else {
				// If the node (start tag) was literal html, the end tag will be as well.
				// However, the converse isn't true.
				//
				// 1. A node for an auto-inserted start tag wouldn't have stx=html.
				//    See "Table with missing opening <tr> tag" test as an example.
				// 2. In "{|\n|foo\n</table>" (yes, found on wikis), start tag isn't HTML.
				//
				// We get to this branch if matched tag is not a html end-tag.
				// Check if start tag is html. If so, mark autoInsertedEnd.
				$startIsHtml = ( $dp->stx ?? '' ) === 'html';
				if ( $startIsHtml ) {
					$dp->autoInsertedEnd = true;
					unset( $dp->tmp->endTSR );
				}
			}
		}
	}

	/**
	 * A reparentChildren() operation includes insertion of the new parent.
	 * This is always automatic, so set autoInsertedStart on the new parent.
	 *
	 * @param Element $element
	 * @param Element $newParent
	 * @param int $sourceStart
	 */
	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$this->nextHandler->reparentChildren( $element, $newParent, $sourceStart );
		if ( $this->isMarkable( $newParent ) ) {
			/** @var DOMElement $node */
			$node = $newParent->userData;
			DOMDataUtils::getDataParsoid( $node )->autoInsertedStart = true;
		}
	}
}
