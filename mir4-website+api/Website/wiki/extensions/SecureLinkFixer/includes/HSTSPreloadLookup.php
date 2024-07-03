<?php
/**
 * Copyright (C) 2018 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace MediaWiki\SecureLinkFixer;

class HSTSPreloadLookup {

	private string $path;
	/** @var array<string,int> */
	private array $domains;

	public function __construct( string $path ) {
		$this->path = $path;
	}

	/**
	 * @param string $host Hostname
	 *
	 * @return bool
	 */
	public function isPreloaded( string $host ): bool {
		// Lazy-load the domain mapping if it's not already set
		$this->domains ??= require $this->path;

		if ( isset( $this->domains[$host] ) ) {
			// Host is directly in the preload list
			return true;
		}
		// Check if parent subdomains are preloaded
		$offset = strpos( $host, '.' );
		while ( $offset !== false ) {
			$parentdomain = substr( $host, $offset + 1 );
			if ( isset( $this->domains[$parentdomain] ) ) {
				// This subdomain is directly in the preload list, returns true when subdomains supports https
				return (bool)$this->domains[$parentdomain];
			}
			// else it's not in the db, we might need to look it up again

			// Find the next parent subdomain
			$offset = strpos( $host, '.', $offset + 1 );
		}

		// @todo should we keep a negative cache?

		return false;
	}
}
