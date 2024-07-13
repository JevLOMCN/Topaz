<?php

namespace MediaWiki\Extension\DiscussionTools\Tests\Unit;

use MediaWiki\Extension\DiscussionTools\CommentUtils;
use MediaWiki\Extension\DiscussionTools\Tests\TestUtils;
use MediaWikiUnitTestCase;

/**
 * @group DiscussionTools
 * @covers \MediaWiki\Extension\DiscussionTools\CommentUtils
 */
class CommentUtilsTest extends MediaWikiUnitTestCase {

	use TestUtils;

	/**
	 * @dataProvider provideLinearWalk
	 */
	public function testLinearWalk( string $name, string $htmlPath, string $expectedPath ) {
		$html = static::getHtml( $htmlPath );
		$doc = static::createDocument( $html );
		$expected = static::getJson( $expectedPath );

		$actual = [];
		CommentUtils::linearWalk( $doc, static function ( $event, $node ) use ( &$actual ) {
			$actual[] = "$event {$node->nodeName}({$node->nodeType})";
		} );

		$actualBackwards = [];
		CommentUtils::linearWalkBackwards( $doc, static function ( $event, $node ) use ( &$actualBackwards ) {
			$actualBackwards[] = "$event {$node->nodeName}({$node->nodeType})";
		} );

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteJsonFile( $expectedPath, $actual );
		}

		static::assertEquals( $expected, $actual, $name );

		$expectedBackwards = array_map( static function ( $a ) {
			return ( substr( $a, 0, 5 ) === 'enter' ? 'leave' : 'enter' ) . substr( $a, 5 );
		}, array_reverse( $expected ) );
		static::assertEquals( $expectedBackwards, $actualBackwards, $name . ' (backwards)' );
	}

	public static function provideLinearWalk(): array {
		return static::getJson( '../cases/linearWalk.json' );
	}
}
