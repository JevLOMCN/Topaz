<?php
/**
 * Copyright 2016 Timo Tijhof
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
 */

namespace Wikimedia;

class WrappedStringList {
	/** @var string */
	protected $sep;

	/** @var (string|WrappedString|WrappedStringList)[] */
	protected $wraps;

	/**
	 * @param string $sep
	 * @param (string|WrappedString|WrappedStringList)[] $wraps
	 */
	public function __construct( $sep, array $wraps ) {
		$this->sep = $sep;
		$this->wraps = $wraps;
	}

	/**
	 * @param (string|WrappedString|WrappedStringList)[] $wraps
	 * @return WrappedStringList Combined list
	 */
	protected function extend( array $wraps ) {
		$list = clone $this;
		$list->wraps = array_merge( $list->wraps, $wraps );
		return $list;
	}

	/**
	 * Merge consecutive lists with the same separator.
	 *
	 * Does not modify the given array or any of the objects in it.
	 *
	 * @param (string|WrappedString|WrappedStringList)[] $lists
	 * @param string $outerSep Separator that the caller intends to use when joining the strings
	 * @return string[] Compacted list to be treated as strings
	 * (may contain WrappedString and WrappedStringList objects)
	 */
	protected static function compact( array $lists, $outerSep ) {
		$consolidated = [];
		foreach ( $lists as $list ) {
			if ( !$list instanceof WrappedStringList ) {
				// Probably WrappedString or regular string,
				// Not mergable as a list, but may be merged as a string
				// later by WrappedString::compact.
				$consolidated[] = $list;
				continue;
			}
			if ( $list->sep === $outerSep ) {
				$consolidated = array_merge(
					$consolidated,
					self::compact( $list->wraps, $outerSep )
				);
			} else {
				$consolidated[] = $list;
			}
		}

		return WrappedString::compact( $consolidated );
	}

	/**
	 * Join a several wrapped strings with a separator between each.
	 *
	 * @param string $sep
	 * @param (string|WrappedString|WrappedStringList)[] $lists
	 * @return string
	 */
	public static function join( $sep, array $lists ) {
		return implode( $sep, self::compact( $lists, $sep ) );
	}

	/** @return string */
	public function __toString() {
		return self::join( $this->sep, [ $this ] );
	}
}
