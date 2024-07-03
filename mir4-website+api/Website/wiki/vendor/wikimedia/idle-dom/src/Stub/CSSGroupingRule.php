<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\CSSRuleList;

trait CSSGroupingRule {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return CSSRuleList
	 */
	public function getCssRules() {
		throw self::_unimplemented();
	}

	/**
	 * @param string $rule
	 * @param int $index
	 * @return int
	 */
	public function insertRule( string $rule, int $index = 0 ): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return void
	 */
	public function deleteRule( int $index ): void {
		throw self::_unimplemented();
	}

}
