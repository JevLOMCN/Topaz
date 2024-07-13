<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences\Consequence;

use BagOStuff;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Throttle;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Warn;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Session\Session;
use MediaWiki\User\UserEditTracker;
use MediaWiki\User\UserFactory;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;

/**
 * Test for priorities of builtin ConsequencesDisablerConsequence classes
 */
class BuiltinPrioritiesTest extends MediaWikiUnitTestCase {

	/**
	 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Throttle::getSort
	 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Warn::getSort
	 */
	public function testThrottleMoreImportantThanWarn() {
		$throttle = new Throttle(
			$this->createMock( Parameters::class ),
			[],
			$this->createMock( BagOStuff::class ),
			$this->createMock( UserEditTracker::class ),
			$this->createMock( UserFactory::class ),
			new NullLogger(),
			'',
			false
		);
		$warn = new Warn(
			$this->createMock( Parameters::class ),
			'',
			$this->createMock( Session::class )
		);
		$this->assertLessThan( $warn->getSort(), $throttle->getSort() );
	}
}
