<?php

namespace MediaWiki\Extension\VisualEditor\Tests;

use Generator;
use MediaWiki\Extension\VisualEditor\DirectParsoidClient;
use MediaWiki\Parser\Parsoid\ParsoidOutputAccess;
use MediaWiki\Revision\RevisionRecord;
use MediaWikiIntegrationTestCase;
use Wikimedia\Bcp47Code\Bcp47CodeValue;

/**
 * @coversDefaultClass \MediaWiki\Extension\VisualEditor\DirectParsoidClient
 * @group Database
 */
class DirectParsoidClientTest extends MediaWikiIntegrationTestCase {

	/**
	 * @return DirectParsoidClient
	 */
	private function createDirectClient(): DirectParsoidClient {
		$services = $this->getServiceContainer();
		$directClient = new DirectParsoidClient(
			$services->getPageRestHelperFactory(),
			$services->getUserFactory()->newAnonymous()
		);

		return $directClient;
	}

	/** @return Generator */
	public static function provideLanguageCodes() {
		yield 'German language code' => [ 'de' ];
		yield 'English language code' => [ 'en' ];
		yield 'French language code' => [ 'fr' ];
		yield 'No language code, fallback to en' => [ null ];
	}

	/**
	 * @covers ::getPageHtml
	 * @dataProvider provideLanguageCodes
	 */
	public function testGetPageHtml( $langCode ) {
		$directClient = $this->createDirectClient();

		$revision = $this->getExistingTestPage( 'DirectParsoidClient' )
			->getRevisionRecord();

		$language = $langCode ? new Bcp47CodeValue( $langCode ) : null;
		$langCode ??= 'en';
		$response = $directClient->getPageHtml( $revision, $language );

		$pageHtml = $response['body'];
		$headers = $response['headers'];

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'body', $response );
		$this->assertStringContainsString( 'DirectParsoidClient', $pageHtml );

		$this->assertArrayHasKey( 'headers', $response );
		$this->assertSame( $langCode, $headers['content-language'] );
		$this->assertStringContainsString( 'lang="' . $langCode . '"', $pageHtml );

		$this->assertArrayHasKey( 'etag', $headers );
		$this->assertStringContainsString( (string)$revision->getId(), $headers['etag'] );
	}

	/**
	 * @covers ::transformHTML
	 * @dataProvider provideLanguageCodes
	 */
	public function testTransformHtml( $langCode ) {
		$directClient = $this->createDirectClient();

		$page = $this->getExistingTestPage();

		$html = '<h2>Hello World</h2>';
		$oldid = $page->getId();

		$response = $directClient->transformHTML(
			$page,
			new Bcp47CodeValue( $langCode ?? 'qqx' ),
			$html,
			$oldid,
			// Supplying "null" will use the $oldid and look at recent rendering in ParserCache.
			null
		);

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'headers', $response );
		$this->assertArrayHasKey( 'Content-Type', $response['headers'] );

		$this->assertArrayHasKey( 'body', $response );
		// Trim to remove trailing newline
		$wikitext = trim( $response['body'] );
		$this->assertStringContainsString( '== Hello World ==', $wikitext );
	}

	/**
	 * @covers ::transformWikitext
	 * @dataProvider provideLanguageCodes
	 */
	public function testTransformWikitext( $langCode ) {
		$directClient = $this->createDirectClient();

		$page = $this->getExistingTestPage( 'DirectParsoidClient' );
		$pageRecord = $page->toPageRecord();
		$wikitext = '== Hello World ==';
		$langCode ??= 'qqx';

		$response = $directClient->transformWikitext(
			$pageRecord,
			new Bcp47CodeValue( $langCode ),
			$wikitext,
			false,
			$pageRecord->getLatest(),
			false
		);

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'body', $response );
		$this->assertArrayHasKey( 'headers', $response );

		$headers = $response['headers'];
		$this->assertSame( $langCode, $headers['content-language'] );

		$html = $response['body'];
		$this->assertStringContainsString( $page->getTitle()->getText(), $html );
		$this->assertStringContainsString( '>Hello World</h2>', $html );
	}

	/** @covers ::transformHTML */
	public function testRoundTripSelserWithETag() {
		$directClient = $this->createDirectClient();

		// Nasty wikitext that would be reformated without selser.
		$originalWikitext = '*a\n* b\n*  <i>c</I>';

		/** @var RevisionRecord $revision */
		$revision = $this->editPage( 'RoundTripSelserWithETag', $originalWikitext )
			->getValue()['revision-record'];

		$pageHtmlResponse = $directClient->getPageHtml( $revision );
		$eTag = $pageHtmlResponse['headers']['etag'];
		$oldHtml = $pageHtmlResponse['body'];
		$updatedHtml = str_replace( '</body>', '<p>More Text</p></body>', $oldHtml );

		// Make sure the etag is for "stash" flavor HTML.
		// The ETag should be transparent, but this is the easiest way to check that we are getting
		// the correct flavor of HTML.
		$this->assertMatchesRegularExpression( '@/stash\b@', $eTag );

		// Now make a new client object, so we can mock the ParsoidOutputAccess.
		$parsoidOutputAccess = $this->createNoOpMock( ParsoidOutputAccess::class );
		$services = $this->getServiceContainer();
		$directClient = new DirectParsoidClient(
			$services->getPageRestHelperFactory(),
			$services->getUserFactory()->newAnonymous()
		);

		$transformHtmlResponse = $directClient->transformHTML(
			$revision->getPage(),
			new Bcp47CodeValue( 'qqx' ),
			$updatedHtml,
			$revision->getId(),
			$eTag
		);

		$updatedWikitext = $transformHtmlResponse['body'];
		$this->assertStringContainsString( $originalWikitext, $updatedWikitext );
		$this->assertStringContainsString( 'More Text', $updatedWikitext );
	}

	/** @covers ::transformHTML */
	public function testRoundTripSelserWithoutETag() {
		$directClient = $this->createDirectClient();

		// Nasty wikitext that would be reformated without selser.
		$originalWikitext = '*a\n* b\n*  <i>c</I>';

		/** @var RevisionRecord $revision */
		$revision = $this->editPage( 'RoundTripSelserWithoutETag', $originalWikitext )
			->getValue()['revision-record'];

		$pageHtmlResponse = $directClient->getPageHtml( $revision );
		$oldHtml = $pageHtmlResponse['body'];
		$updatedHtml = str_replace( '</body>', '<p>More Text</p></body>', $oldHtml );

		$transformHtmlResponse = $directClient->transformHTML(
			$revision->getPage(),
			new Bcp47CodeValue( 'qqx' ),
			$updatedHtml,
			$revision->getId(),
			null
		);

		// Selser should still work, because the current rendering of the page still matches.
		$updatedWikitext = $transformHtmlResponse['body'];
		$this->assertStringContainsString( $originalWikitext, $updatedWikitext );
		$this->assertStringContainsString( 'More Text', $updatedWikitext );
	}

}
