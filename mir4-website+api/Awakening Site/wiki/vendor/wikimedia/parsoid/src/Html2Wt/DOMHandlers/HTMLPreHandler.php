<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;

class HTMLPreHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		( new FallbackHTMLHandler )->handle( $node, $state, $wrapperUnmodified );
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function firstChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [ 'max' => PHP_INT_MAX ];
	}

	/** @inheritDoc */
	public function lastChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [ 'max' => PHP_INT_MAX ];
	}

}
