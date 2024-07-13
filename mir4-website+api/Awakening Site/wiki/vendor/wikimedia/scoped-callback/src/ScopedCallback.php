<?php
/**
 * This file deals with RAII style scoped callbacks.
 *
 * Copyright (C) 2016 Aaron Schulz <aschulz@wikimedia.org>
 *
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
 *
 * @file
 */

namespace Wikimedia;

use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Make a callback run when a dummy object leaves the scope.
 */
class ScopedCallback {
	/** @var callable|null */
	protected $callback;
	/** @var array */
	protected $params;

	/**
	 * @param callable|null $callback
	 * @param array $params Callback arguments (since 1.0.0, MediaWiki 1.25)
	 */
	public function __construct( $callback, array $params = [] ) {
		if ( $callback !== null && !is_callable( $callback ) ) {
			throw new InvalidArgumentException( 'Provided callback is not valid.' );
		}
		$this->callback = $callback;
		$this->params = $params;
	}

	/**
	 * Trigger a scoped callback and destroy it.
	 * This is the same as just setting it to null.
	 *
	 * @param ScopedCallback|null &$sc
	 */
	public static function consume( ScopedCallback &$sc = null ) {
		$sc = null;
	}

	/**
	 * Destroy a scoped callback without triggering it.
	 *
	 * @param ScopedCallback|null &$sc
	 */
	public static function cancel( ScopedCallback &$sc = null ) {
		if ( $sc ) {
			$sc->callback = null;
		}
		$sc = null;
	}

	/**
	 * Make PHP ignore user aborts/disconnects until the returned
	 * value leaves scope. This returns null and does nothing in CLI mode.
	 *
	 * @since 3.0.0
	 * @return ScopedCallback|null
	 *
	 * @codeCoverageIgnore CI is only run via CLI, so this will never be exercised.
	 * Also no benefit testing a function just returns null.
	 */
	public static function newScopedIgnoreUserAbort() {
		// ignore_user_abort previously caused an infinite loop on CLI
		// https://bugs.php.net/bug.php?id=47540
		if ( PHP_SAPI != 'cli' ) {
			// avoid half-finished operations
			$old = ignore_user_abort( true );
			return new ScopedCallback( static function () use ( $old ) {
				ignore_user_abort( (bool)$old );
			} );
		}

		return null;
	}

	/**
	 * Trigger the callback when it leaves scope.
	 */
	function __destruct() {
		if ( $this->callback !== null ) {
			call_user_func_array( $this->callback, $this->params );
		}
	}

	/**
	 * Do not allow this class to be serialized
	 * @return never
	 */
	function __sleep() {
		throw new UnexpectedValueException( __CLASS__ . ' cannot be serialized' );
	}

	/**
	 * Protect the caller against arbitrary code execution
	 * @return never
	 */
	function __wakeup() {
		$this->callback = null;
		throw new UnexpectedValueException( __CLASS__ . ' cannot be unserialized' );
	}
}
