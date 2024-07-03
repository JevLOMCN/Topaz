<?php
declare( strict_types = 1 );

namespace Cite\Parsoid;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Ext\DOMProcessor;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

/**
 * wt -> html DOM PostProcessor
 * @license GPL-2.0-or-later
 */
class RefProcessor extends DOMProcessor {

	/**
	 * @inheritDoc
	 */
	public function wtPostprocess(
		ParsoidExtensionAPI $extApi, Node $node, array $options
	): void {
		$refsData = new ReferencesData();
		References::processRefs( $extApi, $refsData, $node );
		References::insertMissingReferencesIntoDOM( $extApi, $refsData, $node );
		if ( $refsData->embeddedErrors ) {
			References::addEmbeddedErrors( $extApi, $refsData, $node );
		}
	}

	/**
	 * html -> wt DOM PreProcessor
	 *
	 * Nothing to do right now.
	 *
	 * But, for example, as part of some future functionality, this could be used to
	 * reconstitute page-level information from local annotations left behind by editing clients.
	 */
	public function htmlPreprocess( ParsoidExtensionAPI $extApi, Element $root ): void {
		// TODO
	}
}
