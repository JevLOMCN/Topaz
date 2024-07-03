<?php
/**
 * Copyright (c) 2015 Ori Livneh <ori@wikimedia.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @file
 * @author Ori Livneh <ori@wikimedia.org>
 */

namespace Wikimedia;

/**
 * Utilities for computing a relative filepath between two paths.
 */
class RelPath {
	/**
	 * Split a path into path components.
	 *
	 * @param string $path File path.
	 * @return string[] Array of path components.
	 */
	public static function splitPath( string $path ): array {
		$fragments = [];

		while ( true ) {
			$cur = dirname( $path );
			if ( $cur[0] === DIRECTORY_SEPARATOR ) {
				// dirname() on Windows sometimes returns a leading backslash, but other
				// times retains the leading forward slash. Slashes other than the leading one
				// are returned as-is, and therefore do not need to be touched.
				// Furthermore, don't break on *nix where \ is allowed in file/directory names.
				$cur[0] = '/';
			}

			if ( $cur === $path || ( $cur === '.' && basename( $path ) === $path ) ) {
				break;
			}

			$fragment = trim( substr( $path, strlen( $cur ) ), '/' );

			if ( !$fragments ) {
				$fragments[] = $fragment;
			} elseif ( $fragment === '..' && basename( $cur ) !== '..' ) {
				$cur = dirname( $cur );
			} elseif ( $fragment !== '.' ) {
				$fragments[] = $fragment;
			}

			$path = $cur;
		}

		if ( $path !== '' ) {
			$fragments[] = trim( $path, '/' );
		}

		return array_reverse( $fragments );
	}

	/**
	 * Return a relative filepath to path either from the current directory or from
	 * an optional start directory. Both paths must be absolute.
	 *
	 * @param string $path File path.
	 * @param string|null $start Start directory. Optional; if not specified, the current
	 *  working directory will be used.
	 * @return string|false Relative path, or false if input was invalid.
	 */
	public static function getRelativePath( string $path, string $start = null ) {
		if ( $start === null ) {
			// @codeCoverageIgnoreStart
			$start = getcwd();
		}
		// @codeCoverageIgnoreEnd

		if ( substr( $path, 0, 1 ) !== '/' || substr( $start, 0, 1 ) !== '/' ) {
			return false;
		}

		$pathParts = self::splitPath( $path );
		$countPathParts = count( $pathParts );

		$startParts = self::splitPath( $start );
		$countStartParts = count( $startParts );

		$commonLength = min( $countPathParts, $countStartParts );
		for ( $i = 0; $i < $commonLength; $i++ ) {
			if ( $startParts[$i] !== $pathParts[$i] ) {
				break;
			}
		}

		$relList = ( $countStartParts > $i )
			? array_fill( 0, $countStartParts - $i, '..' )
			: [];

		$relList = array_merge( $relList, array_slice( $pathParts, $i ) );

		return implode( '/', $relList ) ?: '.';
	}

	/**
	 * Join two path components.
	 *
	 * This can be used to expand a path relative to a given base path.
	 * The given path may also be absolute, in which case it is returned
	 * directly.
	 *
	 * @code
	 *     RelPath::joinPath('/srv/foo', 'bar');        # '/srv/foo/bar'
	 *     RelPath::joinPath('/srv/foo', './bar');      # '/srv/foo/bar'
	 *     RelPath::joinPath('/srv//foo', '../baz');    # '/srv/baz'
	 *     RelPath::joinPath('/srv/foo', '/var/quux/'); # '/var/quux/'
	 * @endcode
	 *
	 * This function is similar to `os.path.join()` in Python,
	 * and `path.join()` in Node.js.
	 *
	 * @param string $base Base path.
	 * @param string $path File path to join to base path.
	 * @return string|false Combined path, or false if input was invalid.
	 */
	public static function joinPath( string $base, string $path ) {
		if ( substr( $path, 0, 1 ) === '/' ) {
			// $path is absolute.
			return $path;
		}

		if ( substr( $base, 0, 1 ) !== '/' ) {
			// $base is relative.
			return false;
		}

		$pathParts = self::splitPath( $path );
		$resultParts = self::splitPath( $base );

		while ( ( $part = array_shift( $pathParts ) ) !== null ) {
			switch ( $part ) {
			case '.':
				break;
			case '..':
				if ( count( $resultParts ) > 1 ) {
					array_pop( $resultParts );
				}
				break;
			default:
				$resultParts[] = $part;
				break;
			}
		}

		return implode( '/', $resultParts );
	}
}
