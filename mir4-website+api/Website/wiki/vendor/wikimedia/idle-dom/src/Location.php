<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Location
 *
 * @see https://dom.spec.whatwg.org/#interface-location
 *
 * @property string $href
 * @property string $origin
 * @property string $protocol
 * @property string $host
 * @property string $hostname
 * @property string $port
 * @property string $pathname
 * @property string $search
 * @property string $hash
 * @phan-forbid-undeclared-magic-properties
 */
interface Location {
	/**
	 * @return string
	 */
	public function getHref(): string;

	/**
	 * @param string $val
	 */
	public function setHref( string $val ): void;

	/**
	 * @return string
	 */
	public function getOrigin(): string;

	/**
	 * @return string
	 */
	public function getProtocol(): string;

	/**
	 * @param string $val
	 */
	public function setProtocol( string $val ): void;

	/**
	 * @return string
	 */
	public function getHost(): string;

	/**
	 * @param string $val
	 */
	public function setHost( string $val ): void;

	/**
	 * @return string
	 */
	public function getHostname(): string;

	/**
	 * @param string $val
	 */
	public function setHostname( string $val ): void;

	/**
	 * @return string
	 */
	public function getPort(): string;

	/**
	 * @param string $val
	 */
	public function setPort( string $val ): void;

	/**
	 * @return string
	 */
	public function getPathname(): string;

	/**
	 * @param string $val
	 */
	public function setPathname( string $val ): void;

	/**
	 * @return string
	 */
	public function getSearch(): string;

	/**
	 * @param string $val
	 */
	public function setSearch( string $val ): void;

	/**
	 * @return string
	 */
	public function getHash(): string;

	/**
	 * @param string $val
	 */
	public function setHash( string $val ): void;

	/**
	 * @param string $url
	 * @return void
	 */
	public function assign( string $url ): void;

	/**
	 * @param string $url
	 * @return void
	 */
	public function replace( string $url ): void;

	/**
	 * @return void
	 */
	public function reload(): void;

}
