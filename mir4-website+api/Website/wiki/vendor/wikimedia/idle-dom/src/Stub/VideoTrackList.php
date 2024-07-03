<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\EventHandlerNonNull;
use Wikimedia\IDLeDOM\VideoTrack;

trait VideoTrackList {

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
	 * @return VideoTrack
	 */
	public function item( int $index ) {
		throw self::_unimplemented();
	}

	/**
	 * @param string $id
	 * @return VideoTrack|null
	 */
	public function getTrackById( string $id ) {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getSelectedIndex(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnchange() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnchange( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnaddtrack() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnaddtrack( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnremovetrack() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnremovetrack( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

}
