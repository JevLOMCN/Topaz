<?php

namespace MediaWiki\Skins\Vector\Hooks;

/**
 * Use the hook name "VectorSearchResourceLoaderConfig" to register
 * handlers implementing this interface to modify Vector's search config.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface VectorSearchResourceLoaderConfigHook {
	/**
	 * @param array &$vectorSearchConfig
	 * @return void
	 */
	public function onVectorSearchResourceLoaderConfig( array &$vectorSearchConfig ): void;
}
