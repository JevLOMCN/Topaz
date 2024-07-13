<?php

namespace MediaWiki\Extension\TitleBlacklist;

use MediaWiki\MediaWikiServices;
use Scribunto_LuaLibraryBase;

class Scribunto_LuaTitleBlacklistLibrary extends Scribunto_LuaLibraryBase {
	public function register() {
		$lib = [
			'test' => [ $this, 'test' ],
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.ext.TitleBlacklist.lua', $lib, []
		);
	}

	public function test( $action = null, $title = null ) {
		$this->checkType( 'mw.ext.TitleBlacklist.test', 1, $action, 'string' );
		$this->checkTypeOptional( 'mw.ext.TitleBlacklist.test', 2, $title, 'string', '' );
		$this->incrementExpensiveFunctionCount();
		if ( $title == '' ) {
			$page = $this->getParser()->getPage();
			if ( !$page ) {
				// Nothing to check
				return [ null ];
			}
			$title = MediaWikiServices::getInstance()->getTitleFormatter()->getPrefixedText( $page );
		}
		$entry = TitleBlacklist::singleton()->isBlacklisted( $title, $action );
		if ( $entry ) {
			return [ [
				'params' => $entry->getParams(),
				'regex' => $entry->getRegex(),
				'raw' => $entry->getRaw(),
				'version' => $entry->getFormatVersion(),
				'message' => $entry->getErrorMessage( $action ),
				'custommessage' => $entry->getCustomMessage()
			] ];
		}
		return [ null ];
	}

}
