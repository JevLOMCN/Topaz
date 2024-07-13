<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use Parser;

class HtmlLibrary extends LibraryBase {
	public function register() {
		return $this->getEngine()->registerInterface( 'mw.html.lua', [], [
			'uniqPrefix' => Parser::MARKER_PREFIX,
			'uniqSuffix' => Parser::MARKER_SUFFIX,
		] );
	}
}
