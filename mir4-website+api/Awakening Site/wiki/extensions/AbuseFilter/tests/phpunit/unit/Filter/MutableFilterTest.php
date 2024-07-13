<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Filter;

use LogicException;
use MediaWiki\Extension\AbuseFilter\Filter\Filter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\MutableFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Filter\MutableFilter
 */
class MutableFilterTest extends MediaWikiUnitTestCase {
	/**
	 * @param mixed $value
	 * @param string $setter
	 * @param string $getter
	 * @covers ::setRules
	 * @covers ::setComments
	 * @covers ::setName
	 * @covers ::setActionsNames
	 * @covers ::setGroup
	 * @covers ::setEnabled
	 * @covers ::setDeleted
	 * @covers ::setHidden
	 * @covers ::setGlobal
	 * @covers ::setActions
	 * @covers ::setUserID
	 * @covers ::setUserName
	 * @covers ::setTimestamp
	 * @covers ::setID
	 * @covers ::setHitCount
	 * @covers ::setThrottled
	 * @dataProvider provideSetters
	 */
	public function testSetters( $value, string $setter, string $getter ) {
		// Set a bogus callable, not an array or we won't be able to use setActionsNames
		$fakeActions = 'strlen';
		$filter = new MutableFilter(
			new Specs( 'rules', 'comments', 'name', [], 'group' ),
			new Flags( true, true, true, true ),
			$fakeActions,
			new LastEditInfo( 42, 'User', '12345' )
		);

		$filter->$setter( $value );
		$this->assertSame( $value, $filter->$getter() );
	}

	/**
	 * @return array
	 */
	public static function provideSetters() {
		return [
			'rules' => [ 'rules', 'setRules', 'getRules' ],
			'comments' => [ 'comments', 'setComments', 'getComments' ],
			'name' => [ 'name', 'setName', 'getName' ],
			'actions names' => [ [ 'x', 'y' ], 'setActionsNames', 'getActionsNames' ],
			'group' => [ 'group', 'setGroup', 'getGroup' ],
			'enabled' => [ true, 'setEnabled', 'isEnabled' ],
			'deleted' => [ false, 'setDeleted', 'isDeleted' ],
			'hidden' => [ true, 'setHidden', 'isHidden' ],
			'global' => [ false, 'setGlobal', 'isGlobal' ],
			'actions' => [ [ 'foo' => [] ], 'setActions', 'getActions' ],
			'user ID' => [ 163, 'setUserID', 'getUserID' ],
			'username' => [ 'Sysop', 'setUserName', 'getUserName' ],
			'timestamp' => [ '123456', 'setTimestamp', 'getTimestamp' ],
			'id' => [ 123, 'setID', 'getID' ],
			'hit count' => [ 456, 'setHitCount', 'getHitCount' ],
			'throttled' => [ true, 'setThrottled', 'isThrottled' ],
		];
	}

	/**
	 * @covers ::setActionsNames
	 */
	public function testSetActionsNames_withActionsSet() {
		$filter = new MutableFilter(
			new Specs( 'rules', 'comments', 'name', [], 'group' ),
			new Flags( true, true, true, true ),
			[ 'foo' => [] ],
			new LastEditInfo( 42, 'User', '12345' )
		);
		$this->expectException( LogicException::class );
		$filter->setActionsNames( [ 'x' ] );
	}

	/**
	 * @covers ::newFromParentFilter
	 */
	public function testNewFromParentFilter() {
		$baseFilter = new Filter(
			new Specs( 'rules', 'comments', 'name', [ 'x' ], 'group' ),
			new Flags( true, false, true, false ),
			[ 'foobar' => [] ],
			new LastEditInfo( 111, 'User', '4563681' ),
			42,
			12345,
			true
		);
		$actual = MutableFilter::newFromParentFilter( $baseFilter );

		$this->assertEquals( $baseFilter->getSpecs(), $actual->getSpecs(), 'equal specs' );
		$this->assertNotSame( $baseFilter->getSpecs(), $actual->getSpecs(), 'not identical specs' );

		$this->assertEquals( $baseFilter->getFlags(), $actual->getFlags(), 'equal flags' );
		$this->assertNotSame( $baseFilter->getFlags(), $actual->getFlags(), 'not identical flags' );

		$this->assertEquals( $baseFilter->getLastEditInfo(), $actual->getLastEditInfo(), 'equal last edit info' );
		$this->assertNotSame(
			$baseFilter->getLastEditInfo(),
			$actual->getLastEditInfo(),
			'not identical last edit info'
		);

		$this->assertSame( $baseFilter->getID(), $actual->getID(), 'ID' );
		$this->assertSame( $baseFilter->getHitCount(), $actual->getHitCount(), 'hit count' );
		$this->assertSame( $baseFilter->isThrottled(), $actual->isThrottled(), 'throttled' );

		// TODO Enable
		// $this->assertSame( $baseFilter->getActions(), $actual->getActions(), 'actions' );
	}
}
