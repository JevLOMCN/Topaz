<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use HashBagOStuff;
use IBufferingStatsdDataFactory;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\AbuseLoggerFactory;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutorFactory;
use MediaWiki\Extension\AbuseFilter\EmergencyCache;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\FilterProfiler;
use MediaWiki\Extension\AbuseFilter\FilterRunner;
use MediaWiki\Extension\AbuseFilter\FilterRunnerFactory;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Extension\AbuseFilter\Watcher\EmergencyWatcher;
use MediaWiki\Extension\AbuseFilter\Watcher\UpdateHitCountWatcher;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterRunnerFactory
 */
class FilterRunnerFactoryTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 * @covers ::newRunner
	 */
	public function testNewRunner() {
		$opts = new ServiceOptions(
			FilterRunner::CONSTRUCTOR_OPTIONS,
			[
				'AbuseFilterValidGroups' => [ 'default' ],
				'AbuseFilterCentralDB' => false,
				'AbuseFilterIsCentral' => false,
				'AbuseFilterConditionLimit' => 1000,
			]
		);
		$factory = new FilterRunnerFactory(
			$this->createMock( AbuseFilterHookRunner::class ),
			$this->createMock( FilterProfiler::class ),
			$this->createMock( ChangeTagger::class ),
			$this->createMock( FilterLookup::class ),
			$this->createMock( RuleCheckerFactory::class ),
			$this->createMock( ConsequencesExecutorFactory::class ),
			$this->createMock( AbuseLoggerFactory::class ),
			$this->createMock( VariablesManager::class ),
			$this->createMock( VariableGeneratorFactory::class ),
			$this->createMock( EmergencyCache::class ),
			$this->createMock( UpdateHitCountWatcher::class ),
			$this->createMock( EmergencyWatcher::class ),
			new HashBagOStuff(),
			new NullLogger(),
			new NullLogger(),
			$this->createMock( IBufferingStatsdDataFactory::class ),
			$opts
		);

		$factory->newRunner(
			$this->createMock( User::class ),
			$this->createMock( Title::class ),
			VariableHolder::newFromArray( [ 'action' => 'edit' ] ),
			'default'
		);
		$this->addToAssertionCount( 1 );
	}
}
