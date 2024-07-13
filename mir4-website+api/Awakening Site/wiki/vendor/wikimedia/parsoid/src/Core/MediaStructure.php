<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Core;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;

/**
 * All media should have a fixed structure:
 *
 * ```
 * <conatinerElt>
 *  <linkElt><mediaElt /></linkElt>
 *  <captionElt>...</captionElt>
 * </containerElt>
 * ```
 *
 * Pull out this fixed structure, being as generous as possible with
 * possibly-broken HTML.
 */
class MediaStructure {

	/**
	 * Node names: figure, span
	 *
	 * @var ?Element
	 */
	 public $containerElt;

	/**
	 * Node names: a, span
	 *
	 * @var ?Element
	 */
	public $linkElt;

	/**
	 * Node names: img, audio, video, span
	 *
	 * @var ?Element
	 */
	public $mediaElt;

	/**
	 * Node names: figcaption
	 *
	 * @var ?Element
	 */
	public $captionElt;

	/**
	 * @param Element $mediaElt
	 * @param ?Element $linkElt
	 * @param ?Element $containerElt
	 */
	public function __construct(
		Element $mediaElt, ?Element $linkElt = null,
		?Element $containerElt = null
	) {
		$this->mediaElt = $mediaElt;
		$this->linkElt = $linkElt;
		$this->containerElt = $containerElt;
		if ( $containerElt && DOMCompat::nodeName( $containerElt ) === 'figure' ) {
			// FIXME: Support last child, which is not the linkElt, as the caption?
			$this->captionElt = DOMCompat::querySelector( $containerElt, 'figcaption' );
		}
	}

	/**
	 * We were not able to fetch info for the title, so the media was
	 * considered missing and rendered as a span.
	 *
	 * @return bool
	 */
	public function isRedLink(): bool {
		return ( DOMCompat::nodeName( $this->mediaElt ) === 'span' );
	}

	/**
	 * @return ?string the resource name if it exists, otherwise null
	 */
	public function getResource(): ?string {
		return DOMCompat::getAttribute( $this->mediaElt, 'resource' );
	}

	/**
	 * @return ?string The alt text if it exists, otherwise null
	 */
	public function getAlt(): ?string {
		return DOMCompat::getAttribute( $this->mediaElt, 'alt' );
	}

	/**
	 * @return ?string The media href if it exists, otherwise null.
	 */
	public function getMediaUrl(): ?string {
		return $this->linkElt ?
			DOMCompat::getAttribute( $this->linkElt, 'href' ) :
			null;
	}

	/**
	 * @param Node $node
	 * @return ?MediaStructure
	 */
	public static function parse( Node $node ): ?MediaStructure {
		if ( !WTUtils::isGeneratedFigure( $node ) ) {
			return null;
		}
		'@phan-var Element $node';  // @var Element $node
		$linkElt = $node;
		do {
			// Try being lenient, maybe there was a content model violation when
			// parsing and an active formatting element was reopened in the wrapper
			$linkElt = DiffDOMUtils::firstNonSepChild( $linkElt );
		} while (
			$linkElt instanceof Element && DOMCompat::nodeName( $linkElt ) !== 'a' &&
			isset( Consts::$HTML['FormattingTags'][DOMCompat::nodeName( $linkElt )] )
		);
		if (
			!( $linkElt instanceof Element &&
				in_array( DOMCompat::nodeName( $linkElt ), [ 'a', 'span' ], true ) )
		) {
			if ( $linkElt instanceof Element ) {
				// Try being lenient, maybe this is the media element and we don't
				// have a link elt.  See the test, "Image: from basic HTML (1)"
				$mediaElt = $linkElt;
				$linkElt = null;
			} else {
				return null;
			}
		} else {
			$mediaElt = DiffDOMUtils::firstNonSepChild( $linkElt );
		}
		if (
			!( $mediaElt instanceof Element &&
				in_array( DOMCompat::nodeName( $mediaElt ), [ 'audio', 'img', 'span', 'video' ], true ) )
		) {
			return null;
		}
		return new MediaStructure( $mediaElt, $linkElt, $node );
	}

}
