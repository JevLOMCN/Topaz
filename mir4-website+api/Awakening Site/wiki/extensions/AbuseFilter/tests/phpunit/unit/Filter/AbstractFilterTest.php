<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Filter;

use MediaWiki\Extension\AbuseFilter\Filter\AbstractFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWikiUnitTestCase;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Filter\AbstractFilter
 */
class AbstractFilterTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 */
	public function testConstruct_invalidActions() {
		$this->expectException( ParameterTypeException::class );
		new AbstractFilter(
			$this->createMock( Specs::class ),
			$this->createMock( Flags::class ),
			'notcallablenorarray'
		);
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct_actionsFormats() {
		$specs = $this->createMock( Specs::class );
		$flags = $this->createMock( Flags::class );

		$this->assertInstanceOf(
			AbstractFilter::class,
			new AbstractFilter( $specs, $flags, [] ),
			'array'
		);
		$this->assertInstanceOf(
			AbstractFilter::class,
			new AbstractFilter( $specs, $flags, 'strlen' ),
			'callable'
		);
	}

	/**
	 * @covers ::__construct
	 * @covers ::getRules
	 * @covers ::getComments
	 * @covers ::getName
	 * @covers ::getActionsNames
	 * @covers ::getGroup
	 * @covers ::isEnabled
	 * @covers ::isDeleted
	 * @covers ::isHidden
	 * @covers ::isGlobal
	 */
	public function testValueGetters() {
		$rules = 'rules';
		$comments = 'comments';
		$name = 'name';
		$actionsNames = [ 'foo' ];
		$group = 'group';
		$enabled = true;
		$deleted = false;
		$hidden = true;
		$global = false;
		$filter = new AbstractFilter(
			new Specs( $rules, $comments, $name, $actionsNames, $group ),
			new Flags( $enabled, $deleted, $hidden, $global ),
			[ 'foo' => [] ]
		);

		$this->assertSame( $rules, $filter->getRules(), 'rules' );
		$this->assertSame( $comments, $filter->getComments(), 'comments' );
		$this->assertSame( $name, $filter->getName(), 'name' );
		$this->assertSame( $actionsNames, $filter->getActionsNames(), 'actions names' );
		$this->assertSame( $group, $filter->getGroup(), 'group' );
		$this->assertSame( $enabled, $filter->isEnabled(), 'enabled' );
		$this->assertSame( $deleted, $filter->isDeleted(), 'deleted' );
		$this->assertSame( $hidden, $filter->isHidden(), 'hidden' );
		$this->assertSame( $global, $filter->isGlobal(), 'global' );
	}

	/**
	 * @covers ::getSpecs
	 * @covers ::getFlags
	 */
	public function testGetObjects() {
		$specs = $this->createMock( Specs::class );
		$flags = $this->createMock( Flags::class );
		$filter = new AbstractFilter( $specs, $flags, [] );

		$this->assertEquals( $specs, $filter->getSpecs(), 'equal specs' );
		$this->assertNotSame( $specs, $filter->getSpecs(), 'not identical specs' );

		$this->assertEquals( $flags, $filter->getFlags(), 'equal flags' );
		$this->assertNotSame( $flags, $filter->getFlags(), 'not identical flags' );
	}

	/**
	 * @param array|callable $value
	 * @param array $expected
	 * @covers ::getActions
	 * @covers ::setActions
	 * @dataProvider provideActions
	 */
	public function testActions( $value, array $expected ) {
		$filter = new AbstractFilter(
			new Specs( 'rules', 'comments', 'name', [], 'group' ),
			$this->createMock( Flags::class ),
			$value
		);
		$this->assertSame( $expected, $filter->getActions(), 'actions' );
		$this->assertSame( array_keys( $expected ), $filter->getActionsNames(), 'actions names' );
	}

	/**
	 * @return array[]
	 */
	public static function provideActions() {
		return [
			'array' => [
				[ 'foo' => [] ],
				[ 'foo' => [] ]
			],
			'callable' => [
				static function () {
					return [ 'bar' => [] ];
				},
				[ 'bar' => [] ]
			]
		];
	}

	/**
	 * @covers ::__construct
	 * @covers ::__clone
	 */
	public function testNoWriteableReferences() {
		$oldRules = 'rules';
		$specs = new Specs( $oldRules, 'x', 'x', [], 'x' );
		$oldEnabled = true;
		$flags = new Flags( $oldEnabled, true, true, true );
		$filter = new AbstractFilter( $specs, $flags, [] );
		$copy = clone $filter;

		$specs->setRules( 'new rules' );
		$this->assertSame( $oldRules, $filter->getRules(), 'original' );
		$this->assertSame( $oldRules, $copy->getRules(), 'copy' );

		$flags->setEnabled( false );
		$this->assertSame( $oldEnabled, $filter->isEnabled(), 'original' );
		$this->assertSame( $oldEnabled, $copy->isEnabled(), 'copy' );
	}
}
