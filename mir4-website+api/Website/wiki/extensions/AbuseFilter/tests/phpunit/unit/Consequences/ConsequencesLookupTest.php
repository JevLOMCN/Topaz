<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences;

use MediaWiki\Extension\AbuseFilter\CentralDBManager;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesLookup;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;
use Wikimedia\Rdbms\LBFactory;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesLookup
 * @todo Write unit tests (non-trivial because the class is tied to a DB)
 */
class ConsequencesLookupTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 */
	public function testConstructor() {
		$this->assertInstanceOf(
			ConsequencesLookup::class,
			new ConsequencesLookup(
				$this->createMock( LBFactory::class ),
				$this->createMock( CentralDBManager::class ),
				$this->createMock( ConsequencesRegistry::class ),
				new NullLogger()
			)
		);
	}
}
