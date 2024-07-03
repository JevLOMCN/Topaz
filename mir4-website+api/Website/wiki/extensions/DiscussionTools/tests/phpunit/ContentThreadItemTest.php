<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use DateTimeImmutable;
use MediaWiki\Extension\DiscussionTools\CommentUtils;
use MediaWiki\Extension\DiscussionTools\ImmutableRange;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentCommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentHeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentThreadItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ThreadItem;

/**
 * @group DiscussionTools
 * @covers \MediaWiki\Extension\DiscussionTools\ThreadItem\ContentThreadItem
 * @covers \MediaWiki\Extension\DiscussionTools\ThreadItem\ContentCommentItem
 * @covers \MediaWiki\Extension\DiscussionTools\CommentUtils
 * @covers \MediaWiki\Extension\DiscussionTools\ImmutableRange
 */
class ContentThreadItemTest extends IntegrationTestCase {
	/**
	 * @dataProvider provideAuthors
	 */
	public function testGetAuthorsOrThreadItemsBelow(
		array $thread, array $expectedAuthors, array $expectedThreadItemIds
	): void {
		$doc = $this->createDocument( '' );
		$node = $doc->createElement( 'div' );
		$range = new ImmutableRange( $node, 0, $node, 0 );

		$makeThreadItem = static function ( array $arr ) use ( &$makeThreadItem, $range ): ContentThreadItem {
			if ( $arr['type'] === 'comment' ) {
				$item = new ContentCommentItem(
					1, $range, false, [], [], new DateTimeImmutable(),
					$arr['author'],
					$arr['displayName'] ?? null
				);
			} else {
				$item = new ContentHeadingItem( $range, false, 2 );
			}
			$item->setId( $arr['id'] );
			foreach ( $arr['replies'] as $reply ) {
				$item->addReply( $makeThreadItem( $reply ) );
			}
			return $item;
		};

		$threadItem = $makeThreadItem( $thread );

		static::assertEquals( $expectedAuthors, $threadItem->getAuthorsBelow() );
		static::assertEquals( $expectedThreadItemIds, array_map( static function ( ThreadItem $threadItem ): string {
			return $threadItem->getId();
		}, $threadItem->getThreadItemsBelow() ) );
	}

	public static function provideAuthors(): array {
		return static::getJson( '../cases/authors.json' );
	}

	/**
	 * @dataProvider provideTranscludedFrom
	 */
	public function testGetTranscludedFrom(
		string $name, string $title, string $dom, string $expected, string $config, string $data
	): void {
		$dom = static::getHtml( $dom );
		$expectedPath = $expected;
		$expected = static::getJson( $expected );
		$config = static::getJson( $config );
		$data = static::getJson( $data );

		$title = $this->createTitleParser( $config )->parseTitle( $title );

		$doc = static::createDocument( $dom );
		$container = static::getThreadContainer( $doc );

		// Unwrap sections, so that transclusions overlapping section boundaries don't cause all
		// comments in the sections to be treated as transcluded from another page.
		CommentUtils::unwrapParsoidSections( $container );

		$threadItemSet = $this->createParser( $config, $data )->parse( $container, $title );
		$comments = $threadItemSet->getCommentItems();

		$transcludedFrom = [];
		foreach ( $comments as $comment ) {
			$transcludedFrom[ $comment->getId() ] = $comment->getTranscludedFrom();
		}

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteJsonFile( $expectedPath, $transcludedFrom );
		}

		static::assertEquals(
			$expected,
			$transcludedFrom,
			$name
		);
	}

	public static function provideTranscludedFrom(): array {
		return static::getJson( '../cases/transcluded.json' );
	}

	/**
	 * @dataProvider provideGetText
	 */
	public function testGetText(
		string $name, string $title, string $dom, string $expected, string $config, string $data
	): void {
		$dom = static::getHtml( $dom );
		$expectedPath = $expected;
		$expected = static::getJson( $expected );
		$config = static::getJson( $config );
		$data = static::getJson( $data );

		$doc = static::createDocument( $dom );
		$container = static::getThreadContainer( $doc );

		$title = $this->createTitleParser( $config )->parseTitle( $title );
		$threadItemSet = $this->createParser( $config, $data )->parse( $container, $title );
		$items = $threadItemSet->getThreadItems();

		$output = [];
		foreach ( $items as $item ) {
			$output[ $item->getId() ] = CommentUtils::htmlTrim(
				$item instanceof ContentCommentItem ? $item->getBodyText( true ) : $item->getText()
			);
		}

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteJsonFile( $expectedPath, $output );
		}

		static::assertEquals(
			$expected,
			$output,
			$name
		);
	}

	public static function provideGetText(): array {
		return static::getJson( '../cases/getText.json' );
	}

	/**
	 * @dataProvider provideGetHTML
	 */
	public function testGetHTML(
		string $name, string $title, string $dom, string $expected, string $config, string $data
	): void {
		$dom = static::getHtml( $dom );
		$expectedPath = $expected;
		$expected = static::getJson( $expected );
		$config = static::getJson( $config );
		$data = static::getJson( $data );

		$doc = static::createDocument( $dom );
		$container = static::getThreadContainer( $doc );

		$title = $this->createTitleParser( $config )->parseTitle( $title );
		$threadItemSet = $this->createParser( $config, $data )->parse( $container, $title );
		$items = $threadItemSet->getThreadItems();

		$output = [];
		foreach ( $items as $item ) {
			$output[ $item->getId() ] = CommentUtils::htmlTrim(
				$item instanceof ContentCommentItem ? $item->getBodyHTML( true ) : $item->getHTML()
			);
		}

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteJsonFile( $expectedPath, $output );
		}

		static::assertEquals(
			$expected,
			$output,
			$name
		);
	}

	public static function provideGetHTML(): array {
		return static::getJson( '../cases/getHTML.json' );
	}

}
