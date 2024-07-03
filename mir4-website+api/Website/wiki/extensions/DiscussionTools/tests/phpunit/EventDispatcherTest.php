<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use DateTimeImmutable;
use MediaWiki\Language\RawMessage;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\User\UserIdentityValue;

/**
 * @group DiscussionTools
 * @group Database
 * @covers \MediaWiki\Extension\DiscussionTools\Notifications\EventDispatcher
 */
class EventDispatcherTest extends IntegrationTestCase {

	/**
	 * @dataProvider provideGenerateCases
	 */
	public function testGenerateEventsFromParsers(
		string $rev1, string $rev2, string $authorUsername, ?string $other, string $expected
	): void {
		$wikitext1 = static::getText( $rev1 );
		$wikitext2 = static::getText( $rev2 );
		$expectedEvents = static::getJson( $expected, false );
		$config = static::getJson( "../data/enwiki-config.json" );
		$data = static::getJson( "../data/enwiki-data.json" );

		$dom1 = ( new RawMessage( $wikitext1 ) )->parse();
		$doc1 = static::createDocument( $dom1 );
		$container1 = static::getThreadContainer( $doc1 );

		$dom2 = ( new RawMessage( $wikitext2 ) )->parse();
		$doc2 = static::createDocument( $dom2 );
		$container2 = static::getThreadContainer( $doc2 );

		$dummyTitle = $this->createTitleParser( $config )->parseTitle( 'Dummy' );
		$parser = $this->createParser( $config, $data );
		$itemSet1 = $parser->parse( $container1, $dummyTitle );
		$itemSet2 = $parser->parse( $container2, $dummyTitle );

		$events = $other ? static::getJson( $other, true ) : [];

		$fakeUser = new UserIdentityValue( 0, $authorUsername );
		$fakeTitle = new PageIdentityValue( 0, NS_TALK, __CLASS__, PageIdentityValue::LOCAL );
		$fakeRevRecord = new MutableRevisionRecord( $fakeTitle );
		// All mock comments are posted between 00:00 and 00:10 on 2020-01-01
		$fakeRevRecord->setTimestamp( ( new DateTimeImmutable( '2020-01-01T00:10' ) )->format( 'c' ) );
		MockEventDispatcher::generateEventsFromItemSets(
			$events, $itemSet1, $itemSet2, $fakeRevRecord, $fakeTitle, $fakeUser
		);

		foreach ( $events as &$event ) {
			$event = json_decode( json_encode( $event ), false );
		}

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteJsonFile( $expected, $events );
		}

		static::assertEquals( $expectedEvents, $events );

		// Assert that no "new comment" events are generated for comments saved >10 minutes after their timestamps
		$events = $other ? static::getJson( $other, true ) : [];
		$fakeRevRecord->setTimestamp( ( new DateTimeImmutable( '2020-01-01T00:20' ) )->format( 'c' ) );
		MockEventDispatcher::generateEventsFromItemSets(
			$events, $itemSet1, $itemSet2, $fakeRevRecord, $fakeTitle, $fakeUser
		);

		foreach ( $events as &$event ) {
			$event = json_decode( json_encode( $event ), false );
		}

		$events = array_filter( $events, static function ( $event ) {
			return $event->type === 'dt-subscribed-new-comment';
		} );

