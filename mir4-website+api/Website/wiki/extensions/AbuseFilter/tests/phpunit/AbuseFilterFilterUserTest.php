<?php

use MediaWiki\Extension\AbuseFilter\FilterUser;
use Psr\Log\NullLogger;

/**
 * @group Test
 * @group AbuseFilter
 * @group Database
 * @covers \MediaWiki\Extension\AbuseFilter\FilterUser
 * @todo Make a unit test once DI is possible for User::newSystemUser
 */
class AbuseFilterFilterUserTest extends MediaWikiIntegrationTestCase {
	public function testGetUser() {
		$name = 'AbuseFilter blocker user';
		$ml = $this->createMock( MessageLocalizer::class );
		$ml->method( 'msg' )->willReturn( $this->getMockMessage( $name ) );
		$ugm = $this->getServiceContainer()->getUserGroupManager();
		$filterUser = new FilterUser(
			$ml,
			$ugm,
			$this->getServiceContainer()->getUserNameUtils(),
			new NullLogger()
		);

		$actual = $filterUser->getUserIdentity();
		$this->assertSame( $name, $actual->getName(), 'name' );
		$this->assertContains( 'sysop', $ugm->getUserGroups( $actual ), 'sysop' );
		$this->assertTrue( $filterUser->getAuthority()->isAllowed( 'block' ) );
	}

	public function testGetUser_invalidName() {
		$name = 'Foobar filter user';
		$ml = $this->createMock( MessageLocalizer::class );
		$msg = $this->createMock( Message::class );
		$msg->method( 'inContentLanguage' )->willReturn( $this->getMockMessage( '' ) );
		$msg->method( 'inLanguage' )->willReturn( $this->getMockMessage( $name ) );
		$ml->method( 'msg' )->willReturn( $msg );
		$ugm = $this->getServiceContainer()->getUserGroupManager();
		$logger = new TestLogger();
		$logger->setCollect( true );
		$filterUser = new FilterUser(
			$ml,
			$ugm,
			$this->getServiceContainer()->getUserNameUtils(),
			$logger
		);

		$actual = $filterUser->getUserIdentity();
		$this->assertSame( $name, $actual->getName(), 'name' );
		$found = false;
		foreach ( $logger->getBuffer() as $msg ) {
			if ( strpos( $msg[1], 'MediaWiki:abusefilter-blocker' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Invalid name not logged' );
	}

	public function testIsUserSameAs() {
		$ml = $this->createMock( MessageLocalizer::class );
		$ml->method( 'msg' )->willReturn( $this->getMockMessage( 'AbuseFilter blocker user' ) );
		$ugm = $this->getServiceContainer()->getUserGroupManager();
		$filterUser = new FilterUser(
			$ml,
			$ugm,
			$this->getServiceContainer()->getUserNameUtils(),
			new NullLogger()
		);

		$this->assertTrue( $filterUser->isSameUserAs( $filterUser->getUserIdentity() ) );
		$this->assertFalse( $filterUser->isSameUserAs( $this->getTestUser()->getUser() ) );
	}
}
