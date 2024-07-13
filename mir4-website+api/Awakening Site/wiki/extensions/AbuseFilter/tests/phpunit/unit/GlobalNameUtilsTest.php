<?php

/**
 * Generic tests for utility functions in AbuseFilter that do NOT require DB access
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
 *
 * @license GPL-2.0-or-later
 */

use MediaWiki\Extension\AbuseFilter\GlobalNameUtils;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterGeneric
 */
class GlobalNameUtilsTest extends MediaWikiUnitTestCase {

	/**
	 * @covers \MediaWiki\Extension\AbuseFilter\GlobalNameUtils::splitGlobalName
	 * @covers \MediaWiki\Extension\AbuseFilter\GlobalNameUtils::buildGlobalName
	 * @dataProvider provideBuildGlobalName
	 */
	public function testBuildGlobalName( int $id, bool $global, string $expected ) {
		$name = GlobalNameUtils::buildGlobalName( $id, $global );
		$filterDef = GlobalNameUtils::splitGlobalName( $name );
		$this->assertSame( $expected, $name );
		$this->assertSame( [ $id, $global ], $filterDef );
	}

	public static function provideBuildGlobalName(): array {
		return [
			[ 1, false, '1' ],
			[ 2, true, 'global-2' ],
		];
	}

	/**
	 * @param string $name The name of a filter
	 * @param array|null $expected If array, the expected result like [ id, isGlobal ].
	 *   If null it means that we're expecting an exception.
	 * @covers \MediaWiki\Extension\AbuseFilter\GlobalNameUtils::splitGlobalName
	 * @dataProvider provideGlobalNames
	 */
	public function testSplitGlobalName( $name, $expected ) {
		if ( $expected !== null ) {
			$actual = GlobalNameUtils::splitGlobalName( $name );
			$this->assertSame( $expected, $actual );
		} else {
			$this->expectException( InvalidArgumentException::class );
			GlobalNameUtils::splitGlobalName( $name );
		}
	}

	/**
	 * Data provider for testSplitGlobalName
	 *
	 * @return array
	 */
	public static function provideGlobalNames() {
		return [
			[ '15', [ 15, false ] ],
			[ 15, [ 15, false ] ],
			[ 'global-1', [ 1, true ] ],
			[ 'new', null ],
			[ false, null ],
			[ 'global-15-global', null ],
			[ 0, [ 0, false ] ],
			[ 'global-', null ],
			[ 'global-lol', null ],
			[ 'global-17.2', null ],
			[ '17,2', null ],
		];
	}
}
