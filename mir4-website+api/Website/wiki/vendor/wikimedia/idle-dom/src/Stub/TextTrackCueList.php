<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\TextTrackCue;

trait TextTrackCueList {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return TextTrackCue
	 */
	public function item( int $index ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $id
	 * @return TextTrackCue|null
	 */
	public function getCueById( string $id ) {
		throw self::_unimplemented();
	}

}
