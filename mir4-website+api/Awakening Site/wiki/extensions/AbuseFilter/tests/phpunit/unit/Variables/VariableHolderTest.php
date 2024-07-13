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

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use MediaWiki\Extension\AbuseFilter\Parser\AFPData;
use MediaWiki\Extension\AbuseFilter\Variables\LazyLoadedVariable;
use MediaWiki\Extension\AbuseFilter\Variables\UnsetVariableException;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Variables\VariableHolder
 */
class VariableHolderTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::newFromArray
	 */
	public function testNewFromArray() {
		$vars = [
			'foo' => 12,
			'bar' => [ 'x', 'y' ],
			'baz' => false
		];
		$actual = VariableHolder::newFromArray( $vars );
		$expected = new VariableHolder();
		foreach ( $vars as $var => $value ) {
			$expected->setVar( $var, $value );
		}

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @covers ::setVar
	 */
	public function testVarsAreLowercased() {
		$vars = new VariableHolder();
		$this->assertCount( 0, $vars->getVars(), 'precondition' );
		$vars->setVar( 'FOO', 42 );
		$this->assertCount( 1, $vars->getVars(), 'variable should be set' );
		$this->assertArrayHasKey( 'foo', $vars->getVars(), 'var should be lowercase' );
	}

	/**
	 * @param string $name
	 * @param mixed $val
	 * @param mixed $expected
	 *
	 * @dataProvider provideSetVar
	 *
	 * @covers ::setVar
	 */
	public function testSetVar( string $name, $val, $expected ) {
		$vars = new VariableHolder();
		$vars->setVar( $name, $val );
		$this->assertEquals( $expected, $vars->getVars()[$name] );
	}

	public static function provideSetVar() {
		yield 'native' => [ 'foo', 12, new AFPData( AFPData::DINT, 12 ) ];

		$afpdata = new AFPData( AFPData::DSTRING, 'foobar' );
		yield 'AFPData' => [ 'foo', $afpdata, $afpdata ];

		$lazyloadVar = new LazyLoadedVariable( 'foo', [] );
		yield 'lazy-loaded' => [ 'foo', $lazyloadVar, $lazyloadVar ];
	}

	/**
	 * @covers ::getVars
	 */
	public function testGetVars() {
		$vars = new VariableHolder();
		$this->assertSame( [], $vars->getVars(), 'precondition' );

		$vars->setVar( 'foo', [ true ] );
		$vars->setVar( 'bar', 'bar' );
		$exp = [
			'foo' => new AFPData( AFPData::DARRAY, [ new AFPData( AFPData::DBOOL, true ) ] ),
			'bar' => new AFPData( AFPData::DSTRING, 'bar' )
		];

		$this->assertEquals( $exp, $vars->getVars() );
	}

	/**
	 * @param VariableHolder $vars
	 * @param string $name
	 * @param AFPData|LazyLoadedVariable $expected
	 * @covers ::getVarThrow
	 *
	 * @dataProvider provideGetVarThrow
	 */
	public function testGetVarThrow( VariableHolder $vars, string $name, $expected ) {
		$this->assertEquals( $expected, $vars->getVarThrow( $name ) );
	}

	public static function provideGetVarThrow() {
		$vars = new VariableHolder();

		$name = 'foo';
		$afcv = new LazyLoadedVariable( 'method', [ 'param' ] );
		$vars->setVar( $name, $afcv );
		yield 'set, lazy-loaded' => [ $vars, $name, $afcv ];

		$name = 'afpd';
		$afpd = new AFPData( AFPData::DINT, 42 );
		$vars->setVar( $name, $afpd );
		yield 'set, AFPData' => [ $vars, $name, $afpd ];
	}

	/**
	 * @covers ::getVarThrow
	 */
	public function testGetVarThrow_unset() {
		$vars = new VariableHolder();
		$this->expectException( UnsetVariableException::class );
		$vars->getVarThrow( 'unset-variable' );
	}

	/**
	 * @param array $expected
	 * @param VariableHolder ...$holders
	 * @dataProvider provideHoldersForAddition
	 *
	 * @covers ::addHolders
	 */
	public function testAddHolders( array $expected, VariableHolder ...$holders ) {
		$actual = new VariableHolder();
		$actual->addHolders( ...$holders );

		$this->assertEquals( $expected, $actual->getVars() );
	}

	public static function provideHoldersForAddition() {
		$v1 = VariableHolder::newFromArray( [ 'a' => 1, 'b' => 2 ] );
		$v2 = VariableHolder::newFromArray( [ 'b' => 3, 'c' => 4 ] );
		$v3 = VariableHolder::newFromArray( [ 'c' => 5, 'd' => 6 ] );

		$expected = [
			'a' => new AFPData( AFPData::DINT, 1 ),
			'b' => new AFPData( AFPData::DINT, 3 ),
			'c' => new AFPData( AFPData::DINT, 5 ),
			'd' => new AFPData( AFPData::DINT, 6 )
		];

		return [ [ $expected, $v1, $v2, $v3 ] ];
	}

	/**
	 * @covers ::varIsSet
	 */
	public function testVarIsSet() {
		$vars = new VariableHolder();
		$vars->setVar( 'foo', null );
		$this->assertTrue( $vars->varIsSet( 'foo' ), 'Set variable should be set' );
		$this->assertFalse( $vars->varIsSet( 'foobarbaz' ), 'Unset variable should be unset' );
	}

	/**
	 * @covers ::setLazyLoadVar
	 */
	public function testLazyLoader() {
		$var = 'foobar';
		$method = 'compute-foo';
		$params = [ 'baz', 1 ];
		$exp = new LazyLoadedVariable( $method, $params );

		$vars = new VariableHolder();
		$vars->setLazyLoadVar( $var, $method, $params );
		$this->assertEquals( $exp, $vars->getVars()[$var] );
	}

	/**
	 * @covers ::removeVar
	 */
	public function testRemoveVar() {
		$vars = new VariableHolder();
		$varName = 'foo';
		$vars->setVar( $varName, 'foobar' );
		$this->assertInstanceOf( AFPData::class, $vars->getVarThrow( $varName ) );
		$vars->removeVar( $varName );
		$this->expectException( UnsetVariableException::class );
		$vars->getVarThrow( $varName );
	}
}
