<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\EventHandlerNonNull;
use Wikimedia\IDLeDOM\TextTrack;

trait TextTrackCue {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return TextTrack|null
	 */
	public function getTrack() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setId( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getStartTime(): float {
		throw self::_unimplemented();
	}

	/**
	 * @param float $val
	 */
	public function setStartTime( float $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getEndTime(): float {
		throw self::_unimplemented();
	}

	/**
	 * @param float $val
	 */
	public function setEndTime( float $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getPauseOnExit(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setPauseOnExit( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnenter() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnenter( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnexit() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnexit( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

}
