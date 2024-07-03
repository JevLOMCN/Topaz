<?php

use MediaWiki\Extension\AbuseFilter\Hooks\Handlers\SchemaChangesHandler;
use MediaWiki\User\UserGroupManager;

/**
 * @group Database
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Hooks\Handlers\SchemaChangesHandler
 * @todo Make this a unit test once User::newSystemUser is moved to a service
 */
class SchemaChangesHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			SchemaChangesHandler::class,
			new SchemaChangesHandler(
				$this->createMock( MessageLocalizer::class ),
				$this->createMock( UserGroupManager::class )
			)
		);
	}

	/**
	 * @covers ::createAbuseFilterUser
	 */
	public function testCreateAbuseFilterUser_invalidUserName() {
		$noRowUpdater = $this->createMock( DatabaseUpdater::class );
		$noRowUpdater->method( 'updateRowExists' )->willReturn( false );
		$invalidML = $this->createMock( MessageLocalizer::class );
		$invalidML->method( 'msg' )->with( 'abusefilter-blocker' )->willReturn( $this->getMockMessage( '' ) );
		$handler = new SchemaChangesHandler( $invalidML, $this->createNoOpMock( UserGroupManager::class ) );
		$this->assertFalse( $handler->createAbuseFilterUser( $noRowUpdater ) );
	}

	/**
	 * @covers ::createAbuseFilterUser
	 */
	public function testCreateAbuseFilterUser_alreadyCreated() {
		$rowExistsUpdater = $this->createMock( DatabaseUpdater::class );
		$rowExistsUpdater->method( 'updateRowExists' )->willReturn( true );
		$validML = $this->createMock( MessageLocalizer::class );
		$validML->method( 'msg' )->with( 'abusefilter-blocker' )->willReturn( $this->getMockMessage( 'Foo' ) );
		$handler = new SchemaChangesHandler( $validML, $this->createNoOpMock( UserGroupManager::class ) );
		$this->assertFalse( $handler->createAbuseFilterUser( $rowExistsUpdater ) );
	}

	/**
	 * @covers ::createAbuseFilterUser
	 */
	public function testCreateAbuseFilterUser_success() {
		$noRowUpdater = $this->createMock( DatabaseUpdater::class );
		$noRowUpdater->method( 'updateRowExists' )->willReturn( false );
		$validML = $this->createMock( MessageLocalizer::class );
		$validML->method( 'msg' )->with( 'abusefilter-blocker' )->willReturn( $this->getMockMessage( 'Foo' ) );
		$ugm = $this->createMock( UserGroupManager::class );
		$ugm->expects( $this->once() )->method( 'addUserToGroup' );
		$okHandler = new SchemaChangesHandler( $validML, $ugm );
		$this->assertTrue( $okHandler->createAbuseFilterUser( $noRowUpdater ) );
	}

}
