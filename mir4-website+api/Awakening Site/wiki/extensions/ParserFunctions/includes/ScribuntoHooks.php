<?php

namespace MediaWiki\Extension\ParserFunctions;

/**
 * Hooks from Scribunto extension,
 * which is optional to use with this extension.
 * TODO: Implement interface MediaWiki\Extension\Scribunto\Hooks\ScribuntoExternalLibrariesHook
 *  when extension requirement is MediaWiki 1.41+
 */
class ScribuntoHooks {

	/**
	 * Registers ParserFunctions' lua function with Scribunto
	 *
	 * @see https://www.mediawiki.org/wiki/Extension:Scribunto/ScribuntoExternalLibraries
	 *
	 * @param string $engine
	 * @param string[] &$extraLibraries
	 */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		if ( $engine === 'lua' ) {
			$extraLibraries['mw.ext.ParserFunctions'] = LuaLibrary::class;
		}
	}
}
