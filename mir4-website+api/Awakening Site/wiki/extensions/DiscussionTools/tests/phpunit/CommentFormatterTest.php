<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use FormatJson;
use GenderCache;
use MediaWiki\Config\Config;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use ParserOutput;
use Skin;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\DiscussionTools\CommentFormatter
 * @covers \MediaWiki\Extension\DiscussionTools\CommentUtils
 */
class CommentFormatterTest extends IntegrationTestCase {

	/**
	 * @dataProvider provideIsLanguageRequiringReplyIcon
	 */
	public function testIsLanguageRequiringReplyIcon(
		string $langCode, bool $expected, ?array $config = null
	): void {
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $langCode );
		if ( $config ) {
			$this->overrideConfigValues( [
				'DiscussionTools_visualenhancements_reply_icon_languages' => $config
			] );
		}
		$actual = MockCommentFormatter::isLanguageRequiringReplyIcon( $lang );
		static::assertEquals( $expected, $actual, $langCode );
	}

	public static function provideIsLanguageRequiringReplyIcon(): array {
		return [
			[ 'zh', true ],
			[ 'zh-hant', true ],
			[ 'ar', true ],
			[ 'arz', true ],
			[ 'arz', false, [ 'ar' => true, 'arz' => false ] ],
			[ 'en', false ],
			[ 'he', false ],
		];
	}

	/**
	 * @dataProvider provideAddDiscussionToolsInternal
	 */
	public function testAddDiscussionToolsInternal(
		string $name, string $titleText, string $dom, string $expected, string $config, string $data,
		bool $isMobile, bool $useButtons
	): void {
		$this->setService( 'GenderCache', $this->createMock( GenderCache::class ) );
		$dom = static::getHtml( $dom );
		$expectedPath = $expected;
		$expected = static::getText( $expectedPath );
		$config = static::getJson( $config );
		$data = static::getJson( $data );

		$this->overrideConfigValues( [
			// Consistent defaults for generating canonical URLs
			MainConfigNames::Server => 'https://example.org',
			MainConfigNames::CanonicalServer => 'https://example.org',
			MainConfigNames::ArticlePath => '/wiki/$1',
			MainConfigNames::ScriptPath => '/w',
			MainConfigNames::Script => '/w/index.php',
		] );

		$title = Title::newFromText( $titleText );
		$subscriptionStore = new MockSubscriptionStore();
		$user = $this->createMock( User::class );
		$qqxLang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'qqx' );
		$skin = $this->createMock( Skin::class );
		$skin->method( 'getSkinName' )->willReturn( 'minerva' );
		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->method( 'getConfig' )->willReturn( $this->createMock( Config::class ) );
		$outputPage->method( 'getTitle' )->willReturn( $title );
		$outputPage->method( 'getUser' )->willReturn( $user );
		$outputPage->method( 'getLanguage' )->willReturn( $qqxLang );
		$outputPage->method( 'getSkin' )->willReturn( $skin );
		$outputPage->method( 'msg' )->willReturn( 'a label' );

		MockCommentFormatter::$parser = $this->createParser( $config, $data );
		$commentFormatter = TestingAccessWrapper::newFromClass( MockCommentFormatter::class );

		$pout = new ParserOutput();
		$preprocessed = $commentFormatter->addDiscussionToolsInternal( $dom, $pout, $title );
		$preprocessed .= "\n<pre>\n" .
			"newestComment: " . FormatJson::encode(
				$pout->getExtensionData( 'DiscussionTools-newestComment' ), "\t", FormatJson::ALL_OK ) . "\n" .
			( $pout->getExtensionData( 'DiscussionTools-hasLedeContent' ) ?
			 "hasLedeContent\n" : '' ) .
			( $pout->getExtensionData( 'DiscussionTools-hasCommentsInLedeContent' ) ?
			 "hasCommentsInLedeContent\n" : '' ) .
			( $pout->getExtensionData( 'DiscussionTools-isEmptyTalkPage' ) ?
			 "isEmptyTalkPage\n" : '' ) .
			FormatJson::encode( $pout->getJsConfigVars(), "\t", FormatJson::ALL_OK ) .
			"\n</pre>";

		OutputPage::setupOOUI();

		$actual = $preprocessed;

		$actual = MockCommentFormatter::postprocessTopicSubscription(
			$actual, $outputPage, $subscriptionStore, $isMobile, $useButtons
		);

		$actual = MockCommentFormatter::postprocessVisualEnhancements(
			$actual, $outputPage, $isMobile
		);

		$actual = MockCommentFormatter::postprocessReplyTool(
			$actual, $outputPage, $isMobile, $useButtons
		);

		// OOUI ID's are non-deterministic, so strip them from test output
		$actual = preg_replace( '/ id=[\'"]ooui-php-[0-9]+[\'"]/', '', $actual );

		// Optionally write updated content to the "reply HTML" files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteTextFile( $expectedPath, $actual );
		}

		static::assertEquals( $expected, $actual, $name );
	}

	/**
	 * @return iterable<array>
	 */
	public static function provideAddDiscussionToolsInternal() {
		foreach ( static::getJson( '../cases/formatted.json' ) as $case ) {
			// Run each test case twice, for desktop and mobile output
			yield array_merge( $case,
				[ 'expected' => $case['expected']['desktop'], 'isMobile' => false, 'useButtons' => true ] );
			yield array_merge( $case,
				[ 'expected' => $case['expected']['mobile'], 'isMobile' => true, 'useButtons' => true ] );

			// Test the legacy output without visual enhancements (only available on desktop)
			yield array_merge( $case,
				[ 'expected' => $case['expected']['legacy'], 'isMobile' => false, 'useButtons' => false ] );
		}
	}

}
