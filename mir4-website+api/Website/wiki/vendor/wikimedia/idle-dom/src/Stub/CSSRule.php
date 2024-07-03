<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\CSSStyleSheet;

trait CSSRule {

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
	public function getCssText(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setCssText( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return \Wikimedia\IDLeDOM\CSSRule|null
	 */
	public function getParentRule() {
		throw self::_unimplemented();
	}

	/**
	 * @return CSSStyleSheet|null
	 */
	public function getParentStyleSheet() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getType(): int {
		throw self::_unimplemented();
	}

}
