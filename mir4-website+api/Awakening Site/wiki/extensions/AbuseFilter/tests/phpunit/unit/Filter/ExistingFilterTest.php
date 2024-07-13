<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Filter;

use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter
 */
class ExistingFilterTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 * @covers ::getID
	 */
	public function testGetID() {
		$id = 163;
		$filter = new ExistingFilter(
			$this->createMock( Specs::class ),
			$this->createMock( Flags::class ),
			[],
			$this->createMock( LastEditInfo::class ),
			$id,
			null,
			null
		);

		$this->assertSame( $id, $filter->getID() );
	}
}
