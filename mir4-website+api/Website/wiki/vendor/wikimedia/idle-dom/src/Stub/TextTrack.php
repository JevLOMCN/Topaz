<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\EventHandlerNonNull;
use Wikimedia\IDLeDOM\TextTrackCue;
use Wikimedia\IDLeDOM\TextTrackCueList;

trait TextTrack {

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
	public function getKind(): /* TextTrackKind */ string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getLanguage(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getInBandMetadataTrackDispatchType(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return TextTrackCueList|null
	 */
	public function getCues() {
		throw self::_unimplemented();
	}

	/**
	 * @return TextTrackCueList|null
	 */
	public function getActiveCues() {
		throw self::_unimplemented();
	}

	/**
	 * @param TextTrackCue $cue
	 * @return void
	 */
	public function addCue( /* TextTrackCue */ $cue ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param TextTrackCue $cue
	 * @return void
	 */
	public function removeCue( /* TextTrackCue */ $cue ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOncuechange() {
		throw self::_unimplemented();
	}

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOncuechange( /* ?mixed */ $val ): void {
		throw self::_unimplemented();
	}

}
