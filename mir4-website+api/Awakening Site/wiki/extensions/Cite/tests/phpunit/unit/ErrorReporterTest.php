<?php

namespace Cite\Tests\Unit;

use Cite\ErrorReporter;
use Cite\ReferenceMessageLocalizer;
use Language;
use Message;
use Parser;
use ParserOptions;

/**
 * @covers \Cite\ErrorReporter
 * @license GPL-2.0-or-later
 */
class ErrorReporterTest extends \MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideErrors
	 */
	public function testPlain(
		string $key,
		string $expectedHtml,
		?string $expectedCategory
	) {
		$language = $this->createLanguage();
		$reporter = $this->createReporter( $language );
		$mockParser = $this->createParser( $language, $expectedCategory );
		$this->assertSame(
			$expectedHtml,
			$reporter->plain( $mockParser, $key, 'first param' ) );
	}

	public function testHalfParsed() {
		$language = $this->createLanguage();
		$reporter = $this->createReporter( $language );
		$mockParser = $this->createParser( $language );
		$this->assertSame(
			'<span class="warning mw-ext-cite-warning mw-ext-cite-warning-example" lang="qqx" ' .
				'dir="rtl">[(cite_warning|(cite_warning_example|first param))]</span>',
			$reporter->halfParsed( $mockParser, 'cite_warning_example', 'first param' ) );
	}

	public static function provideErrors() {
		return [
			'Example error' => [
				'key' => 'cite_error_example',
				'expectedHtml' => '<span class="error mw-ext-cite-error" lang="qqx" dir="rtl">' .
					'(cite_error|(cite_error_example|first param))</span>',
				'expectedCategory' => 'cite-tracking-category-cite-error',
			],
			'Warning error' => [
				'key' => 'cite_warning_example',
				'expectedHtml' => '<span class="warning mw-ext-cite-warning mw-ext-cite-warning-example" lang="qqx" ' .
					'dir="rtl">(cite_warning|(cite_warning_example|first param))</span>',
				'expectedCategory' => null,
			],
		];
	}

	private function createLanguage(): Language {
		$language = $this->createNoOpMock( Language::class, [ 'getDir', 'getHtmlCode' ] );
		$language->method( 'getDir' )->willReturn( 'rtl' );
		$language->method( 'getHtmlCode' )->willReturn( 'qqx' );
		return $language;
	}

	private function createReporter( Language $language ): ErrorReporter {
		$mockMessageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$mockMessageLocalizer->method( 'msg' )->willReturnCallback(
			function ( ...$args ) use ( $language ) {
				$message = $this->createMock( Message::class );
				$message->method( 'getKey' )->willReturn( $args[0] );
				$message->method( 'plain' )->willReturn( '(' . implode( '|', $args ) . ')' );
				$message->method( 'inLanguage' )->with( $language )->willReturnSelf();
				$message->method( 'getLanguage' )->willReturn( $language );
				return $message;
			}
		);

		return new ErrorReporter( $mockMessageLocalizer );
	}

	private function createParser( Language $language, string $expectedCategory = null ): Parser {
		$parserOptions = $this->createMock( ParserOptions::class );
		$parserOptions->method( 'getUserLangObj' )->willReturn( $language );

		$parser = $this->createNoOpMock( Parser::class, [ 'addTrackingCategory', 'getOptions', 'recursiveTagParse' ] );
		$parser->expects( $expectedCategory ? $this->once() : $this->never() )
			->method( 'addTrackingCategory' )
			->with( $expectedCategory );
		$parser->method( 'getOptions' )->willReturn( $parserOptions );
		$parser->method( 'recursiveTagParse' )->willReturnCallback(
			static fn ( $content ) => '[' . $content . ']'
		);
		return $parser;
	}

}
