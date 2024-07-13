<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;

/**
 * wt2html DOM processor used to implement some DOM functionality
 * (ex: DSR computation, template wrapping, etc.)
 */
interface Wt2HtmlDOMProcessor {
	/**
	 * @param Env $env
	 * @param Element|DocumentFragment $root The root of the tree to process
	 * @param array $options
	 * @param bool $atTopLevel Is this processor invoked on the top level page?
	 *   If false, this is being invoked in a sub-pipeline (ex: extensions)
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void;
}
