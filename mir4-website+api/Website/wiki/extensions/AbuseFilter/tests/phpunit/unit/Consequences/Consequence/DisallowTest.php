<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences\Consequence;

use ConsequenceGetMessageTestTrait;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Disallow;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Disallow
 * @covers ::__construct
 */
class DisallowTest extends MediaWikiUnitTestCase {
	use ConsequenceGetMessageTestTrait;

	/**
	 * @covers ::execute
	 */
	public function testExecute() {
		$disallow = new Disallow( $this->createMock( Parameters::class ), '' );
		$this->assertTrue( $disallow->execute() );
	}

	/**
	 * @covers ::getMessage
	 * @dataProvider provideGetMessageParameters
	 */
	public function testGetMessage( Parameters $params ) {
		$msg = 'some-disallow-message';
		$rangeBlock = new Disallow( $params, $msg );
		$this->doTestGetMessage( $rangeBlock, $params, $msg );
	}
}
