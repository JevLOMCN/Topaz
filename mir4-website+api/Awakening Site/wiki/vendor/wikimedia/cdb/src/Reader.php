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

namespace Cdb;

/**
 * Read data from a CDB file.
 * Native C and pure PHP implementations are provided.
 *
 * @see http://cr.yp.to/cdb.html
 */
abstract class Reader {
	/**
	 * Open a file and return a subclass instance
	 *
	 * @param string $fileName
	 * @return Reader
	 */
	public static function open( $fileName ): Reader {
		return self::haveExtension() ?
			new Reader\DBA( $fileName ) :
			new Reader\PHP( $fileName );
	}

	/**
	 * Returns true if the native extension is available
	 *
	 * @return bool
	 * @codeCoverageIgnore
	 */
	public static function haveExtension(): bool {
		if ( !function_exists( 'dba_handlers' ) ) {
			return false;
		}
		$handlers = dba_handlers();

		return in_array( 'cdb', $handlers ) && in_array( 'cdb_make', $handlers );
	}

	/**
	 * Close the file. Optional, you can just let the variable go out of scope.
	 */
	abstract public function close(): void;

	/**
	 * Get a value with a given key. Only string values are supported.
	 *
	 * @param string|int $key
	 * @return string|false
	 */
	abstract public function get( $key );

	/**
	 * Check whether key exists
	 *
	 * @param string $key
	 * @return bool
	 */
	abstract public function exists( $key ): bool;

	/**
	 * Fetch first key
	 *
	 * @return string|false
	 */
	abstract public function firstkey();

	/**
	 * Fetch next key
	 *
	 * @return string|false
	 */
	abstract public function nextkey();
}
