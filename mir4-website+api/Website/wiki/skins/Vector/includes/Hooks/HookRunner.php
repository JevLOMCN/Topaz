<?php

namespace MediaWiki\Skins\Vector\Hooks;

use MediaWiki\HookContainer\HookContainer;

/**
 * @internal
 */
class HookRunner implements VectorSearchResourceLoaderConfigHook {
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onVectorSearchResourceLoaderConfig( array &$vectorSearchConfig ): void {
		$this->hookContainer->run(
			'VectorSearchResourceLoaderConfig',
			[ &$vectorSearchConfig ]
		);
	}
}
