<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use LogicException;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;

class LuaCommonTestsFailLibrary extends LibraryBase {
	public function __construct() {
		throw new LogicException( 'deferLoad library that is never required was loaded anyway' );
	}

	public function register() {
	}
}
