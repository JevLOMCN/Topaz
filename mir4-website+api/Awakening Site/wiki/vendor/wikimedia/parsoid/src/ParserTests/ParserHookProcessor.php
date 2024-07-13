<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

use stdClass;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMProcessor as ExtDOMProcessor;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * See tests/parser/ParserTestParserHook.php in core.
 */
class ParserHookProcessor extends ExtDOMProcessor {

	public function staticTagPostProcessor(
		Node $node, stdClass $obj
	): void {
		if ( $node instanceof Element ) {
			if ( DOMUtils::hasTypeOf( $node, 'mw:Extension/statictag' ) ) {
				$dataMw = DOMDataUtils::getDataMw( $node );
				if ( ( $dataMw->attrs->action ?? null ) === 'flush' ) {
					$node->appendChild( $node->ownerDocument->createTextNode( $obj->buf ) );
					$obj->buf = '';
				} else {
					$obj->buf .= $dataMw->body->extsrc;
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function wtPostprocess(
		ParsoidExtensionAPI $extApi, Node $node, array $options
	): void {
		// Pass an object since we want the data to be carried around across
		// nodes in the DOM. Passing an array won't work since visitDOM doesn't
		// use a reference on its end. Maybe we could fix that separately.
		DOMUtils::visitDOM( $node, [ $this, 'staticTagPostProcessor' ], (object)[ 'buf' => '' ] );
	}
}
