<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\DiffUtils;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;

class THHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$dp = DOMDataUtils::getDataParsoid( $node );
		$usableDP = $this->stxInfoValidForTableCell( $state, $node );
		$attrSepSrc = $usableDP ? ( $dp->attrSepSrc ?? null ) : null;
		$startTagSrc = $usableDP ? ( $dp->startTagSrc ?? null ) : '';
		if ( !$startTagSrc ) {
			$startTagSrc = $usableDP && ( $dp->stx ?? null ) === 'row' ? '!!' : '!';
		}

		// T149209: Special case to deal with scenarios
		// where the previous sibling put us in a SOL state
		// (or will put in a SOL state when the separator is emitted)
		$min = $state->sep->constraints['min'] ?? 0;
		$max = $state->sep->constraints['max'] ?? 1;
		if ( $min > 0 || ( $max > 0 && str_contains( $state->sep->src ?? '', "\n" ) ) ) {
			// You can use both "!!" and "||" for same-row headings (ugh!)
			$startTagSrc = preg_replace( '/!!/', '!', $startTagSrc, 1 );
			$startTagSrc = preg_replace( '/\|\|/', '!', $startTagSrc, 1 );
			$startTagSrc = preg_replace( '/{{!}}{{!}}/', '{{!}}', $startTagSrc, 1 );
		}

		$thTag = $this->serializeTableTag( $startTagSrc, $attrSepSrc, $state, $node, $wrapperUnmodified );
		$leadingSpace = $this->getLeadingSpace( $state, $node, '' );
		// If the HTML for the first th is not enclosed in a tr-tag,
		// we start a new line.  If not, tr will have taken care of it.
		$state->emitChunk( $thTag . $leadingSpace, $node );
		$thHandler = static function ( $state, $text, $opts ) use ( $node ) {
			return $state->serializer->wteHandlers->thHandler( $node, $state, $text, $opts );
		};

		$nextTh = DiffDOMUtils::nextNonSepSibling( $node );
		$nextUsesRowSyntax = $nextTh instanceof Element
			&& ( DOMDataUtils::getDataParsoid( $nextTh )->stx ?? null ) === 'row';

		// For empty cells, emit a single whitespace to make wikitext
		// more readable as well as to eliminate potential misparses.
		if ( $nextUsesRowSyntax && !DiffDOMUtils::firstNonDeletedChild( $node ) ) {
			$state->serializer->emitWikitext( ' ', $node );
			return $node->nextSibling;
		}

		$state->serializeChildren( $node, $thHandler );

		// PORT-FIXME does regexp whitespace semantics change matter?
		if ( !preg_match( '/\s$/D', $state->currLine->text ) ) {
			$trailingSpace = null;
			if ( $nextUsesRowSyntax ) {
				$trailingSpace = $this->getTrailingSpace( $state, $node, '' );
			}
			// Recover any trimmed whitespace only on unmodified nodes
			if ( !$trailingSpace ) {
				$lastChild = DiffDOMUtils::lastNonSepChild( $node );
				if ( $lastChild && !DiffUtils::hasDiffMarkers( $lastChild ) ) {
					$trailingSpace = $state->recoverTrimmedWhitespace( $node, false );
				}
			}
			if ( $trailingSpace ) {
				$state->appendSep( $trailingSpace );
			}
		}
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( DOMCompat::nodeName( $otherNode ) === 'th'
			&& ( DOMDataUtils::getDataParsoid( $node )->stx ?? null ) === 'row'
		) {
			// force single line
			return [ 'min' => 0, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
		} else {
			return [ 'min' => 1, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( DOMCompat::nodeName( $otherNode ) === 'td' ) {
			// Force a newline break
			return [ 'min' => 1, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
		} else {
			return [ 'min' => 0, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
		}
	}

}
