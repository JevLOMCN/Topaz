<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\AudioTrackList;
use Wikimedia\IDLeDOM\TextTrackList;
use Wikimedia\IDLeDOM\TimeRanges;
use Wikimedia\IDLeDOM\VideoTrackList;

trait HTMLMediaElement {
	// use \Wikimedia\IDLeDOM\Stub\CrossOrigin;

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
	public function getSrc(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setSrc( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getCurrentSrc(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getNetworkState(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return TimeRanges
	 */
	public function getBuffered() {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function load(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getReadyState(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getSeeking(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getCurrentTime(): float {
		throw self::_unimplemented();
	}

	/**
	 * @param float $val
	 */
	public function setCurrentTime( float $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getDuration(): float {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getPaused(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getDefaultPlaybackRate(): float {
		throw self::_unimplemented();
	}

	/**
	 * @param float $val
	 */
	public function setDefaultPlaybackRate( float $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getPlaybackRate(): float {
		throw self::_unimplemented();
	}

	/**
	 * @param float $val
	 */
	public function setPlaybackRate( float $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return TimeRanges
	 */
	public function getPlayed() {
		throw self::_unimplemented();
	}

	/**
	 * @return TimeRanges
	 */
	public function getSeekable() {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getEnded(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function pause(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getVolume(): float {
		throw self::_unimplemented();
	}

	/**
	 * @param float $val
	 */
	public function setVolume( float $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getMuted(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $val
	 */
	public function setMuted( bool $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return AudioTrackList
	 */
	public function getAudioTracks() {
		throw self::_unimplemented();
	}

	/**
	 * @return VideoTrackList
	 */
	public function getVideoTracks() {
		throw self::_unimplemented();
	}

	/**
	 * @return TextTrackList
	 */
	public function getTextTracks() {
		throw self::_unimplemented();
	}

}
