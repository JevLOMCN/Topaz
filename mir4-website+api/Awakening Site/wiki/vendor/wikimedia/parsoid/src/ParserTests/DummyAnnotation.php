<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;

/**
 * Dummy annotation to test the annotation mechanisms outside of any extension-specific
 * considerations.
 */
class DummyAnnotation extends ExtensionTagHandler implements ExtensionModule {
	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'DummyAnnotation',
			// If these are not the same length as "translate" and "tvar"
			// respectively, it requires adjusting wtOffsets in the (large) test file.
			'annotations' => [ 'dummyanno', 'ann2' ]
		];
	}
}
