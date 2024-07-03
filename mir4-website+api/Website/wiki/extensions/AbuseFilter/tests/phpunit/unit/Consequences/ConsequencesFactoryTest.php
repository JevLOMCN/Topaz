<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences;

use HashBagOStuff;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Session\Session;
use MediaWiki\User\UserEditTracker;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentityUtils;
use MediaWikiUnitTestCase;
use MessageLocalizer;
use Psr\Log\NullLogger;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesFactory
 * @covers ::__construct
 */
class ConsequencesFactoryTest extends MediaWikiUnitTestCase {

	private function getFactory(): ConsequencesFactory {
		$opts = new ServiceOptions(
			ConsequencesFactory::CONSTRUCTOR_OPTIONS,
			[
				'AbuseFilterCentralDB' => false,
				'AbuseFilterIsCentral' => false,
				'AbuseFilterRangeBlockSize' => [],
				'BlockCIDRLimit' => [],
			]
		);

		$consequencesFactory = new ConsequencesFactory(
			$opts,
			new NullLogger(),
			$this->createMock( BlockUserFactory::class ),
			$this->createMock( DatabaseBlockStore::class ),
			$this->createMock( UserGroupManager::class ),
			new HashBagOStuff(),
			$this->createMock( ChangeTagger::class ),
			$this->createMock( BlockAutopromoteStore::class ),
			$this->createMock( FilterUser::class ),
			$this->createMock( MessageLocalizer::class ),
			$this->createMock( UserEditTracker::class ),
			$this->createMock( UserFactory::class ),
			$this->createMock( UserIdentityUtils::class )
		);
		$consequencesFactory->setSession( $this->createMock( Session::class ) );

		return $consequencesFactory;
	}

	/**
	 * @covers ::newBlock
	 */
	public function testNewBlock() {
		$this->getFactory()->newBlock( $this->createMock( Parameters::class ), '', false );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newRangeBlock
	 */
	public function testNewRangeBlock() {
		$this->getFactory()->newRangeBlock( $this->createMock( Parameters::class ), '' );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newDegroup
	 */
	public function testNewDegroup() {
		$this->getFactory()->newDegroup( $this->createMock( Parameters::class ), new VariableHolder() );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newBlockAutopromote
	 */
	public function testNewBlockAutopromote() {
		$this->getFactory()->newBlockAutopromote( $this->createMock( Parameters::class ), 42 );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newThrottle
	 */
	public function testNewThrottle() {
		$this->getFactory()->newThrottle( $this->createMock( Parameters::class ), [] );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newWarn
	 */
	public function testNewWarn() {
		$this->getFactory()->newWarn( $this->createMock( Parameters::class ), '' );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newDisallow
	 */
	public function testNewDisallow() {
		$this->getFactory()->newDisallow( $this->createMock( Parameters::class ), '' );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @covers ::newTag
	 */
	public function testNewTag() {
		$this->getFactory()->newTag( $this->createMock( Parameters::class ), [] );
		$this->addToAssertionCount( 1 );
	}
}
