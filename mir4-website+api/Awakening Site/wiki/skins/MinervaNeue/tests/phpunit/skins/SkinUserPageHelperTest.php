<?php

namespace MediaWiki\Minerva;

use MediaWiki\Minerva\Skins\SkinUserPageHelper;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWikiIntegrationTestCase;

/**
 * @group MinervaNeue
 * @coversDefaultClass \MediaWiki\Minerva\Skins\SkinUserPageHelper
 */
class SkinUserPageHelperTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::isUserPage
	 * @covers ::fetchData
	 * @covers ::__construct
	 */
	public function testTitleNotInUserNamespace() {
		$title = Title::makeTitle( NS_MAIN, 'Test_Page' );

		$helper = new SkinUserPageHelper(
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $title );
		$this->assertFalse( $helper->isUserPage() );
	}

	/**
	 * @covers ::isUserPage
	 * @covers ::fetchData
	 * @covers ::__construct
	 */
	public function testTitleIsNull() {
		$title = null;

		$helper = new SkinUserPageHelper(
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $title );
		$this->assertNull( $helper->getPageUser() );
		$this->assertFalse( $helper->isUserPage() );
	}

	/**
	 * @covers ::isUserPage
	 * @covers ::fetchData
	 */
	public function testTitleisASubpage() {
		$title = Title::makeTitle( NS_USER, 'TestUser/subpage' );

		$helper = new SkinUserPageHelper(
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $title );
		$this->assertFalse( $helper->isUserPage() );
	}

	/**
	 * @covers ::isUserPage
	 * @covers ::fetchData
	 * @covers ::buildPageUserObject
	 */
	public function testTitleisAnIP() {
		$title = Title::makeTitle( NS_USER, '127.0.0.1' );

		$helper = new SkinUserPageHelper(
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $title );
		$this->assertTrue( $helper->isUserPage() );
	}

	/**
	 * @covers ::isUserPage
	 * @covers ::fetchData
	 * @covers ::buildPageUserObject
	 */
	public function testTitleIsIPRange() {
		$title = Title::makeTitle( NS_USER, '127.0.0.1/24' );

		$helper = new SkinUserPageHelper(
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $title );
		$this->assertFalse( $helper->isUserPage() );
	}

	/**
	 * @covers ::isUserPage
	 * @covers ::fetchData
	 * @covers ::buildPageUserObject
	 */
	public function testTitleIsFakeUserPage() {
		$origUserFactory = $this->getServiceContainer()->getUserFactory();
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromName' )
			->willReturnCallback( static function () use ( $origUserFactory ) {
				$user = $origUserFactory->newFromName( ...func_get_args() );
				$user->setId( 0 );
				$user->setItemLoaded( 'id' );
				return $user;
			} );
		$title = Title::makeTitle( NS_USER, 'Fake_user' );

		$helper = new SkinUserPageHelper(
			$userFactory,
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $title );
		$this->assertFalse( $helper->isUserPage() );
	}

	/**
	 * @covers ::fetchData
	 */
	public function testTitleProcessingIsCached() {
		$titleMock = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$titleMock->expects( $this->once() )
			->method( 'inNamespace' )
			->with( NS_USER )
			->willReturn( true );

		$titleMock->expects( $this->once() )
			->method( 'isSubpage' )
			->willReturn( false );

		$titleMock->expects( $this->once() )
			->method( 'getText' )
			->willReturn( 'Test' );

		$origUserFactory = $this->getServiceContainer()->getUserFactory();
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromName' )
			->willReturnCallback( static function () use ( $origUserFactory ) {
				$user = $origUserFactory->newFromName( ...func_get_args() );
				$user->setId( 0 );
				$user->setItemLoaded( 'id' );
				return $user;
			} );

		$helper = new SkinUserPageHelper(
			$userFactory,
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $titleMock );
		$helper->isUserPage();
		$helper->isUserPage();
		$helper->getPageUser();
		$helper->getPageUser();
	}

	/**
	 * @covers ::fetchData
	 * @covers ::getPageUser
	 * @covers ::isUserPage
	 */
	public function testGetPageUserWhenOnUserPage() {
		$user = $this->createMock( User::class );
		$user->method( 'getId' )->willReturn( 42 );
		$user->method( 'getName' )->willReturn( __METHOD__ );
		$user->method( 'isRegistered' )->willReturn( true );
		$title = Title::makeTitle( NS_USER, $user->getName() );

		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromName' )
			->with( $user->getName() )
			->willReturn( $user );
		$helper = new SkinUserPageHelper(
			$userFactory,
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $title );
		$this->assertTrue( $helper->isUserPage() );
		$this->assertEquals( $user->getId(), $helper->getPageUser()->getId() );
	}

	/**
	 * @covers ::fetchData
	 * @covers ::getPageUser
	 * @covers ::isUserPage
	 */
	public function testGetPageUserWhenOnUserPageReturnsCorrectUser() {
		$user = $this->createMock( User::class );
		$user->method( 'getId' )->willReturn( 42 );
		$user->method( 'getName' )->willReturn( __METHOD__ );
		$user->method( 'isRegistered' )->willReturn( true );
		$userTitle = Title::makeTitle( NS_USER, $user->getName() );

		$secondUser = $this->createMock( User::class );
		$secondUser->method( 'getId' )->willReturn( $user->getId() + 1 );
		$secondUser->method( 'getName' )->willReturn( $user->getName() . 'other' );
		$secondUser->method( 'isRegistered' )->willReturn( true );
		$secondUserTitle = Title::makeTitle( NS_USER, $secondUser->getName() );

		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromName' )
			->with( $secondUser->getName() )
			->willReturn( $secondUser );
		$helper = new SkinUserPageHelper(
			$userFactory,
			$this->getServiceContainer()->getUserNameUtils()
		);
		$helper->setTitle( $secondUserTitle );
		$this->assertTrue( $helper->isUserPage() );
		$this->assertNotEquals( $user->getId(), $helper->getPageUser()->getId() );
		$this->assertNotEquals( $helper->getPageUser()->getUserPage(), $userTitle );
	}

}
