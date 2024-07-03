<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Logger\LintLogger;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

/**
 * Very thin shim to call ContentUtils::convertOffsets where requested
 * in the environment.
 */
class ConvertOffsets implements Wt2HtmlDOMProcessor {
	/**
	 * DOM Postprocessor entry function to walk DOM rooted at $root
	 * and convert the DSR offsets as needed.
	 * @see ConvertUtils::convertOffsets
	 *
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		Assert::invariant( $atTopLevel, 'This pass should only be run on the top-level' );
		$doc = $root->ownerDocument;
		$offsetType = $env->getRequestOffsetType();
		ContentUtils::convertOffsets(
			$env, $doc, 'byte', $offsetType
		);
		// Because linter runs before this DOM pass, we need to convert offsets
		// of collected lints from 'byte' to the requested type
		if ( $offsetType !== 'byte' ) {
			$lints = $env->getLints();
			LintLogger::convertDSROffsets( $env, $lints, 'byte', $offsetType );
			$env->setLints( $lints );
		}
		DOMDataUtils::getPageBundle( $doc )->parsoid['offsetType'] = $offsetType;
	}
}
