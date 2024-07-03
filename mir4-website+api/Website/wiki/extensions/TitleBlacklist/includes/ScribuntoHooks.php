<?php

namespace MediaWiki\Extension\TitleBlacklist;

use MediaWiki\Extension\Scribunto\Hooks\ScribuntoExternalLibrariesHook;

/**
 * Hooks from Scribunto extension,
 * which is optional to use with this extension.
 *
 * @ingroup Extensions
 */
class ScribuntoHooks implements ScribuntoExternalLibrariesHook {

	/**
	 * External Lua library for Scribunto
	 *
	 * @param string $engine
	 * @param array &$extraLibraries
	 */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		if ( $engine === 'lua' ) {
			$extraLibraries['mw.ext.TitleBlacklist'] = Scribunto_LuaTitleBlacklistLibrary::class;
		}
	}
}
