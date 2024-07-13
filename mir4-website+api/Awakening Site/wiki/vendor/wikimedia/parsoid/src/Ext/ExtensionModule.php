<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

/**
 * A Parsoid native extension module.  This bundles up the configuration
 * for a number of different ExtensionTagHandlers, ContentModelHandlers,
 * and DomProcessors into one registered object.  The only method required
 * is `getConfig`.
 *
 * FIXME: This might be created on-demand by configuration data specified
 * in extension.json.
 */
interface ExtensionModule {

	/**
	 * Return information about this extension module.
	 * FIXME: Add more expected fields or create a class for this
	 * FIXME: The 'name' is expected to be the same as the name defined
	 * at the top level of extension.json.
	 * @return array{name:string}
	 */
	public function getConfig(): array;

}
