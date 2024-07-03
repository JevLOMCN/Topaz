<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagsManager;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\EmergencyCache;
use MediaWiki\Extension\AbuseFilter\FilterCompare;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\FilterProfiler;
use MediaWiki\Extension\AbuseFilter\FilterStore;
use MediaWiki\Extension\AbuseFilter\FilterValidator;
use MediaWiki\User\ActorMigrationBase;
use MediaWikiUnitTestCase;
use Wikimedia\Rdbms\LBFactory;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterStore
 * @todo Expand this. FilterStore is tightly bound to a Database, so it's not easy.
 */
class FilterStoreTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			FilterStore::class,
			new FilterStore(
				$this->createMock( ConsequencesRegistry::class ),
				$this->createMock( LBFactory::class ),
				$this->createMock( FilterProfiler::class ),
				$this->createMock( FilterLookup::class ),
				$this->createMock( ChangeTagsManager::class ),
				$this->createMock( FilterValidator::class ),
				$this->createMock( FilterCompare::class ),
				$this->createMock( EmergencyCache::class ),
				$this->createMock( ActorMigrationBase::class )
			)
		);
	}
}
