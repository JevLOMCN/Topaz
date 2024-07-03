<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use Content;
use DummyNonTextContent;
use Generator;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\TextExtractor;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Permissions\SimpleAuthority;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use MediaWikiUnitTestCase;
use TextContent;
use WikitextContent;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\TextExtractor
 * @covers ::__construct
 */
class TextExtractorTest extends MediaWikiUnitTestCase {

	/**
	 * @param RevisionRecord|null $rev The revision being converted
	 * @param bool $sysop Whether the user should be a sysop (i.e. able to see deleted stuff)
	 * @param string $expected The expected textual representation of the Revision
	 * @covers ::revisionToString
	 * @dataProvider provideRevisionToString
	 */
	public function testRevisionToString( ?RevisionRecord $rev, bool $sysop, string $expected ) {
		$authority = new SimpleAuthority( $this->createMock( UserIdentity::class ), $sysop ? [ 'deletedtext' ] : [] );
		$hookRunner = new AbuseFilterHookRunner( $this->createHookContainer() );
		$converter = new TextExtractor( $hookRunner );
		$actual = $converter->revisionToString( $rev, $authority );
		$this->assertSame( $expected, $actual );
	}

	public static function provideRevisionToString() {
		yield 'no revision' => [ null, false, '' ];

		$page = new PageIdentityValue( 1, NS_MAIN, 'Foo', PageIdentityValue::LOCAL );
		$revRec = new MutableRevisionRecord( $page );
		$revRec->setContent( SlotRecord::MAIN, new TextContent( 'Main slot text.' ) );

		yield 'RevisionRecord instance' => [
			$revRec,
			false,
			'Main slot text.'
		];

		$revRec = new MutableRevisionRecord( $page );
		$revRec->setContent( SlotRecord::MAIN, new TextContent( 'Main slot text.' ) );
		$revRec->setContent( 'aux', new TextContent( 'Aux slot content.' ) );
		yield 'Multi-slot' => [
			$revRec,
			false,
			"Main slot text.\n\nAux slot content."
		];

		$revRec = new MutableRevisionRecord( $page );
		$revRec->setContent( SlotRecord::MAIN, new TextContent( 'Main slot text.' ) );
		$revRec->setVisibility( RevisionRecord::DELETED_TEXT );
		yield 'Suppressed revision, unprivileged' => [
			$revRec,
			false,
			''
		];

		yield 'Suppressed revision, privileged' => [
			$revRec,
			true,
			'Main slot text.'
		];
	}

	/**
	 * @param Content $content
	 * @param string $expected
	 * @covers ::contentToString
	 * @dataProvider provideContentToString
	 */
	public function testContentToString( Content $content, string $expected ) {
		$hookRunner = new AbuseFilterHookRunner( $this->createHookContainer() );
		$converter = new TextExtractor( $hookRunner );
		$this->assertSame( $expected, $converter->contentToString( $content ) );
	}

	/**
	 * @return Generator
	 */
	public static function provideContentToString(): Generator {
		$text = 'Some dummy text';
		yield 'text' => [ new TextContent( $text ), $text ];
		yield 'wikitext' => [ new WikitextContent( $text ), $text ];
		yield 'non-text' => [ new DummyNonTextContent( $text ), '' ];
	}

	/**
	 * @covers ::contentToString
	 */
	public function testContentToString__hook() {
		$expected = 'Text changed by hook';
		$hookCb = static function ( Content $content, ?string &$text ) use ( $expected ) {
			$text = $expected;
			return false;
		};
		$hookRunner = new AbuseFilterHookRunner(
			$this->createHookContainer( [ 'AbuseFilter-contentToString' => $hookCb ] )
		);
		$converter = new TextExtractor( $hookRunner );
		$unusedContent = new TextContent( 'You should not see me' );
		$this->assertSame( $expected, $converter->contentToString( $unusedContent ) );
	}
}