		static::assertEquals( [], $events );
	}

	public static function provideGenerateCases(): array {
		return [
			// Several simple edits adding replies by different users.
			[
				'cases/EventDispatcher/simple/rev1.txt',
				'cases/EventDispatcher/simple/rev2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/simple/rev2.json',
			],
			[
				'cases/EventDispatcher/simple/rev2.txt',
				'cases/EventDispatcher/simple/rev3.txt',
				'Z',
				null,
				'../cases/EventDispatcher/simple/rev3.json',
			],
			[
				'cases/EventDispatcher/simple/rev3.txt',
				'cases/EventDispatcher/simple/rev4.txt',
				'Y',
				null,
				'../cases/EventDispatcher/simple/rev4.json',
			],
			[
				'cases/EventDispatcher/simple/rev4.txt',
				'cases/EventDispatcher/simple/rev5.txt',
				'X',
				null,
				'../cases/EventDispatcher/simple/rev5.json',
			],
			// Adding a new section with heading and a top-level comment.
			[
				'cases/EventDispatcher/newsection/rev1.txt',
				'cases/EventDispatcher/newsection/rev2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/newsection/rev2.json',
			],
			// Adding multiple replies in one edit.
			[
				'cases/EventDispatcher/multiple/rev1.txt',
				'cases/EventDispatcher/multiple/rev2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/multiple/rev2.json',
			],
			// Adding comments in section 0 (before first heading).
			// These do not generate notifications, because the interface doesn't allow subscribing to it.
			[
				'cases/EventDispatcher/section0/rev1.txt',
				'cases/EventDispatcher/section0/rev2.txt',
				'X',
				null,
				'../cases/EventDispatcher/section0/rev2.json',
			],
			[
				'cases/EventDispatcher/section0/rev2.txt',
				'cases/EventDispatcher/section0/rev3.txt',
				'Y',
				null,
				'../cases/EventDispatcher/section0/rev3.json',
			],
			// Adding comments in section starting with a heading with level 1.
			// These do not generate notifications, because the interface doesn't allow subscribing to it.
			[
				'cases/EventDispatcher/sectionlevel1/rev1.txt',
				'cases/EventDispatcher/sectionlevel1/rev2.txt',
				'Y',
				null,
				'../cases/EventDispatcher/sectionlevel1/rev2.json',
			],
			[
				'cases/EventDispatcher/sectionlevel1/rev2.txt',
				'cases/EventDispatcher/sectionlevel1/rev3.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sectionlevel1/rev3.json',
			],
			[
				'cases/EventDispatcher/sectionlevel1/rev3.txt',
				'cases/EventDispatcher/sectionlevel1/rev4.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sectionlevel1/rev4.json',
			],
			// Adding comments in section starting with a heading with level 3 (not following level 2 headings).
			// These do not generate notifications, because the interface doesn't allow subscribing to it.
			[
				'cases/EventDispatcher/sectionlevel3/rev1.txt',
				'cases/EventDispatcher/sectionlevel3/rev2.txt',
				'Y',
				null,
				'../cases/EventDispatcher/sectionlevel3/rev2.json',
			],
			// Adding a comment in a previously empty section.
			[
				'cases/EventDispatcher/emptysection/rev1.txt',
				'cases/EventDispatcher/emptysection/rev2.txt',
				'Y',
				null,
				'../cases/EventDispatcher/emptysection/rev2.json',
			],
			// Adding comments in sub-sections, where the parent section has no comments (except in
			// sub-sections). They generate notifications now (since T298617), previously they didn't.
			[
				'cases/EventDispatcher/subsection-empty/rev1.txt',
				'cases/EventDispatcher/subsection-empty/rev2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/subsection-empty/rev2.json',
			],
			[
				'cases/EventDispatcher/subsection-empty/rev2.txt',
				'cases/EventDispatcher/subsection-empty/rev3.txt',
				'Z',
				null,
				'../cases/EventDispatcher/subsection-empty/rev3.json',
			],
			// Adding comments in sub-sections, where the parent section also has comments.
			[
				'cases/EventDispatcher/subsection/rev1.txt',
				'cases/EventDispatcher/subsection/rev2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/subsection/rev2.json',
			],
			[
				'cases/EventDispatcher/subsection/rev2.txt',
				'cases/EventDispatcher/subsection/rev3.txt',
				'Z',
				null,
				'../cases/EventDispatcher/subsection/rev3.json',
			],
			[
				'cases/EventDispatcher/subsection/rev3.txt',
				'cases/EventDispatcher/subsection/rev4.txt',
				'Z',
				null,
				'../cases/EventDispatcher/subsection/rev4.json',
			],
			// Edits that do not add comments, and do not generate notifications.
			[
				// Copying a discussion from another page (note the author of revision)
				'cases/EventDispatcher/notcomments/rev1.txt',
				'cases/EventDispatcher/notcomments/rev2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/notcomments/rev2.json',
			],
			[
				// Editing a comment
				'cases/EventDispatcher/notcomments/rev2.txt',
				'cases/EventDispatcher/notcomments/rev3.txt',
				'X',
				null,
				'../cases/EventDispatcher/notcomments/rev3.json',
			],
			[
				// Editing page intro section
				'cases/EventDispatcher/notcomments/rev3.txt',
				'cases/EventDispatcher/notcomments/rev4.txt',
				'X',
				null,
				'../cases/EventDispatcher/notcomments/rev4.json',
			],
			// Multiple edits within a minute adding comments by the same user.
			// See T285528#7177220 for more detail about each case.
			[
				'cases/EventDispatcher/sametime/rev1.txt',
				'cases/EventDispatcher/sametime/rev2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev2.json',
			],
			[
				'cases/EventDispatcher/sametime/rev2.txt',
				'cases/EventDispatcher/sametime/rev3-case1.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev3-case1.json',
			],
			[
				'cases/EventDispatcher/sametime/rev2.txt',
				'cases/EventDispatcher/sametime/rev3-case2.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev3-case2.json',
			],
			[
				'cases/EventDispatcher/sametime/rev2.txt',
				'cases/EventDispatcher/sametime/rev3-case3.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev3-case3.json',
			],
			[
				'cases/EventDispatcher/sametime/rev2.txt',
				'cases/EventDispatcher/sametime/rev3-case4.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev3-case4.json',
			],
			[
				'cases/EventDispatcher/sametime/rev2.txt',
				'cases/EventDispatcher/sametime/rev3-case5.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev3-case5.json',
			],
			[
				'cases/EventDispatcher/sametime/rev1b.txt',
				'cases/EventDispatcher/sametime/rev2b.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev2b.json',
			],
			[
				'cases/EventDispatcher/sametime/rev2b.txt',
				'cases/EventDispatcher/sametime/rev3b-case6.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev3b-case6.json',
			],
			[
				'cases/EventDispatcher/sametime/rev2b.txt',
				'cases/EventDispatcher/sametime/rev3b-case7.txt',
				'Z',
				null,
				'../cases/EventDispatcher/sametime/rev3b-case7.json',
			],
			// Removing topics
			[
				// Warning if section is deleted
				'cases/EventDispatcher/removing-topics/rev1.txt',
				'cases/EventDispatcher/removing-topics/rev2a.txt',
				'Z',
				null,
				'../cases/EventDispatcher/removing-topics/rev2a.json',
			],
			[
				// No warning if section is moved
				'cases/EventDispatcher/removing-topics/rev1.txt',
				'cases/EventDispatcher/removing-topics/rev2b.txt',
				'Z',
				null,
				'../cases/EventDispatcher/removing-topics/rev2b.json',
			],
			[
				// Warning if section becomes a subsection
				'cases/EventDispatcher/removing-topics/rev1.txt',
				'cases/EventDispatcher/removing-topics/rev2c.txt',
				'Z',
				null,
				'../cases/EventDispatcher/removing-topics/rev2c.json',
			],
			[
				// Correct warning if section is deleted in case of duplicates (A)
				'cases/EventDispatcher/removing-topics-same/rev1.txt',
				'cases/EventDispatcher/removing-topics-same/rev2a.txt',
				'Z',
				null,
				'../cases/EventDispatcher/removing-topics-same/rev2a.json',
			],
			[
				// Correct warning if section is deleted in case of duplicates (B)
				'cases/EventDispatcher/removing-topics-same/rev1.txt',
				'cases/EventDispatcher/removing-topics-same/rev2b.txt',
				'Z',
				null,
				'../cases/EventDispatcher/removing-topics-same/rev2b.json',
			],
		];
	}

}
