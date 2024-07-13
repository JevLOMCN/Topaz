<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Filter;

use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Filter\Specs
 */
class SpecsTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 * @covers ::getRules
	 * @covers ::getComments
	 * @covers ::getName
	 * @covers ::getActionsNames
	 * @covers ::getGroup
	 */
	public function testGetters() {
		$rules = 'rules';
		$comments = 'comments';
		$name = 'name';
		$actions = [ 'foo', 'bar' ];
		$group = 'group';
		$specs = new Specs( $rules, $comments, $name, $actions, $group );

		$this->assertSame( $rules, $specs->getRules(), 'rules' );
		$this->assertSame( $comments, $specs->getComments(), 'comments' );
		$this->assertSame( $name, $specs->getName(), 'name' );
		$this->assertSame( $actions, $specs->getActionsNames(), 'actions' );
		$this->assertSame( $group, $specs->getGroup(), 'group' );
	}

	/**
	 * @param mixed $value
	 * @param string $setter
	 * @param string $getter
	 * @covers ::setRules
	 * @covers ::setComments
	 * @covers ::setName
	 * @covers ::setActionsNames
	 * @covers ::setGroup
	 * @dataProvider provideSetters
	 */
	public function testSetters( $value, string $setter, string $getter ) {
		$specs = new Specs( 'r', 'c', 'n', [], 'g' );

		$specs->$setter( $value );
		$this->assertSame( $value, $specs->$getter() );
	}

	/**
	 * @return array
	 */
	public static function provideSetters() {
		return [
			'rules' => [ 'rules', 'setRules', 'getRules' ],
			'comments' => [ 'comments', 'setComments', 'getComments' ],
			'name' => [ 'name', 'setName', 'getName' ],
			'actions' => [ [ 'x', 'y' ], 'setActionsNames', 'getActionsNames' ],
			'group' => [ 'group', 'setGroup', 'getGroup' ],
		];
	}
}
