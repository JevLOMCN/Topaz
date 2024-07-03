<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;

class QuoteHandler extends DOMHandler {

	/** @var string Quote sequence to match as opener/closer */
	public $quotes;

	/**
	 * @param string $quotes Quote sequence to match as opener/closer
	 */
	public function __construct( string $quotes ) {
		parent::__construct( false );
		$this->quotes = $quotes;
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		if ( $this->precedingQuoteEltRequiresEscape( $node ) ) {
			$state->emitChunk( '<nowiki/>', $node );
		}
		$state->emitChunk( $this->quotes, $node );

		if ( $node->hasChildNodes() ) {
			$state->serializeChildren( $node );
		} else {
			// Empty nodes like <i></i> or <b></b> need
			// a <nowiki/> in place of the empty content so that
			// they parse back identically.
			$state->emitChunk( '<nowiki/>', $node );
		}

		$state->emitChunk( $this->quotes, $node );
		return $node->nextSibling;
	}

	private function precedingQuoteEltRequiresEscape(
		Element $node
	): bool {
		// * <i> and <b> siblings don't need a <nowiki/> separation
		// as long as quote chars in text nodes are always
		// properly escaped -- which they are right now.
		//
		// * Adjacent quote siblings need a <nowiki/> separation
		// between them if either of them will individually
		// generate a sequence of quotes 4 or longer. That can
		// only happen when either prev or node is of the form:
		// <i><b>...</b></i>
		//
		// For new/edited DOMs, this can never happen because
		// wts.minimizeQuoteTags.js does quote tag minimization.
		//
		// For DOMs from existing wikitext, this can only happen
		// because of auto-inserted end/start tags. (Ex: ''a''' b ''c''')
		$prev = DiffDOMUtils::previousNonDeletedSibling( $node );
		return $prev && DOMUtils::isQuoteElt( $prev )
			&& ( DOMUtils::isQuoteElt( DiffDOMUtils::lastNonDeletedChild( $prev ) )
				|| DOMUtils::isQuoteElt( DiffDOMUtils::firstNonDeletedChild( $node ) ) );
	}

}
