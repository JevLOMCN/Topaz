<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\VariableGenerator;

use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\TextExtractor;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWikiUnitTestCase;
use MimeAnalyzer;
use RecentChange;
use RepoGroup;

/**
 * @group Test
 * @group AbuseFilter
 *
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory
 * @covers ::__construct
 */
class VariableGeneratorFactoryTest extends MediaWikiUnitTestCase {
	private function getFactory(): VariableGeneratorFactory {
		return new VariableGeneratorFactory(
			$this->createMock( AbuseFilterHookRunner::class ),
			$this->createMock( TextExtractor::class ),
			$this->createMock( MimeAnalyzer::class ),
			$this->createMock( RepoGroup::class ),
			$this->createMock( WikiPageFactory::class ),
			$this->createMock( UserFactory::class )
		);
	}

	/**
	 * @covers ::newGenerator
	 */
	public function testNewGenerator() {
		$this->getFactory()->newGenerator( new VariableHolder() );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newRunGenerator
	 */
	public function testNewRunGenerator() {
		$this->getFactory()->newRunGenerator(
			$this->createMock( User::class ),
			$this->createMock( Title::class ),
			new VariableHolder()
		);
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newRCGenerator
	 */
	public function testNewRCGenerator() {
		$this->getFactory()->newRCGenerator(
			$this->createMock( RecentChange::class ),
			$this->createMock( User::class ),
			new VariableHolder()
		);
		$this->addToAssertionCount( 1 );
	}
}
