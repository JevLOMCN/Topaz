<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\AbuseLoggerFactory;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutorFactory;
use MediaWiki\Extension\AbuseFilter\EditStashCache;
use MediaWiki\Extension\AbuseFilter\EmergencyCache;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\FilterProfiler;
use MediaWiki\Extension\AbuseFilter\FilterRunner;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterRunner
 * @covers ::__construct
 */
class FilterRunnerTest extends MediaWikiIntegrationTestCase {
	/**
	 * @param ChangeTagger|null $changeTagger
	 * @param EditStashCache|null $cache
	 * @param VariableHolder|null $vars
	 * @param string $group
	 * @return FilterRunner
	 */
	private function getRunner(
		ChangeTagger $changeTagger = null,
		EditStashCache $cache = null,
		VariableHolder $vars = null,
		$group = 'default'
	): FilterRunner {
		$opts = new ServiceOptions(
			FilterRunner::CONSTRUCTOR_OPTIONS,
			[
				'AbuseFilterValidGroups' => [ 'default' ],
				'AbuseFilterCentralDB' => false,
				'AbuseFilterIsCentral' => false,
				'AbuseFilterConditionLimit' => 1000,
			]
		);
		if ( $cache === null ) {
			$cache = $this->createMock( EditStashCache::class );
			$cache->method( 'seek' )->willReturn( false );
		}
		$request = $this->createMock( WebRequest::class );
		$request->method( 'getIP' )->willReturn( '127.0.0.1' );
		$user = $this->createMock( User::class );
		$user->method( 'getRequest' )->willReturn( $request );
		return new FilterRunner(
			new AbuseFilterHookRunner( $this->createHookContainer() ),
			$this->createMock( FilterProfiler::class ),
			$changeTagger ?? $this->createMock( ChangeTagger::class ),
			$this->createMock( FilterLookup::class ),
			$this->createMock( RuleCheckerFactory::class ),
			$this->createMock( ConsequencesExecutorFactory::class ),
			$this->createMock( AbuseLoggerFactory::class ),
			$this->createMock( VariablesManager::class ),
			$this->createMock( VariableGeneratorFactory::class ),
			$this->createMock( EmergencyCache::class ),
			[],
			$cache,
			new NullLogger(),
			$opts,
			$user,
			$this->createMock( Title::class ),
			$vars ?? VariableHolder::newFromArray( [ 'action' => 'edit' ] ),
			$group
		);
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstructor_invalidGroup() {
		$invalidGroup = 'invalid-group';
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( $invalidGroup );
		$this->getRunner( null, null, new VariableHolder(), $invalidGroup );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstructor_noAction() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'variable is not set' );
		$this->getRunner( null, null, new VariableHolder() );
	}

	/**
	 * @covers ::run
	 * @covers ::checkAllFilters
	 */
	public function testConditionsLimit() {
		$cache = $this->createMock( EditStashCache::class );
		$cache->method( 'seek' )->willReturn( [
			'vars' => [],
			'data' => [
				'matches' => [],
				'condCount' => 2000,
				'runtime' => 100.0,
				'profiling' => []
			]
		] );
		$changeTagger = $this->createMock( ChangeTagger::class );
		$changeTagger->expects( $this->once() )->method( 'addConditionsLimitTag' );
		$runner = $this->getRunner( $changeTagger, $cache );
		$this->assertStatusGood( $runner->run() );
	}
}
