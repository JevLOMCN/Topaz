<?php

use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\Model\TargetPage;
use MediaWiki\Title\Title;

/**
 * @covers \MediaWiki\Extension\Notifications\Model\TargetPage
 */
class TargetPageTest extends MediaWikiUnitTestCase {

	public function testCreate() {
		$this->assertNull(
			TargetPage::create(
				$this->mockTitle( 0 ),
				$this->mockEchoEvent()
			)
		);

		$this->assertInstanceOf(
			TargetPage::class,
			TargetPage::create(
				$this->mockTitle( 1 ),
				$this->mockEchoEvent()
			)
		);
	}

	/**
	 * @return TargetPage
	 */
	public function testNewFromRow() {
		$row = (object)[
			'etp_page' => 2,
			'etp_event' => 3
		];
		$obj = TargetPage::newFromRow( $row );
		$this->assertInstanceOf( TargetPage::class, $obj );

		return $obj;
	}

	public function testNewFromRowWithException() {
		$row = (object)[
			'etp_event' => 3
		];
		$this->expectException( InvalidArgumentException::class );
		TargetPage::newFromRow( $row );
	}

	/**
	 * @depends testNewFromRow
	 */
	public function testToDbArray( TargetPage $obj ) {
		$row = $obj->toDbArray();
		$this->assertIsArray( $row );

		// Not very common to assert that a field does _not_ exist
		// but since we are explicitly removing it, it seems to make sense.
		$this->assertArrayNotHasKey( 'etp_user', $row );

		$this->assertArrayHasKey( 'etp_page', $row );
		$this->assertArrayHasKey( 'etp_event', $row );
	}

	/**
	 * @param int $pageId
	 * @return Title
	 */
	protected function mockTitle( $pageId ) {
		$event = $this->createMock( Title::class );
		$event->method( 'getArticleID' )
			->willReturn( $pageId );

		return $event;
	}

	/**
	 * @param int $eventId
	 * @return Event
	 */
	protected function mockEchoEvent( $eventId = 1 ) {
		$event = $this->createMock( Event::class );
		$event->method( 'getId' )
			->willReturn( $eventId );

		return $event;
	}

}
