<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

class WrapSections implements Wt2HtmlDOMProcessor {

	/**
	 * DOM Postprocessor entry function to walk DOM rooted at $root
	 * and add <section> wrappers as necessary.
	 * Implements the algorithm documented @ mw:Parsing/Notes/Section_Wrapping
	 *
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		if ( !$env->getWrapSections() ) {
			return;
		}

		$state = new WrapSectionsState(
			$env,
			$options['frame'],
			$root
		);
		$state->run();
	}
}
