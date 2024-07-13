<?php

namespace Cite\Tests\Unit;

use Cite\Cite;
use Cite\Parsoid\RefGroup;
use MediaWikiUnitTestCase;

/**
 * @covers \Cite\Parsoid\RefGroup
 * @license GPL-2.0-or-later
 */
class RefGroupTest extends MediaWikiUnitTestCase {

	public function testMinimalSetup() {
		$group = new RefGroup();
		$this->assertSame( Cite::DEFAULT_GROUP, $group->name );
		$this->assertSame( [], $group->refs );
		$this->assertSame( [], $group->indexByName );
	}

	// TODO: Incomplete, at least the renderLine method should be covered

}
