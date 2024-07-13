<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Parser;

use BagOStuff;
use Language;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWikiUnitTestCase;
use NullStatsdDataFactory;
use Psr\Log\NullLogger;
use Wikimedia\Equivset\Equivset;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 *
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory
 */
class RuleCheckerFactoryTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 * @covers ::newRuleChecker
	 */
	public function testNewRuleChecker() {
		$factory = new RuleCheckerFactory(
			$this->createMock( Language::class ),
			$this->createMock( BagOStuff::class ),
			new NullLogger(),
			$this->createMock( KeywordsManager::class ),
			$this->createMock( VariablesManager::class ),
			new NullStatsdDataFactory(),
			$this->createMock( Equivset::class ),
			1000
		);
		$this->assertInstanceOf( FilterEvaluator::class, $factory->newRuleChecker() );
	}
}
