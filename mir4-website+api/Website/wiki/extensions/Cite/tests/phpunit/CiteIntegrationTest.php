<?php

namespace Cite\Tests;

use Cite\Cite;
use Cite\ErrorReporter;
use Cite\ReferenceListFormatter;
use Cite\ReferenceStack;
use Language;
use Parser;
use ParserOptions;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Cite\Cite
 * @license GPL-2.0-or-later
 */
class CiteIntegrationTest extends \MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValue( 'LanguageCode', 'qqx' );
	}

	/**
	 * @dataProvider provideCheckRefsNoReferences
	 */
	public function testCheckRefsNoReferences(
		array $initialRefs, bool $isSectionPreview, string $expectedOutput
	) {
		$this->overrideConfigValue( 'CiteResponsiveReferences', true );

		$mockErrorReporter = $this->createMock( ErrorReporter::class );
		$mockErrorReporter->method( 'halfParsed' )->willReturnCallback(
			static fn ( $parser, ...$args ) => '(' . implode( '|', $args ) . ')'
		);

		$referenceStack = new ReferenceStack();
		TestingAccessWrapper::newFromObject( $referenceStack )->refs = $initialRefs;

		$formatter = $this->createMock( ReferenceListFormatter::class );
		$formatter->method( 'formatReferences' )->willReturn( '<references />' );

		$cite = $this->newCite();
		/** @var Cite $spy */
		$spy = TestingAccessWrapper::newFromObject( $cite );
		$spy->referenceStack = $referenceStack;
		$spy->errorReporter = $mockErrorReporter;
		$spy->referenceListFormatter = $formatter;
		$spy->isSectionPreview = $isSectionPreview;

		$parser = $this->createNoOpMock( Parser::class );
		$output = $cite->checkRefsNoReferences( $parser, $isSectionPreview );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function provideCheckRefsNoReferences() {
		return [
			'Default group' => [
				[ '' => [ [ 'name' => 'a' ] ] ],
				false,
				"\n<references />"
			],
			'Default group in preview' => [
				[ '' => [ [ 'name' => 'a' ] ] ],
				true,
				"\n" . '<div class="mw-ext-cite-cite_section_preview_references">' .
				'<h2 id="mw-ext-cite-cite_section_preview_references_header">' .
				'(cite_section_preview_references)</h2><references /></div>'
			],
			'Named group' => [
				[ 'foo' => [ [ 'name' => 'a' ] ] ],
				false,
				"\n<br />(cite_error_group_refs_without_references|foo)"
			],
			'Named group in preview' => [
				[ 'foo' => [ [ 'name' => 'a' ] ] ],
				true,
				"\n" . '<div class="mw-ext-cite-cite_section_preview_references">' .
				'<h2 id="mw-ext-cite-cite_section_preview_references_header">' .
				'(cite_section_preview_references)</h2><references /></div>'
			]
		];
	}

	private function newCite(): Cite {
		$language = $this->createNoOpMock( Language::class );

		$mockOptions = $this->createMock( ParserOptions::class );
		$mockOptions->method( 'getIsPreview' )->willReturn( false );
		$mockOptions->method( 'getIsSectionPreview' )->willReturn( false );

		$mockParser = $this->createNoOpMock( Parser::class, [ 'getOptions', 'getContentLanguage' ] );
		$mockParser->method( 'getOptions' )->willReturn( $mockOptions );
		$mockParser->method( 'getContentLanguage' )->willReturn( $language );
		return new Cite( $mockParser );
	}

}
