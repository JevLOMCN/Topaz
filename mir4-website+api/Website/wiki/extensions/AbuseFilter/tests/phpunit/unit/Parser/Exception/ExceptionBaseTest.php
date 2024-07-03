<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Parser\Exception;

use MediaWiki\Extension\AbuseFilter\Parser\Exception\InternalException;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 *
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Parser\Exception\ExceptionBase
 */
class ExceptionBaseTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::toArray
	 * @covers ::fromArray
	 */
	public function testToArrayRoundTrip() {
		$exc = new InternalException( 'Foo' );
		$newExc = InternalException::fromArray( $exc->toArray() );
		$this->assertSame( $exc->getMessage(), $newExc->getMessage() );
	}

}
