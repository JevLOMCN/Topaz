<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;

/**
 * Used as a fallback in other tag handles.
 */
class FallbackHTMLHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$serializer = $state->serializer;

		// Wikitext supports the following list syntax:
		//
		// * <li class="a"> hello world
		//
		// The "LI Hack" gives support for this syntax, and we need to
		// specially reconstruct the above from a single <li> tag.
		$serializer->handleLIHackIfApplicable( $node );

		$tag = $serializer->serializeHTMLTag( $node, $wrapperUnmodified );
		$state->emitChunk( $tag, $node );

		if ( $node->hasChildNodes() ) {
			$inPHPBlock = $state->inPHPBlock;
			if (
				TokenUtils::tagOpensBlockScope( DOMCompat::nodeName( $node ) ) ||
				// Blockquote is special in that it doesn't suppress paragraphs
				// but does suppress pre wrapping
				DOMCompat::nodeName( $node ) === 'blockquote'
			) {
				$state->inPHPBlock = true;
			}

			// TODO(arlolra): As of 1.3.0, html pre is considered an extension
			// and wrapped in encapsulation.  When that version is no longer
			// accepted for serialization, we can remove this backwards
			// compatibility code.
			if ( DOMCompat::nodeName( $node ) === 'pre' ) {
				// Handle html-pres specially
				// 1. If the node has a leading newline, add one like it (logic copied from VE)
				// 2. If not, and it has a data-parsoid strippedNL flag, add it back.
				// This patched DOM will serialize html-pres correctly.

				$lostLine = '';
				$fc = $node->firstChild;
				if ( $fc instanceof Text ) {
					$lostLine = str_starts_with( $fc->nodeValue, "\n" ) ? "\n" : '';
				}

				if ( !$lostLine && ( DOMDataUtils::getDataParsoid( $node )->strippedNL ?? false ) ) {
					$lostLine = "\n";
				}

				$state->emitChunk( $lostLine, $node );
			}

			$state->serializeChildren( $node );
			$state->inPHPBlock = $inPHPBlock;
		}

		$endTag = $serializer->serializeHTMLEndTag( $node, $wrapperUnmodified );
		$state->emitChunk( $endTag, $node );
		return $node->nextSibling;
	}
}
