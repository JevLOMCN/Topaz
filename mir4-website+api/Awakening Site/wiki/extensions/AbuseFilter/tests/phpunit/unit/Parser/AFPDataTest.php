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
 *
 * @file
 *
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Parser;

use InvalidArgumentException;
use MediaWiki\Extension\AbuseFilter\Parser\AFPData;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\InternalException;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 */
class AFPDataTest extends ParserTestCase {
	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::mulRel
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPTreeParser
	 *
	 * @dataProvider divideByZero
	 */
	public function testDivideByZeroException( $expr, $caller ) {
		$this->exceptionTest( 'dividebyzero', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'dividebyzero', $expr, $caller );
	}

	/**
	 * Data provider for testDivideByZeroException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public function divideByZero() {
		return [
			[ '1/0', 'mulRel' ],
			[ '1/0.0', 'mulRel' ],
			[ '1/(-0.0)', 'mulRel' ],
			[ '1%0', 'mulRel' ],
			[ '1%0.0', 'mulRel' ],
			[ '1%0.3', 'mulRel' ],
			[ '1%(-0.7)', 'mulRel' ],
			'DUNDEFINED numerator 1' => [ 'timestamp % 0', 'mulRel' ],
			'DUNDEFINED numerator 2' => [ 'timestamp / 0.0', 'mulRel' ],
		];
	}

	/**
	 * @param mixed $raw
	 * @param AFPData|null $expected If null, we expect an exception due to unsupported data type
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::newFromPHPVar
	 * @dataProvider providePHPVars
	 */
	public function testNewFromPHPVar( $raw, $expected ) {
		if ( $expected === null ) {
			$this->expectException( InternalException::class );
		}
		$this->assertEquals( $expected, AFPData::newFromPHPVar( $raw ) );
	}

