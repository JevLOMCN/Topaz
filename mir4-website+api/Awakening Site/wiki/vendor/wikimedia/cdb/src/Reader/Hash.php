<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Cdb\Reader;

use Cdb\Reader;

/**
 * Hash implements the CdbReader interface based on an associative
 * PHP array (a.k.a "hash").
 */
class Hash extends Reader {
	/** @var string[] */
	private $data;

	/**
	 * A queue of keys to return from nextkey(), initialized by firstkey();
	 *
	 * @var string[]|null
	 */
	private $keys = null;

	/**
	 * Create the object and open the file
	 *
	 * @param string[] $data An associative array
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Close the file. Optional, you can just let the variable go out of scope.
	 */
	public function close(): void {
		$this->data = [];
		$this->keys = null;
	}

	/**
	 * Get a value with a given key. Only string values are supported.
	 *
	 * @param string|int $key
	 * @return string|false The value associated with $key, or false if $key is not known.
	 */
	public function get( $key ) {
		return $this->data[ $key ] ?? false;
	}

	/**
	 * Check whether key exists
	 *
	 * @param string|int $key
	 * @return bool
	 */
	public function exists( $key ): bool {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Fetch first key
	 *
	 * @return string|false
	 */
	public function firstkey() {
		$this->keys = array_keys( $this->data );
		return $this->nextkey();
	}

	/**
	 * Fetch next key
	 *
	 * @return string|false
	 */
	public function nextkey() {
		if ( $this->keys === null ) {
			return $this->firstkey();
		}

		return $this->keys ? array_shift( $this->keys ) : false;
	}

}
