<?php

namespace MediaWiki\Extension\Scribunto\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ScribuntoExternalLibraries" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface ScribuntoExternalLibrariesHook {
	/**
	 * @param string $engine
	 * @param array &$extraLibraries
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries );
}
