<?php

namespace Cite\Tests\Integration;

use Cite\Cite;
use Cite\ErrorReporter;
use Cite\FootnoteMarkFormatter;
use Cite\ReferenceListFormatter;
use Cite\ReferenceStack;
use Cite\Tests\TestUtils;
use Language;
use LogicException;
use Parser;
use ParserOptions;
use ParserOutput;
use StripState;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversDefaultClass \Cite\Cite
 * @license GPL-2.0-or-later
 */
class CiteTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @covers ::parseArguments
	 * @dataProvider provideParseArguments
	 */
	public function testParseArguments(
		array $attributes,
		array $expectedValue,
		string $expectedError = null
	) {
		/** @var Cite $cite */
		$cite = TestingAccessWrapper::newFromObject( $this->newCite() );
		$status = $cite->parseArguments(
			$attributes,
			[ 'dir', 'extends', 'follow', 'group', 'name' ]
		);
		$this->assertSame( $expectedValue, array_values( $status->getValue() ) );
		if ( $expectedError ) {
			$this->assertStatusError( $expectedError, $status );
		} else {
			$this->assertStatusGood( $status );
		}
	}

	public static function provideParseArguments() {
		// Note: Values are guaranteed to be trimmed by the parser, see
		// Sanitizer::decodeTagAttributes()
		return [
			[
				'attributes' => [],
				'expectedValue' => [ null, null, null, null, null ],
			],

			// One attribute only
			[
				'attributes' => [ 'dir' => 'invalid' ],
				'expectedValue' => [ 'invalid', null, null, null, null ] ],
			[
				'attributes' => [ 'dir' => 'RTL' ],
				'expectedValue' => [ 'rtl', null, null, null, null ] ],
			[
				'attributes' => [ 'follow' => 'f' ],
				'expectedValue' => [ null, null, 'f', null, null ] ],
			[
				'attributes' => [ 'group' => 'g' ],
				'expectedValue' => [ null, null, null, 'g', null ] ],
			[
				'attributes' => [ 'invalid' => 'i' ],
				'expectedValue' => [ null, null, null, null, null ],
				'expectedError' => 'cite_error_ref_too_many_keys'
			],
			[
				'attributes' => [ 'invalid' => null ],
				'expectedValue' => [ null, null, null, null, null ],
				'expectedError' => 'cite_error_ref_too_many_keys'
			],
			[
				'attributes' => [ 'name' => 'n' ],
				'expectedValue' => [ null, null, null, null, 'n' ]
			],
			[
				'attributes' => [ 'name' => null ],
				'expectedValue' => [ null, null, null, null, null ]
			],
			[
				'attributes' => [ 'extends' => 'e' ],
				'expectedValue' => [ null, 'e', null, null, null ]
			],

			// Pairs
			[
				'attributes' => [ 'follow' => 'f', 'name' => 'n' ],
				'expectedValue' => [ null, null, 'f', null, 'n' ]
			],
			[
				'attributes' => [ 'follow' => null, 'name' => null ],
				'expectedValue' => [ null, null, null, null, null ]
			],
			[
				'attributes' => [ 'follow' => 'f', 'extends' => 'e' ],
				'expectedValue' => [ null, 'e', 'f', null, null ]
			],
			[
				'attributes' => [ 'group' => 'g', 'name' => 'n' ],
				'expectedValue' => [ null, null, null, 'g', 'n' ]
			],

			// Combinations of 3 or more attributes
			[
				'attributes' => [ 'group' => 'g', 'name' => 'n', 'extends' => 'e', 'dir' => 'rtl' ],
				'expectedValue' => [ 'rtl', 'e', null, 'g', 'n' ]
			],
		];
	}

	/**
	 * @covers ::references
	 * @dataProvider provideGuardedReferences
	 */
	public function testGuardedReferences(
		?string $text,
		array $argv,
		int $expectedRollbackCount,
		string $expectedInReferencesGroup,
		bool $expectedResponsive,
		string $expectedOutput
	) {
		$this->overrideConfigValue( 'CiteResponsiveReferences', false );

		$parser = $this->createNoOpMock( Parser::class, [ 'recursiveTagParse' ] );

		$cite = $this->newCite();
		/** @var Cite $spy */
		$spy = TestingAccessWrapper::newFromObject( $cite );
		$spy->errorReporter = $this->createPartialMock( ErrorReporter::class, [ 'halfParsed' ] );
		$spy->errorReporter->method( 'halfParsed' )->willReturnCallback(
			static fn ( $parser, ...$args ) => '(' . implode( '|', $args ) . ')'
		);
		$spy->referenceListFormatter = $this->createMock( ReferenceListFormatter::class );
		$spy->referenceListFormatter->method( 'formatReferences' )
			->with( $parser, [], $expectedResponsive, false )
			->willReturn( 'references!' );
		$spy->isSectionPreview = false;
		$spy->referenceStack = $this->createMock( ReferenceStack::class );
		$spy->referenceStack->method( 'popGroup' )
			->with( $expectedInReferencesGroup )->willReturn( [] );
		$spy->referenceStack->expects( $expectedRollbackCount ? $this->once() : $this->never() )
			->method( 'rollbackRefs' )
			->with( $expectedRollbackCount )
			->willReturn( [ [ 't', [] ] ] );

		$output = $cite->references( $parser, $text, $argv );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function provideGuardedReferences() {
		return [
			'Bare references tag' => [
				'text' => null,
				'argv' => [],
				'expectedRollbackCount' => 0,
				'expectedInReferencesGroup' => '',
				'expectedResponsive' => false,
				'expectedOutput' => 'references!'
			],
			'References with group' => [
				'text' => null,
				'argv' => [ 'group' => 'g' ],
				'expectedRollbackCount' => 0,
				'expectedInReferencesGroup' => 'g',
				'expectedResponsive' => false,
				'expectedOutput' => 'references!'
			],
			'Empty references tag' => [
				'text' => '',
				'argv' => [],
				'expectedRollbackCount' => 0,
				'expectedInReferencesGroup' => '',
				'expectedResponsive' => false,
				'expectedOutput' => 'references!'
			],
			'Set responsive' => [
				'text' => '',
				'argv' => [ 'responsive' => '1' ],
				'expectedRollbackCount' => 0,
				'expectedInReferencesGroup' => '',
				'expectedResponsive' => true,
				'expectedOutput' => 'references!'
			],
			'Unknown attribute' => [
				'text' => '',
				'argv' => [ 'blargh' => '0' ],
				'expectedRollbackCount' => 0,
				'expectedInReferencesGroup' => '',
				'expectedResponsive' => false,
				'expectedOutput' => '(cite_error_references_invalid_parameters)',
			],
			'Contains refs (which are broken)' => [
				'text' => Parser::MARKER_PREFIX . '-ref- and ' . Parser::MARKER_PREFIX . '-notref-',
				'argv' => [],
				'expectedRollbackCount' => 1,
				'expectedInReferencesGroup' => '',
				'expectedResponsive' => false,
				'expectedOutput' => "references!\n(cite_error_references_no_key)"
			],
		];
	}

	/**
	 * @covers ::guardedRef
	 * @dataProvider provideGuardedRef
	 */
	public function testGuardedRef(
		string $text,
		array $argv,
		?string $inReferencesGroup,
		array $initialRefs,
		string $expectOutput,
		?string $expectedError,
		array $expectedRefs,
		bool $isSectionPreview = false
	) {
		$mockParser = $this->createNoOpMock( Parser::class, [ 'getStripState' ] );
		$mockParser->method( 'getStripState' )
			->willReturn( $this->createMock( StripState::class ) );

		$errorReporter = $this->createPartialMock( ErrorReporter::class, [ 'halfParsed', 'plain' ] );
		$errorReporter->method( $this->logicalOr( 'halfParsed', 'plain' ) )->willReturnCallback(
			static fn ( $parser, ...$args ) => '(' . implode( '|', $args ) . ')'
		);

		$referenceStack = new ReferenceStack();
		/** @var ReferenceStack $stackSpy */
		$stackSpy = TestingAccessWrapper::newFromObject( $referenceStack );
		$stackSpy->refs = TestUtils::refGroupsFromArray( $initialRefs );

		$mockFootnoteMarkFormatter = $this->createMock( FootnoteMarkFormatter::class );
		$mockFootnoteMarkFormatter->method( 'linkRef' )->willReturn( '<foot />' );

		$cite = $this->newCite( $isSectionPreview );
		/** @var Cite $spy */
		$spy = TestingAccessWrapper::newFromObject( $cite );
		$spy->errorReporter = $errorReporter;
		$spy->footnoteMarkFormatter = $mockFootnoteMarkFormatter;
		$spy->inReferencesGroup = $inReferencesGroup;
		$spy->referenceStack = $referenceStack;

		$result = $spy->guardedRef( $mockParser, $text, $argv );
		$this->assertSame( $expectOutput, $result );
		if ( $expectedError ) {
			$this->assertStatusError( $expectedError, $spy->mReferencesErrors );
		} else {
			$this->assertStatusGood( $spy->mReferencesErrors );
		}
		$expectedRefs = TestUtils::refGroupsFromArray( $expectedRefs );
		$this->assertEquals( $expectedRefs, $stackSpy->refs );
	}

	public static function provideGuardedRef() {
		return [
			'Whitespace text' => [
				'text' => ' ',
				'argv' => [ 'name' => 'a' ],
				'inReferencesGroup' => null,
				'initialRefs' => [],
				'expectedOutput' => '<foot />',
				'expectedError' => null,
				'expectedRefs' => [
					'' => [
						'a' => [
							'count' => 1,
							'dir' => null,
							'key' => 1,
							'group' => '',
							'name' => 'a',
							'text' => null,
							'number' => 1,
						],
					],
				]
			],
			'Empty in default references' => [
				'text' => '',
				'argv' => [],
				'inReferencesGroup' => '',
				'initialRefs' => [ '' => [] ],
				'expectedOutput' => '',
				'expectedError' => 'cite_error_references_no_key',
				'expectedRefs' => [ '' => [] ]
			],
			'Fallback to references group' => [
				'text' => 'text',
				'argv' => [ 'name' => 'a' ],
				'inReferencesGroup' => 'foo',
				'initialRefs' => [
					'foo' => [ 'a' => [] ],
				],
				'expectedOutput' => '',
				'expectedError' => null,
				'expectedRefs' => [
					'foo' => [
						'a' => [
							'text' => 'text',
							'count' => 0,
						],
					],
				]
			],
			'Successful ref' => [
				'text' => 'text',
				'argv' => [ 'name' => 'a' ],
				'inReferencesGroup' => null,
				'initialRefs' => [],
				'expectedOutput' => '<foot />',
				'expectedError' => null,
				'expectedRefs' => [
					'' => [
						'a' => [
							'count' => 1,
							'dir' => null,
							'key' => 1,
							'group' => '',
							'name' => 'a',
							'text' => 'text',
							'number' => 1,
						],
					],
				]
			],
			'Invalid ref' => [
				'text' => 'text',
				'argv' => [
					'name' => 'a',
					'badkey' => 'b',
				],
				'inReferencesGroup' => null,
				'initialRefs' => [],
				'expectedOutput' => '(cite_error_ref_too_many_keys)',
				'expectedError' => null,
				'expectedRefs' => []
			],
			'Successful references ref' => [
				'text' => 'text',
				'argv' => [ 'name' => 'a' ],
				'inReferencesGroup' => '',
				'initialRefs' => [
					'' => [
						'a' => []
					]
				],
				'expectedOutput' => '',
				'expectedError' => null,
				'expectedRefs' => [
					'' => [
						'a' => [
							'text' => 'text',
							'count' => 0,
						],
					],
				]
			],
			'T245376: Preview a list-defined ref that was never used' => [
				'text' => 'T245376',
				'argv' => [ 'name' => 'a' ],
				'inReferencesGroup' => '',
				'initialRefs' => [],
				'expectOutput' => '',
				'expectedError' => null,
				'expectedRefs' => [
					'' => [
						'a' => [
							'text' => 'T245376',
							'count' => 0,
						],
					],
				],
				'isSectionPreview' => true,
			],
			'Mismatched text in references' => [
				'text' => 'text-2',
				'argv' => [ 'name' => 'a' ],
				'inReferencesGroup' => '',
				'initialRefs' => [
					'' => [
						'a' => [
							'text' => 'text-1',
							'count' => 1,
						],
					]
				],
				'expectedOutput' => '',
				'expectedError' => null,
				'expectedRefs' => [
					'' => [
						'a' => [
							'text' => 'text-1',
							'count' => 1,
							'warnings' => [ [ 'cite_error_references_duplicate_key', 'a' ] ],
						],
					],
				]
			],
		];
	}

	/**
	 * @covers ::guardedRef
	 */
	public function testGuardedRef_extendsProperty() {
		$this->overrideConfigValue( 'CiteBookReferencing', false );

		$mockOutput = $this->createMock( ParserOutput::class );
		// This will be our most important assertion.
		$mockOutput->expects( $this->once() )
			->method( 'setPageProperty' )
			->with( Cite::BOOK_REF_PROPERTY, '' );

		$mockParser = $this->createNoOpMock( Parser::class, [ 'getOutput' ] );
		$mockParser->method( 'getOutput' )->willReturn( $mockOutput );

		$cite = $this->newCite();
		/** @var Cite $spy */
		$spy = TestingAccessWrapper::newFromObject( $cite );
		$spy->errorReporter = $this->createMock( ErrorReporter::class );

		$spy->guardedRef( $mockParser, 'text', [ Cite::BOOK_REF_ATTRIBUTE => 'a' ] );
	}

	/**
	 * @coversNothing
	 */
	public function testReferencesSectionPreview() {
		$language = $this->createNoOpMock( Language::class );

		$parserOptions = $this->createMock( ParserOptions::class );
		$parserOptions->method( 'getIsSectionPreview' )->willReturn( true );

		$parser = $this->createNoOpMock( Parser::class, [ 'getOptions', 'getContentLanguage' ] );
		$parser->method( 'getOptions' )->willReturn( $parserOptions );
		$parser->method( 'getContentLanguage' )->willReturn( $language );

		/** @var Cite $cite */
		$cite = TestingAccessWrapper::newFromObject( new Cite( $parser ) );
		// Assume the currently parsed <ref> is wrapped in <references>
		$cite->inReferencesGroup = '';

		$html = $cite->guardedRef( $parser, 'a', [ 'name' => 'a' ] );
		$this->assertSame( '', $html );
	}

	/**
	 * @covers ::__clone
	 * @covers ::__construct
	 */
	public function testClone() {
		$cite = $this->newCite();

		$this->expectException( LogicException::class );
		clone $cite;
	}

	private function newCite( bool $isSectionPreview = false ): Cite {
		$language = $this->createNoOpMock( Language::class, [ '__debugInfo' ] );

		$mockOptions = $this->createMock( ParserOptions::class );
		$mockOptions->method( 'getIsSectionPreview' )->willReturn( $isSectionPreview );

		$mockParser = $this->createNoOpMock( Parser::class, [ 'getOptions', 'getContentLanguage' ] );
		$mockParser->method( 'getOptions' )->willReturn( $mockOptions );
		$mockParser->method( 'getContentLanguage' )->willReturn( $language );
		return new Cite( $mockParser );
	}

}
