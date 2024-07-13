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

namespace Cdb\Writer;

use Cdb\Exception;
use Cdb\Util;
use Cdb\Writer;

/**
 * CDB writer class
 *
 * This is a port of D.J. Bernstein's CDB to PHP. It's based on the copy that
 * appears in PHP 5.3.
 */
class PHP extends Writer {
	/**
	 * @var resource|false|null The file handle
	 */
	protected $handle;

	/** @var int[][] */
	protected $hplist = [];

	/** @var int */
	protected $numentries = 0;

	/** @var int */
	protected $pos;

	/**
	 * Create the object and open the file.
	 *
	 * @param string $fileName
	 */
	public function __construct( string $fileName ) {
		$this->realFileName = $fileName;
		$this->tmpFileName = $fileName . '.tmp.' . mt_rand( 0, 0x7fffffff );
		$this->handle = fopen( $this->tmpFileName, 'wb' );
		if ( !$this->handle ) {
			$this->throwException(
				'Unable to open CDB file "' . $this->tmpFileName . '" for write.' );
		}
		$this->pos = 2048; // leaving space for the pointer array, 256 * 8
		if ( fseek( $this->handle, $this->pos ) == -1 ) {
			$this->throwException( 'fseek failed in file "' . $this->tmpFileName . '".' );
		}
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function set( $key, $value ): void {
		$key = (string)$key;
		if ( $key === '' ) {
			// DBA cross-check hack
			return;
		}

		// Based on cdb_make_addbegin
		$keylen = strlen( $key );
		$datalen = strlen( $value );
		if ( $keylen > 0x7fffffff ) {
			$this->throwException( 'Key length too long in file "' . $this->tmpFileName . '".' );
		}
		if ( $datalen > 0x7fffffff ) {
			$this->throwException( 'Data length too long in file "' . $this->tmpFileName . '".' );
		}
		$begin = pack( 'VV', $keylen, $datalen );

		$this->write( $begin . $key . $value );

		// Based on cdb_make_addend
		$this->hplist[] = [
			'h' => Util::hash( $key ),
			'p' => $this->pos
		];
		$this->numentries++;
		$this->posplus( 8 + $keylen + $datalen );
	}

	public function close(): void {
		if ( $this->handle ) {
			$this->finish();
			fclose( $this->handle );

			if ( $this->isWindows() && file_exists( $this->realFileName ) ) {
				unlink( $this->realFileName );
			}
			if ( !rename( $this->tmpFileName, $this->realFileName ) ) {
				$this->throwException( 'Unable to move the new CDB file into place.' );
			}
		}
		$this->handle = null;
	}

	/**
	 * @param string $buf
	 */
	protected function write( $buf ): void {
		$len = fwrite( $this->handle, $buf );
		if ( $len !== strlen( $buf ) ) {
			$this->throwException( 'Error writing to CDB file "' . $this->tmpFileName . '".' );
		}
	}

	/**
	 * @param int $len
	 */
	protected function posplus( $len ) {
		$newpos = $this->pos + $len;
		if ( $newpos > 0x7fffffff ) {
			$this->throwException(
				'A value in the CDB file "' . $this->tmpFileName . '" is too large.' );
		}
		$this->pos = $newpos;
	}

	protected function finish(): void {
		// Hack for DBA cross-check
		$this->hplist = array_reverse( $this->hplist );

		// Calculate the number of items that will be in each hashtable
		$counts = array_fill( 0, 256, 0 );
		foreach ( $this->hplist as $item ) {
			++$counts[255 & $item['h']];
		}

		// Fill in $starts with the *end* indexes
		$starts = [];
		$pos = 0;
		for ( $i = 0; $i < 256; ++$i ) {
			$pos += $counts[$i];
			$starts[$i] = $pos;
		}

		// Excessively clever and indulgent code to simultaneously fill $packedTables
		// with the packed hashtables, and adjust the elements of $starts
		// to actually point to the starts instead of the ends.
		if ( $this->numentries > 0 ) {
			$packedTables = array_fill( 0, $this->numentries, false );
		} else {
			// array_fill(): Number of elements must be positive
			$packedTables = [];
		}
		foreach ( $this->hplist as $item ) {
			$packedTables[--$starts[255 & $item['h']]] = $item;
		}

		$final = '';
		for ( $i = 0; $i < 256; ++$i ) {
			$count = $counts[$i];

			// The size of the hashtable will be double the item count.
			// The rest of the slots will be empty.
			$len = $count + $count;
			$final .= pack( 'VV', $this->pos, $len );

			$hashtable = array_fill( 0, $len, [ 'h' => 0, 'p' => 0 ] );

			// Fill the hashtable, using the next empty slot if the hashed slot
			// is taken.
			for ( $u = 0; $u < $count; ++$u ) {
				// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
				$hp = $packedTables[$starts[$i] + $u];
				$where = Util::unsignedMod(
					Util::unsignedShiftRight( $hp['h'], 8 ), $len );
				while ( $hashtable[$where]['p'] ) {
					if ( ++$where == $len ) {
						$where = 0;
					}
				}
				$hashtable[$where] = $hp;
			}

			// Write the hashtable
			$buf = '';
			for ( $u = 0; $u < $len; ++$u ) {
				$buf .= pack( 'vvV',
					$hashtable[$u]['h'] & 0xffff,
					Util::unsignedShiftRight( $hashtable[$u]['h'], 16 ),
					$hashtable[$u]['p'] );
			}
			$this->write( $buf );
			$this->posplus( strlen( $buf ) );
		}

		// Write the pointer array at the start of the file
		rewind( $this->handle );
		if ( ftell( $this->handle ) != 0 ) {
			$this->throwException( 'Error rewinding to start of file "' . $this->tmpFileName . '".' );
		}
		$this->write( $final );
	}

	/**
	 * Clean up the temp file and throw an exception
	 *
	 * @param string $msg
	 * @return never
	 * @throws Exception
	 */
	protected function throwException( $msg ) {
		if ( $this->handle ) {
			fclose( $this->handle );
			unlink( $this->tmpFileName );
		}
		throw new Exception( $msg );
	}
}
