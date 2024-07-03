<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\AbuseLogger;
use MediaWiki\Extension\AbuseFilter\AbuseLoggerFactory;
use MediaWiki\Extension\AbuseFilter\CentralDBManager;
use MediaWiki\Extension\AbuseFilter\EditRevUpdater;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;
use Wikimedia\Rdbms\LBFactory;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\AbuseLoggerFactory
 */
class AbuseLoggerFactoryTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::newLogger
	 * @covers \MediaWiki\Extension\AbuseFilter\AbuseLogger::__construct
	 */
	public function testNewLogger() {
		$factory = new AbuseLoggerFactory(
			$this->createMock( CentralDBManager::class ),
			$this->createMock( FilterLookup::class ),
			$this->createMock( VariablesBlobStore::class ),
			$this->createMock( VariablesManager::class ),
			$this->createMock( EditRevUpdater::class ),
			$this->createMock( LBFactory::class ),
			new ServiceOptions(
				AbuseLogger::CONSTRUCTOR_OPTIONS,
				[
					'AbuseFilterLogIP' => false,
					'AbuseFilterNotifications' => 'rc',
					'AbuseFilterNotificationsPrivate' => true,
				]
			),
			'wikiID',
			'1.2.3.4'
		);
		$logger = $factory->newLogger(
			$this->createMock( Title::class ),
			$this->createMock( User::class ),
			VariableHolder::newFromArray( [ 'action' => 'edit' ] )
		);
		$this->assertInstanceOf( AbuseLogger::class, $logger, 'valid' );

		$this->expectException( InvalidArgumentException::class );
		$factory->newLogger(
			$this->createMock( Title::class ),
			$this->createMock( User::class ),
			new VariableHolder()
		);
	}

}
