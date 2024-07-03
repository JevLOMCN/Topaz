<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;

/**
 * A Parsoid extension module may contain one or more DOMProcessors,
 * which allow Parsoid to post-process the DOM in the wt2html direction,
 * or pre-process the DOM in the html2wt direction.
 *
 * @phan-file-suppress PhanEmptyPublicMethod
 */
abstract class DOMProcessor {

	/**
	 * Post-process DOM in the wt2html direction.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param DocumentFragment|Element $root The root of the tree to process
	 * @param array $options
	 */
	public function wtPostprocess(
		ParsoidExtensionAPI $extApi,
		Node $root,
		array $options
	): void {
		/* do nothing by default */
	}

	/**
	 * Pre-process DOM in the html2wt direction.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param Element $root
	 */
	public function htmlPreprocess(
		ParsoidExtensionAPI $extApi,
		Element $root
	): void {
		/* do nothing by default */
	}
}
