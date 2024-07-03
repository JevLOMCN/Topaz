<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;

class PreHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		// Handle indent pre

		// XXX: Use a pre escaper?
		$content = $state->serializeIndentPreChildrenToString( $node );
		// Strip (only the) trailing newline
		$trailingNL = str_ends_with( $content, "\n" ) ? "\n" : '';
		if ( $trailingNL !== '' ) {
			$content = substr( $content, 0, -1 );
		}

		// Insert indentation
		$solRE = '/'
			. '(\n('
			// SSS FIXME: What happened to the includeonly seen
			// in wts.separators.js?
			. PHPUtils::reStrip( Utils::COMMENT_REGEXP )
			. ')*)'
		. '/';
		$content = ' ' . preg_replace( $solRE, '$1 ', $content );

		// But skip "empty lines" (lines with 1+ comment and
		// optional whitespace) since empty-lines sail through all
		// handlers without being affected.
		//
		// See empty_line_with_comments rule in pegTokenizer.pegjs
		//
		// We could use 'split' to split content into lines and
		// selectively add indentation, but the code will get
		// unnecessarily complex for questionable benefits. So, going
		// this route for now.
		$emptyLinesRE = '/'
			// This space comes from what we inserted earlier
			. '(^|\n) '
			. '((?:'
			. '[ \t]*'
			. PHPUtils::reStrip( Utils::COMMENT_REGEXP )
			. '[ \t]*'
			. ')+)'
			. '(?=\n|$)'
		. '/D';
		$content = preg_replace( $emptyLinesRE, '$1$2', $content, 1 );

		$state->emitChunk( $content, $node );

		// Preserve separator source
		$state->appendSep( $trailingNL );
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( $otherNode instanceof Element // for static analyzers
			&& DOMCompat::nodeName( $otherNode ) === 'pre'
			&& ( DOMDataUtils::getDataParsoid( $otherNode )->stx ?? null ) !== 'html'
		) {
			return [ 'min' => 2 ];
		} else {
			return [ 'min' => 1 ];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( $otherNode instanceof Element // for static analyzers
			&& DOMCompat::nodeName( $otherNode ) === 'pre'
			&& ( DOMDataUtils::getDataParsoid( $otherNode )->stx ?? null ) !== 'html'
		) {
			return [ 'min' => 2 ];
		} else {
			return [ 'min' => 1 ];
		}
	}

	/** @inheritDoc */
	public function firstChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [];
	}

	/** @inheritDoc */
	public function lastChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [];
	}

}
