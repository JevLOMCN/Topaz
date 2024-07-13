<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use DateTimeImmutable;
use MediaWiki\Extension\DiscussionTools\CommentParser;
use MediaWiki\Extension\DiscussionTools\CommentUtils;
use MediaWiki\Extension\DiscussionTools\ImmutableRange;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentCommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentHeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentThreadItem;
use RuntimeException;
use stdClass;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscussionTools
 * @covers \MediaWiki\Extension\DiscussionTools\CommentParser
 * @covers \MediaWiki\Extension\DiscussionTools\CommentUtils
 */
class CommentParserTest extends IntegrationTestCase {

	/**
	 * Get the offset path from ancestor to offset in descendant
	 *
	 * Convert Unicode codepoint offsets to UTF-16 code unit offsets.
	 */
	private static function getOffsetPath(
		Element $ancestor, Node $node, int $nodeOffset
	): string {
		if ( $node instanceof Text ) {
			$str = mb_substr( $node->nodeValue, 0, $nodeOffset );
			// Count characters that require two code units to encode in UTF-16
			$count = preg_match_all( '/[\x{010000}-\x{10FFFF}]/u', $str );
			$nodeOffset += $count;
		}

		$path = [ $nodeOffset ];
		while ( $node !== $ancestor ) {
			if ( !$node->parentNode ) {
				throw new RuntimeException( 'Not a descendant' );
			}
			array_unshift( $path, CommentUtils::childIndexOf( $node ) );
			$node = $node->parentNode;
		}
		return implode( '/', $path );
	}

	private static function getPathsFromRange( ImmutableRange $range, Element $root ): array {
		return [
			static::getOffsetPath( $root, $range->startContainer, $range->startOffset ),
			static::getOffsetPath( $root, $range->endContainer, $range->endOffset )
		];
	}

	private static function serializeComments( ContentThreadItem $threadItem, Element $root ): stdClass {
		$serialized = new stdClass();

		if ( $threadItem instanceof ContentHeadingItem ) {
			$serialized->placeholderHeading = $threadItem->isPlaceholderHeading();
		}

		$serialized->type = $threadItem->getType();

		if ( $threadItem instanceof ContentCommentItem ) {
			$serialized->timestamp = $threadItem->getTimestampString();
			$serialized->author = $threadItem->getAuthor();
			if ( $threadItem->getDisplayName() ) {
				$serialized->displayName = $threadItem->getDisplayName();
			}
		}

		// Can't serialize the DOM nodes involved in the range,
		// instead use their offsets within their parent nodes
		$range = $threadItem->getRange();
		$serialized->range = static::getPathsFromRange( $range, $root );

		if ( $threadItem instanceof ContentCommentItem ) {
			$serialized->signatureRanges = array_map( function ( ImmutableRange $range ) use ( $root ) {
				return static::getPathsFromRange( $range, $root );
			}, $threadItem->getSignatureRanges() );

			$serialized->timestampRanges = array_map( function ( ImmutableRange $range ) use ( $root ) {
				return static::getPathsFromRange( $range, $root );
			}, $threadItem->getTimestampRanges() );
		}

		if ( $threadItem instanceof ContentHeadingItem ) {
			$serialized->headingLevel = $threadItem->getHeadingLevel();
		}
		$serialized->level = $threadItem->getLevel();
		$serialized->name = $threadItem->getName();
		$serialized->id = $threadItem->getId();

		$serialized->warnings = $threadItem->getWarnings();

		$serialized->replies = [];
		foreach ( $threadItem->getReplies() as $reply ) {
			$serialized->replies[] = static::serializeComments( $reply, $root );
		}

		return $serialized;
	}

	/**
	 * @dataProvider provideTimestampRegexps
	 */
	public function testGetTimestampRegexp(
		string $format, string $expected, string $message
	): void {
		$config = static::getJson( "../data/enwiki-config.json" );
		$data = static::getJson( "../data/enwiki-data.json" );
		/** @var CommentParser $parser */
		$parser = TestingAccessWrapper::newFromObject(
			$this->createParser( $config, $data )
		);

		// HACK: Fix differences between JS & PHP regexes
		// TODO: We may just have to have two version in the test data
		$expected = preg_replace( '/\\\\u([0-9A-F]+)/', '\\\\x{$1}', $expected );
		$expected = str_replace( ':', '\:', $expected );
		$expected = '/' . $expected . '/u';

		$result = $parser->getTimestampRegexp( 'en', $format, '\\d', [ 'UTC' => 'UTC' ] );
		static::assertSame( $expected, $result, $message );
	}

	public static function provideTimestampRegexps(): array {
		return static::getJson( '../cases/timestamp-regex.json' );
	}

	/**
	 * @dataProvider provideTimestampParser
	 */
	public function testGetTimestampParser(
		string $format, ?array $digits, array $matchData, string $expected, string $message
	): void {
		$config = static::getJson( "../data/enwiki-config.json" );
		$data = static::getJson( "../data/enwiki-data.json" );
		/** @var CommentParser $parser */
		$parser = TestingAccessWrapper::newFromObject(
			$this->createParser( $config, $data )
		);

		$expected = new DateTimeImmutable( $expected );

		$tsParser = $parser->getTimestampParser( 'en', $format, $digits, 'UTC', [ 'UTC' => 'UTC' ] );
		static::assertEquals( $expected, $tsParser( $matchData )['date'], $message );
	}

	public static function provideTimestampParser(): array {
		return static::getJson( '../cases/timestamp-parser.json' );
	}

	/**
	 * @dataProvider provideTimestampParserDST
	 */
	public function testGetTimestampParserDST(
		string $sample, string $expected, string $expectedUtc, string $format,
		string $timezone, array $timezoneAbbrs, string $message
	): void {
		$config = static::getJson( "../data/enwiki-config.json" );
		$data = static::getJson( "../data/enwiki-data.json" );
		/** @var CommentParser $parser */
		$parser = TestingAccessWrapper::newFromObject(
			$this->createParser( $config, $data )
		);

		$regexp = $parser->getTimestampRegexp( 'en', $format, '\\d', $timezoneAbbrs );
		$tsParser = $parser->getTimestampParser( 'en', $format, null, $timezone, $timezoneAbbrs );

		$expected = new DateTimeImmutable( $expected );
		$expectedUtc = new DateTimeImmutable( $expectedUtc );

		preg_match( $regexp, $sample, $match, PREG_OFFSET_CAPTURE );
		$date = $tsParser( $match )['date'];

		static::assertEquals( $expected, $date, $message );
		static::assertEquals( $expectedUtc, $date, $message );
	}

	public static function provideTimestampParserDST(): array {
		return static::getJson( '../cases/timestamp-parser-dst.json' );
	}

	/**
	 * @dataProvider provideComments
	 */
	public function testGetThreads(
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
		$threads = $threadItemSet->getThreads();

		$processedThreads = [];

		foreach ( $threads as $i => $thread ) {
			$thread = static::serializeComments( $thread, $container );
			$thread = json_decode( json_encode( $thread ), true );
			$processedThreads[] = $thread;
		}

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteJsonFile( $expectedPath, $processedThreads );
		}

		foreach ( $threads as $i => $thread ) {
			static::assertEquals( $expected[$i], $processedThreads[$i], $name . ' section ' . $i );
		}
	}

	public static function provideComments(): array {
		return static::getJson( '../cases/comments.json' );
	}

}
