<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\CSSStyleSheet;
use Wikimedia\IDLeDOM\MediaList;

trait CSSImportRule {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return string
	 */
	public function getHref(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return MediaList
	 */
	public function getMedia() {
		throw self::_unimplemented();
	}

	/**
	 * @return CSSStyleSheet
	 */
	public function getStyleSheet() {
		throw self::_unimplemented();
	}

}