	/**
	 * Data provider for testNewFromPHPVar
	 *
	 * @return array
	 */
	public static function providePHPVars() {
		return [
			[ 15, new AFPData( AFPData::DINT, 15 ) ],
			[ '42', new AFPData( AFPData::DSTRING, '42' ) ],
			[ 0.123, new AFPData( AFPData::DFLOAT, 0.123 ) ],
			[ false, new AFPData( AFPData::DBOOL, false ) ],
			[ true, new AFPData( AFPData::DBOOL, true ) ],
			[ null, new AFPData( AFPData::DNULL ) ],
			[
				[ 1, 'foo', [], [ null ], false ],
				new AFPData( AFPData::DARRAY, [
					new AFPData( AFPData::DINT, 1 ),
					new AFPData( AFPData::DSTRING, 'foo' ),
					new AFPData( AFPData::DARRAY, [] ),
					new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DNULL ) ] ),
					new AFPData( AFPData::DBOOL, false )
				] )
			],
			// Invalid data types
			[ (object)[], null ],
			[ new AFPData( AFPData::DUNDEFINED ), null ]
		];
	}

	/**
	 * Test casts to null and to arrays, for which we don't expose any method for use in actual
	 * filters. Other casts are already covered in parserTests.
	 *
	 * @param AFPData $orig
	 * @param string $newType One of the AFPData::D* constants
	 * @param AFPData|null $expected If null, we expect an exception due to unsupported data type
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::castTypes
	 * @dataProvider provideMissingCastTypes
	 */
	public function testMissingCastTypes( $orig, $newType, $expected ) {
		if ( $expected === null ) {
			$this->expectException( InternalException::class );
		}
		$this->assertEquals( $expected, AFPData::castTypes( $orig, $newType ) );
	}

	/**
	 * Data provider for testMissingCastTypes
	 *
	 * @return array
	 */
	public static function provideMissingCastTypes() {
		return [
			[ new AFPData( AFPData::DINT, 1 ), AFPData::DNULL, new AFPData( AFPData::DNULL ) ],
			[ new AFPData( AFPData::DBOOL, false ), AFPData::DNULL, new AFPData( AFPData::DNULL ) ],
			[ new AFPData( AFPData::DSTRING, 'foo' ), AFPData::DNULL, new AFPData( AFPData::DNULL ) ],
			[ new AFPData( AFPData::DFLOAT, 3.14 ), AFPData::DNULL, new AFPData( AFPData::DNULL ) ],
			[
				new AFPData( AFPData::DARRAY, [
					new AFPData( AFPData::DSTRING, 'foo' ),
					new AFPData( AFPData::DNULL )
				] ),
				AFPData::DNULL,
				new AFPData( AFPData::DNULL )
			],
			[
				new AFPData( AFPData::DINT, 1 ),
				AFPData::DARRAY,
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DINT, 1 ) ] )
			],
			[
				new AFPData( AFPData::DBOOL, false ),
				AFPData::DARRAY,
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DBOOL, false ) ] )
			],
			[
				new AFPData( AFPData::DSTRING, 'foo' ),
				AFPData::DARRAY,
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DSTRING, 'foo' ) ] )
			],
			[
				new AFPData( AFPData::DFLOAT, 3.14 ),
				AFPData::DARRAY,
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DFLOAT, 3.14 ) ] )
			],
			[
				new AFPData( AFPData::DNULL ),
				AFPData::DARRAY,
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DNULL ) ] )
			],
			[ new AFPData( AFPData::DSTRING, 'foo' ), 'foobaz', null ],
			[ new AFPData( AFPData::DNULL ), null, null ]
		];
	}

	/**
	 * @param AFPData $orig
	 * @param mixed $expected
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::toNative
	 * @dataProvider provideToNative
	 */
	public function testToNative( $orig, $expected ) {
		$this->assertEquals( $expected, $orig->toNative() );
	}

	/**
	 * Data provider for testToNative
	 *
	 * @return array
	 */
	public static function provideToNative() {
		return [
			[ new AFPData( AFPData::DFLOAT, 1.2345 ), 1.2345 ],
			[ new AFPData( AFPData::DFLOAT, 0.1 ), 0.1 ],
			[ new AFPData( AFPData::DUNDEFINED ), null ],
			[ new AFPData( AFPData::DNULL, null ), null ],
			[ new AFPData( AFPData::DBOOL, false ), false ],
			[ new AFPData( AFPData::DSTRING, '12' ), '12' ],
			[ new AFPData( AFPData::DINT, 123 ), 123 ],
			[
				new AFPData(
					AFPData::DARRAY,
					[ new AFPData( AFPData::DSTRING, 'foo' ), new AFPData( AFPData::DBOOL, true ) ]
				),
				[ 'foo', true ]
			],
		];
	}

	/**
	 * Ensure that we don't allow DUNDEFINED in AFPData::equals
	 *
	 * @param AFPData $lhs
	 * @param AFPData $rhs
	 * @dataProvider provideDUNDEFINEDEquals
	 *
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::equals
	 */
	public function testNoDUNDEFINEDEquals( $lhs, $rhs ) {
		$this->expectException( InternalException::class );
		$lhs->equals( $rhs );
	}

	/**
	 * Data provider for testNoDUNDEFINEDEquals
	 *
	 * @return array
	 */
	public static function provideDUNDEFINEDEquals() {
		$undefined = new AFPData( AFPData::DUNDEFINED );
		$nonempty = new AFPData( AFPData::DSTRING, 'foo' );
		return [
			'left' => [ $undefined, $nonempty ],
			'right' => [ $nonempty, $undefined ],
			'both' => [ $undefined, $undefined ]
		];
	}

	/**
	 * Test that DUNDEFINED can only have null value
	 *
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::__construct
	 */
	public function testDUNDEFINEDRequiresNullValue() {
		$this->expectException( InvalidArgumentException::class );
		new AFPData( AFPData::DUNDEFINED, 'non-null' );
	}

	/**
	 * Test that casting DUNDEFINED to something else is forbidden
	 *
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::castTypes
	 */
	public function testDUNDEFINEDCannotBeCast() {
		$data = new AFPData( AFPData::DUNDEFINED );
		$this->expectException( InternalException::class );
		$this->expectExceptionMessage( 'Refusing to cast' );
		AFPData::castTypes( $data, AFPData::DNULL );
	}

	/**
	 * @param AFPData $obj
	 * @param bool $expected
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::hasUndefined
	 * @dataProvider provideHasUndefined
	 */
	public function testHasUndefined( AFPData $obj, bool $expected ) {
		$this->assertSame( $expected, $obj->hasUndefined() );
	}

	/**
	 * Provider for testHasUndefined
	 */
	public static function provideHasUndefined() {
		return [
			[ new AFPData( AFPData::DUNDEFINED ), true ],
			[ new AFPData( AFPData::DNULL ), false ],
			[ new AFPData( AFPData::DSTRING, '' ), false ],
			[ new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DUNDEFINED ) ] ), true ],
			[ new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DNULL ) ] ), false ],
		];
	}

	/**
	 * @param AFPData $obj
	 * @param AFPData $expected
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData::cloneAsUndefinedReplacedWithNull
	 * @dataProvider provideCloneAsUndefinedReplacedWithNull
	 */
	public function testCloneAsUndefinedReplacedWithNull( AFPData $obj, AFPData $expected ) {
		$this->assertEquals( $expected, $obj->cloneAsUndefinedReplacedWithNull() );
	}

	/**
	 * Provider for testHasUndefined
	 */
	public static function provideCloneAsUndefinedReplacedWithNull() {
		return [
			[
				new AFPData( AFPData::DUNDEFINED ),
				new AFPData( AFPData::DNULL )
			],
			[
				new AFPData( AFPData::DNULL ),
				new AFPData( AFPData::DNULL )
			],
			[
				new AFPData( AFPData::DSTRING, '' ),
				new AFPData( AFPData::DSTRING, '' )
			],
			[
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DUNDEFINED ) ] ),
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DNULL ) ] )
			],
			[
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DNULL ) ] ),
				new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DNULL ) ] )
			],
		];
	}
}
