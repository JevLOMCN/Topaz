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

class TDHandler extends DOMHandler {

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
			$startTagSrc = ( $usableDP && ( $dp->stx ?? null ) === 'row' ) ? '||' : '|';
		}

		// T149209: Special case to deal with scenarios
		// where the previous sibling put us in a SOL state
		// (or will put in a SOL state when the separator is emitted)
		$min = $state->sep->constraints['min'] ?? 0;
		$max = $state->sep->constraints['max'] ?? 1;
		if ( $min > 0 || ( $max > 0 && str_contains( $state->sep->src ?? '', "\n" ) ) ) {
			$startTagSrc = preg_replace( '/\|\|/', '|', $startTagSrc, 1 );
			$startTagSrc = preg_replace( '/{{!}}{{!}}/', '{{!}}', $startTagSrc, 1 );
		}

		// If the HTML for the first td is not enclosed in a tr-tag,
		// we start a new line.  If not, tr will have taken care of it.
		$tdTag = $this->serializeTableTag(
			$startTagSrc, $attrSepSrc,
			$state, $node, $wrapperUnmodified
		);
		$inWideTD = (bool)preg_match( '/\|\||^{{!}}{{!}}/', $tdTag );
		$leadingSpace = $this->getLeadingSpace( $state, $node, '' );
		$state->emitChunk( $tdTag . $leadingSpace, $node );
		$tdHandler = static function ( $state, $text, $opts ) use ( $node, $inWideTD ) {
			return $state->serializer->wteHandlers->tdHandler( $node, $inWideTD, $state, $text, $opts );
		};

		$nextTd = DiffDOMUtils::nextNonSepSibling( $node );
		$nextUsesRowSyntax = $nextTd instanceof Element
			&& ( DOMDataUtils::getDataParsoid( $nextTd )->stx ?? null ) === 'row';

		// For empty cells, emit a single whitespace to make wikitext
		// more readable as well as to eliminate potential misparses.
		if ( $nextUsesRowSyntax && !DiffDOMUtils::firstNonDeletedChild( $node ) ) {
			$state->serializer->emitWikitext( ' ', $node );
			return $node->nextSibling;
		}

		$state->serializeChildren( $node, $tdHandler );

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
		$forceSingleLine = DOMCompat::nodeName( $otherNode ) === 'td'
			&& ( DOMDataUtils::getDataParsoid( $node )->stx ?? null ) === 'row';
		return [ 'min' => $forceSingleLine ? 0 : 1, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 0, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
	}

}
