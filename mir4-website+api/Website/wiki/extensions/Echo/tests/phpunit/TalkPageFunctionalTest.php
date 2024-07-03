<?php

use MediaWiki\Extension\Notifications\Model\Event;

/**
 * @group Echo
 * @group Database
 * @group medium
 */
class TalkPageFunctionalTest extends ApiTestCase {
	/**
	 * Creates and updates a user talk page a few times to ensure proper events are
	 * created.
	 * @covers \MediaWiki\Extension\Notifications\DiscussionParser
	 */
	public function testAddCommentsToTalkPage() {
		$editor = $this->getTestSysop()->getUser();
		$talkTitle = $this->getTestSysop()->getUser()->getTalkPage();
		$talkPage = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $talkTitle );

		$expectedMessageCount = 0;
		$this->assertCount( $expectedMessageCount, $this->fetchAllEvents() );

		// Start a talkpage
		$expectedMessageCount++;
		$content = "== Section 8 ==\n\nblah blah ~~~~\n";
		$this->editPage(
			$talkPage,
			$content,
			'',
			NS_USER_TALK,
			$editor
		);

		// Ensure the proper event was created
		$events = $this->fetchAllEvents();
		$this->assertCount( $expectedMessageCount, $events, 'After initial edit a single event must exist.' );
		$row = array_pop( $events );
		$this->assertEquals( 'edit-user-talk', $row->event_type );
		$this->assertEventSectionTitle( 'Section 8', $row );

		// Add another message to the talk page
		$expectedMessageCount++;
		$content .= "More content ~~~~\n";
		$this->editPage(
			$talkPage,
			$content,
			'',
			NS_USER_TALK,
			$editor
		);

		// Ensure another event was created
		$events = $this->fetchAllEvents();
		$this->assertCount( $expectedMessageCount, $events );
		$row = array_pop( $events );
		$this->assertEquals( 'edit-user-talk', $row->event_type );
		$this->assertEventSectionTitle( 'Section 8', $row );

		// Add a new section and a message within it
		$expectedMessageCount++;
		$content .= "\n\n== EE ==\n\nhere we go with a new section ~~~~\n";
		$this->editPage(
			$talkPage,
			$content,
			'',
			NS_USER_TALK,
			$editor
		);

		// Ensure this event has the new section title
		$events = $this->fetchAllEvents();
		$this->assertCount( $expectedMessageCount, $events );
		$row = array_pop( $events );
		$this->assertEquals( 'edit-user-talk', $row->event_type );
		$this->assertEventSectionTitle( 'EE', $row );
	}

	protected function assertEventSectionTitle( $sectionTitle, $row ) {
		$this->assertNotNull( $row->event_extra, 'Event must contain extra data.' );
		$extra = unserialize( $row->event_extra );
		$this->assertArrayHasKey( 'section-title', $extra, 'Extra data must include a section-title key.' );
		$this->assertEquals( $sectionTitle, $extra['section-title'], 'Detected section title must match' );
	}

	/**
	 * @return \stdClass[] All talk page edit events in db sorted from oldest to newest
	 */
	protected function fetchAllEvents() {
		$res = $this->db->select( 'echo_event', Event::selectFields(), [
				'event_type' => 'edit-user-talk',
			], __METHOD__, [ 'ORDER BY' => 'event_id ASC' ] );

		return iterator_to_array( $res );
	}

}
