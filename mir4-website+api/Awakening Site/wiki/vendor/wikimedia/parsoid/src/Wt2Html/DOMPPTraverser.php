<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMTraverser;
use Wikimedia\Parsoid\Utils\DTState;

/**
 * This is a class that wraps the DOMTraverser utility for use
 * in the DOM Post Processor pipeline.
 */
class DOMPPTraverser implements Wt2HtmlDOMProcessor {
	private DOMTraverser $dt;

	public function __construct( bool $traverseWithTplInfo = false, bool $applyToAttributeEmbeddedHTML = false ) {
		$this->dt = new DOMTraverser( $traverseWithTplInfo, $applyToAttributeEmbeddedHTML );
	}

	/**
	 * @param ?string $nodeName An optional node name filter
	 * @param callable $action A callback, called on each node we traverse that matches nodeName.
	 * Proxies call to underlying DOMTraverser. See docs for DOMTraverser::addHandler
	 */
	public function addHandler( ?string $nodeName, callable $action ): void {
		$this->dt->addHandler( $nodeName, $action );
	}

	/**
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $workNode, array $options = [], bool $atTopLevel = false
	): void {
		$state = new DTState( $options, $atTopLevel );
		$this->dt->traverse( new ParsoidExtensionAPI( $env ), $workNode, $state );
	}
}
