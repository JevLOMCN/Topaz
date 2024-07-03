<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

class HeadingHandler extends DOMHandler {

	/** @var string Heading open/close wikitext (e.g. '===') */
	public $headingWT;

	/**
	 * @param string $headingWT Heading open/close wikitext (e.g. '===')
	 */
	public function __construct( string $headingWT ) {
		parent::__construct( true );
		$this->headingWT = $headingWT;
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		// For new elements, for prettier wikitext serialization,
		// emit a space after the last '=' char.
		$space = $this->getLeadingSpace( $state, $node, ' ' );
		$state->emitChunk( $this->headingWT . $space, $node );
		$state->singleLineContext->enforce();

		if ( $node->hasChildNodes() ) {
			$state->serializeChildren( $node, null, DiffDOMUtils::firstNonDeletedChild( $node ) );
		} else {
			// Deal with empty headings
			$state->emitChunk( '<nowiki/>', $node );
		}

		// For new elements, for prettier wikitext serialization,
		// emit a space before the first '=' char.
		$space = $this->getTrailingSpace( $state, $node, ' ' );
		$state->emitChunk( $space . $this->headingWT, $node ); // Why emitChunk here??
		$state->singleLineContext->pop();
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( WTUtils::isNewElt( $node ) && DiffDOMUtils::previousNonSepSibling( $node ) &&
			!WTUtils::isAnnotationStartMarkerMeta( $otherNode )
		) {
			// Default to two preceding newlines for new content
			return [ 'min' => 2, 'max' => 2 ];
		} elseif ( WTUtils::isNewElt( $otherNode )
			&& DiffDOMUtils::previousNonSepSibling( $node ) === $otherNode
		) {
			// T72791: The previous node was newly inserted, separate
			// them for readability, except if it's an annotation tag
			if ( WTUtils::isAnnotationStartMarkerMeta( $otherNode ) ) {
				return [ 'min' => 1, 'max' => 2 ];
			}
			return [ 'min' => 2, 'max' => 2 ];
		} else {
			return [ 'min' => 1, 'max' => 2 ];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 1, 'max' => 2 ];
	}

}
