<?php

namespace MediaWiki\Extension\Notifications\Test;

use ContentHandler;
use EchoUserLocator;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\UserLocator;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\Notifications\UserLocator
 */
class UserLocatorTest extends MediaWikiIntegrationTestCase {

	public function testLocateUsersWatchingTitle() {
		$title = Title::makeTitleSafe( NS_USER_TALK, 'Something_something_something' );
		$key = $title->getDBkey();

		for ( $i = 1000; $i < 1050; ++$i ) {
			$rows[] = [
				'wl_user' => $i,
				'wl_namespace' => NS_USER_TALK,
				'wl_title' => $key
			];
		}

		$this->getDb()->insert( 'watchlist', $rows, __METHOD__ );

		$event = $this->createMock( Event::class );
		$event->method( 'getTitle' )
			->willReturn( $title );

		$it = UserLocator::locateUsersWatchingTitle( $event, 10 );
		$this->assertCount( 50, $it );
		// @todo assert more than one query was issued
	}

	public static function locateTalkPageOwnerProvider() {
		return [
			[
				'Allows null event title',
				// expected user id's
				'empty',
				// event title
				null
			],

			[
				'No users selected for non-user talk namespace',
				// expected user id's
				'empty',
				// event title
				Title::newMainPage(),
			],

			[
				'Selects user from NS_USER_TALK',
				// expected user id's
				'user',
			],
		];
	}

	/**
	 * @dataProvider locateTalkPageOwnerProvider
	 */
	public function testLocateTalkPageOwner( $message, $expectMode, Title $title = null ) {
		if ( $expectMode === 'user' ) {
			$user = $this->getTestUser()->getUser();
			$user->addToDatabase();
			$expect = [ $user->getId() ];
			$title = $user->getTalkPage();
		} else {
			$expect = [];
		}
		$event = $this->createMock( Event::class );
		$event->method( 'getTitle' )
			->willReturn( $title );

		$users = UserLocator::locateTalkPageOwner( $event );
		$this->assertEquals( $expect, array_keys( $users ), $message );
	}

	public static function locateArticleCreatorProvider() {
		return [
			[ 'Something' ],
		];
	}

	/**
	 * @dataProvider locateArticleCreatorProvider
	 */
	public function testLocateArticleCreator( $message ) {
		$user = $this->getTestUser()->getUser();
		$user->addToDatabase();
		$title = $user->getTalkPage();
		$this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title )->doUserEditContent(
			/* $content = */ ContentHandler::makeContent( 'content', $title ),
			/* $user = */ $user,
			/* $summary = */ 'summary'
		);

		$event = $this->createMock( Event::class );
		$event->method( 'getTitle' )
			->willReturn( $title );
		$event->method( 'getAgent' )
			->willReturn( User::newFromId( 123 ) );

		$users = UserLocator::locateArticleCreator( $event );
		$this->assertEquals( [ $user->getId() ], array_keys( $users ), $message );
	}

	public function testDontSendPageLinkedNotificationsForPagesCreatedByBotUsers() {
		$botUser = $this->getTestUser( [ 'bot' ] )->getUser();
		$botUser->addToDatabase();
		$this->editPage( 'TestBotCreatedPage', 'Test', '', NS_MAIN, $botUser );
		$this->editPage( 'SomeOtherPage', '[[TestBotCreatedPage]]' );
		$event = $this->createMock( Event::class );
		$event->method( 'getTitle' )
			->willReturn( Title::newFromText( 'TestBotCreatedPage' ) );
		$event->method( 'getAgent' )
			->willReturn( User::newFromId( 123 ) );
		$event->method( 'getType' )
			->willReturn( 'page-linked' );
		$this->assertEquals( [], EchoUserLocator::locateArticleCreator( $event ) );

		$normalUser = $this->getTestUser()->getUser();
		$normalUser->addToDatabase();
		$this->editPage( 'NormalCreatedPage', 'Test', '', NS_MAIN, $normalUser );
		$this->editPage( 'AnotherPage', '[[NormalCreatedPage]]' );
		$event = $this->createMock( Event::class );
		$event->method( 'getTitle' )
			->willReturn( Title::newFromText( 'NormalCreatedPage' ) );
		$event->method( 'getAgent' )
			->willReturn( User::newFromId( 456 ) );
		$event->method( 'getType' )
			->willReturn( 'page-linked' );
		$this->assertEquals(
			$normalUser->getUser()->getId(),
			array_key_first( EchoUserLocator::locateArticleCreator( $event ) )
		);
	}

	public static function locateEventAgentProvider() {
		return [
			[
				'Null event agent returns no users',
				// expected result user id's
				[],
				// event agent
				null,
			],

			[
				'Anonymous event agent returns no users',
				// expected result user id's
				[],
				// event agent
				User::newFromName( '4.5.6.7', false ),
			],

			[
				'Registed event agent returned as user',
				// expected result user id's
				[ 42 ],
				// event agent
				User::newFromId( 42 ),
			],
		];
	}

	/**
	 * @dataProvider locateEventAgentProvider
	 */
	public function testLocateEventAgent( $message, $expect, User $agent = null ) {
		$event = $this->createMock( Event::class );
		$event->method( 'getAgent' )
			->willReturn( $agent );

		$users = UserLocator::locateEventAgent( $event );
		$this->assertEquals( $expect, array_keys( $users ), $message );
	}

	public static function locateFromEventExtraProvider() {
		return [
			[
				'Event without extra data returns empty result',
				// expected user list
				[],
				// event extra data
				[],
				// extra keys to get ids from
				[ 'foo' ],
			],

			[
				'Event with specified extra data returns expected result',
				// expected user list
				[ 123 ],
				// event extra data
				[ 'foo' => 123 ],
				// extra keys to get ids from
				[ 'foo' ],
			],

			[
				'Accepts User objects instead of user ids',
				// expected user list
				[ 123 ],
				// event extra data
				[ 'foo' => User::newFromId( 123 ) ],
				// extra keys to get ids from
				[ 'foo' ],
			],

			[
				'Allows inner key to be array of ids',
				// expected user list
				[ 123, 321 ],
				// event extra data
				[ 'foo' => [ 123, 321 ] ],
				// extra keys to get ids from
				[ 'foo' ],
			],

			[
				'Empty inner array causes no error',
				// expected user list
				[],
				// event extra data
				[ 'foo' => [] ],
				// extra keys to get ids from
				[ 'foo' ],
			],

			[
				'Accepts User object at inner level',
				// expected user list
				[ 123 ],
				// event extra data
				[ 'foo' => [ User::newFromId( 123 ) ] ],
				// extra keys to get ids from
				[ 'foo' ],
			],

		];
	}

	/**
	 * @dataProvider locateFromEventExtraProvider
	 */
	public function testLocateFromEventExtra( $message, $expect, array $extra, array $keys ) {
		$event = $this->createMock( Event::class );
		$event->method( 'getExtra' )
			->willReturn( $extra );
		$event->method( 'getExtraParam' )
			->willReturnMap( self::arrayToValueMap( $extra ) );

		$users = UserLocator::locateFromEventExtra( $event, $keys );
		$this->assertEquals( $expect, array_keys( $users ), $message );
	}

	protected static function arrayToValueMap( array $array ) {
		$result = [];
		foreach ( $array as $key => $value ) {
			// Event::getExtraParam second argument defaults to null
			$result[] = [ $key, null, $value ];
		}

		return $result;
	}

}
