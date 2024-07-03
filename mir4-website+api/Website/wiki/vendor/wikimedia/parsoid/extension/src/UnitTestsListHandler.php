<?php
/**
 * Copyright (C) 2011-2020 Wikimedia Foundation and others.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
declare( strict_types = 1 );

namespace MWParsoid;

use MediaWiki\Hook\UnitTestsListHook;

class UnitTestsListHandler implements UnitTestsListHook {
	/** @inheritDoc */
	public function onUnitTestsList( &$paths ) {
		// Find the directory corresponding to this extension code and skip
		// it!  tests/phpunit is our own standalone tests for the Parsoid
		// library.
		$libTestDir = realpath( __DIR__ . '/../../tests/phpunit' );
		foreach ( $paths as $idx => $path ) {
			if ( realpath( $path ) === $libTestDir ) {
				unset( $paths[$idx] );
			}
		}
		$paths = array_values( $paths ); // re-index
		// If we need to add extension-specific Parsoid tests
		// we'll put them in extension/tests/phpunit.
		$paths[] = __DIR__ . '/../tests/phpunit';
	}

}
