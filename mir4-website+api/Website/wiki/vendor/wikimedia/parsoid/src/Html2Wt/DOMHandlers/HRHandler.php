<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DOMDataUtils;

class HRHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$state->emitChunk( str_repeat( '-',
			4 + ( DOMDataUtils::getDataParsoid( $node )->extra_dashes ?? 0 ) ), $node );
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 1, 'max' => 2 ];
	}

	// XXX: Add a newline by default if followed by new/modified content

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 0, 'max' => 2 ];
	}

}
