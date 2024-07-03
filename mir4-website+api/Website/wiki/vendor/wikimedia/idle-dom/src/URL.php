<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * URL
 *
 * @see https://dom.spec.whatwg.org/#interface-url
 *
 * @property string $href
 * @property string $origin
 * @property string $protocol
 * @property string $username
 * @property string $password
 * @property string $host
 * @property string $hostname
 * @property string $port
 * @property string $pathname
 * @property string $search
 * @property URLSearchParams $searchParams
 * @property string $hash
 * @phan-forbid-undeclared-magic-properties
 */
interface URL {

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
	public function getUsername(): string;

	/**
	 * @param string $val
	 */
	public function setUsername( string $val ): void;

	/**
	 * @return string
	 */
	public function getPassword(): string;

	/**
	 * @param string $val
	 */
	public function setPassword( string $val ): void;

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
	 * @return URLSearchParams
	 */
	public function getSearchParams();

	/**
	 * @return string
	 */
	public function getHash(): string;

	/**
	 * @param string $val
	 */
	public function setHash( string $val ): void;

	/**
	 * @return string
	 */
	public function toJSON(): string;

}
