<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Block;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Throttle;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Warn;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutor;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesLookup;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityUtils;
use MediaWikiUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutor
 */
class ConsequencesExecutorTest extends MediaWikiUnitTestCase {
	/**
	 * Returns a ConsequencesFactory where:
	 *  - all the ConsequenceDisablerConsequence's created will disable other consequences.
	 *  - the block expiry is set as normal
	 * @return ConsequencesFactory|MockObject
	 */
	private function getConsequencesFactory() {
		$consFactory = $this->createMock( ConsequencesFactory::class );
		$warn = $this->createMock( Warn::class );
		$warn->method( 'shouldDisableOtherConsequences' )->willReturn( true );
		$consFactory->method( 'newWarn' )->willReturn( $warn );
		$throttle = $this->createMock( Throttle::class );
		$throttle->method( 'shouldDisableOtherConsequences' )->willReturn( true );
		$consFactory->method( 'newThrottle' )->willReturn( $throttle );
		$consFactory->method( 'newBlock' )->willReturnCallback(
			function ( Parameters $params, string $expiry, bool $preventsTalk ): Block {
				$block = $this->createMock( Block::class );
				$block->method( 'getExpiry' )->willReturn( $expiry );
				return $block;
			}
		);
		return $consFactory;
	}

	private function getConsExecutor( array $consequences ): ConsequencesExecutor {
		$locallyDisabledActions = [
			'flag' => false,
			'throttle' => false,
			'warn' => false,
			'disallow' => false,
			'blockautopromote' => true,
			'block' => true,
			'rangeblock' => true,
			'degroup' => true,
			'tag' => false
		];
		$options = new ServiceOptions(
			ConsequencesExecutor::CONSTRUCTOR_OPTIONS,
			[
				'AbuseFilterLocallyDisabledGlobalActions' => $locallyDisabledActions,
				'AbuseFilterBlockDuration' => '24 hours',
				'AbuseFilterAnonBlockDuration' => '24 hours',
				'AbuseFilterBlockAutopromoteDuration' => '5 days',
			]
		);
		$consRegistry = $this->createMock( ConsequencesRegistry::class );
		$dangerousActions = TestingAccessWrapper::constant( ConsequencesRegistry::class, 'DANGEROUS_ACTIONS' );
		$consRegistry->method( 'getDangerousActionNames' )->willReturn( $dangerousActions );
		$consLookup = $this->createMock( ConsequencesLookup::class );
		$consLookup->expects( $this->atLeastOnce() )
			->method( 'getConsequencesForFilters' )
			->with( array_keys( $consequences ) )
			->willReturn( $consequences );

		return new ConsequencesExecutor(
			$consLookup,
			$this->getConsequencesFactory(),
			$consRegistry,
			$this->createMock( FilterLookup::class ),
			new NullLogger,
			$this->createMock( UserIdentityUtils::class ),
			$options,
			new ActionSpecifier(
				'edit',
				$this->createMock( LinkTarget::class ),
				$this->createMock( UserIdentity::class ),
				'1.2.3.4',
				null
			),
			new VariableHolder
		);
	}

	/**
	 * @param array $rawConsequences A raw, unfiltered list of consequences
	 * @param array $expectedKeys
	 *
	 * @covers ::getActualConsequencesToExecute
	 * @covers ::replaceLegacyParameters
	 * @covers ::specializeParameters
	 * @covers ::removeForbiddenConsequences
	 * @covers ::replaceArraysWithConsequences
	 * @covers ::applyConsequenceDisablers
	 * @covers ::deduplicateConsequences
	 * @covers ::removeRedundantConsequences
	 * @dataProvider provideConsequences
	 */
	public function testGetActualConsequencesToExecute(
		array $rawConsequences,
		array $expectedKeys
	): void {
		$executor = $this->getConsExecutor( $rawConsequences );
		$actual = $executor->getActualConsequencesToExecute( array_keys( $rawConsequences ) );

		$actualKeys = [];
		foreach ( $actual as $filter => $actions ) {
			$actualKeys[$filter] = array_keys( $actions );
		}

		$this->assertEquals( $expectedKeys, $actualKeys );
	}

	/**
	 * @return array
	 */
	public static function provideConsequences(): array {
		return [
			'warn and throttle exclude other actions' => [
				[
					2 => [
						'warn' => [
							'abusefilter-warning'
						],
						'tag' => [
							'some tag'
						]
					],
					13 => [
						'throttle' => [
							'13',
							'14,15',
							'user'
						],
						'disallow' => []
					],
					168 => [
						'degroup' => []
					]
				],
				[
					2 => [ 'warn' ],
					13 => [ 'throttle' ],
					168 => [ 'degroup' ]
				],
			],
			'warn excludes other actions, block excludes disallow' => [
				[
					3 => [
						'tag' => [
							'some tag'
						]
					],
					'global-2' => [
						'warn' => [
							'abusefilter-beautiful-warning'
						],
						'degroup' => []
					],
					4 => [
						'disallow' => [],
						'block' => [
							'blocktalk',
							'15 minutes',
							'indefinite'
						]
					]
				],
				[
					3 => [ 'tag' ],
					'global-2' => [ 'warn' ],
					4 => [ 'block' ]
				],
			],
			'some global actions are disabled locally, the longest block is chosen' => [
				[
					'global-1' => [
						'blockautopromote' => [],
						'block' => [
							'blocktalk',
							'indefinite',
							'indefinite'
						]
					],
					1 => [
						'block' => [
							'blocktalk',
							'4 hours',
							'4 hours'
						]
					],
					2 => [
						'degroup' => [],
						'block' => [
							'blocktalk',
							'infinity',
							'never'
						]
					]
				],
				[
					'global-1' => [],
					1 => [],
					2 => [ 'degroup', 'block' ]
				],
			],
			'do not use a block that will be skipped as the longer one' => [
				[
					1 => [
						'warn' => [
							'abusefilter-warning'
						],
						'block' => [
							'blocktalk',
							'4 hours',
							'4 hours'
						]
					],
					2 => [
						'block' => [
							'blocktalk',
							'2 hours',
							'2 hours'
						]
					]
				],
				[
					1 => [ 'warn' ],
					2 => [ 'block' ]
				],
			],
		];
	}
}
