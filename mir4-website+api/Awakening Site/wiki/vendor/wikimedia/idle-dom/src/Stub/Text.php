<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait Text {
	// use \Wikimedia\IDLeDOM\Stub\Slottable;

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param int $offset
	 * @return \Wikimedia\IDLeDOM\Text
	 */
	public function splitText( int $offset ) {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getWholeText(): string {
		throw self::_unimplemented();
	}

}
