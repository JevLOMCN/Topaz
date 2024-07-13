<?php

namespace MediaWiki\Extension\Scribunto\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ScribuntoExternalLibraryPaths" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface ScribuntoExternalLibraryPathsHook {
	/**
	 * @param string $engine
	 * @param array &$extraLibraryPaths
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onScribuntoExternalLibraryPaths( string $engine, array &$extraLibraryPaths );
}
