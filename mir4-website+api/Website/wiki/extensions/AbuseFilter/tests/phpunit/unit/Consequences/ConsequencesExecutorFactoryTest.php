<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutorFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesLookup;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\User\UserIdentityUtils;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutorFactory
 */
class ConsequencesExecutorFactoryTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::newExecutor
	 */
	public function testNewExecutor() {
		$factory = new ConsequencesExecutorFactory(
			$this->createMock( ConsequencesLookup::class ),
			$this->createMock( ConsequencesFactory::class ),
			$this->createMock( ConsequencesRegistry::class ),
			$this->createMock( FilterLookup::class ),
			new NullLogger(),
			$this->createMock( UserIdentityUtils::class ),
			$this->createMock( ServiceOptions::class )
		);
		$factory->newExecutor(
			$this->createMock( ActionSpecifier::class ),
			new VariableHolder()
		);
		$this->addToAssertionCount( 1 );
	}
}
