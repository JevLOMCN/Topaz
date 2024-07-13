<?php

namespace Cite\Tests\Unit;

use Cite\AnchorFormatter;
use Cite\ErrorReporter;
use Cite\ReferenceListFormatter;
use Cite\ReferenceMessageLocalizer;
use Cite\Tests\TestUtils;
use Message;
use Parser;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Cite\ReferenceListFormatter
 * @license GPL-2.0-or-later
 */
class ReferenceListFormatterTest extends \MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideFormatReferences
	 */
	public function testFormatReferences( array $refs, string $expectedOutput ) {
		$mockParser = $this->createNoOpMock( Parser::class, [ 'recursiveTagParse' ] );
		$mockParser->method( 'recursiveTagParse' )->willReturnArgument( 0 );

		$mockErrorReporter = $this->createMock( ErrorReporter::class );
		$mockErrorReporter->method( 'plain' )->willReturnCallback(
			static fn ( $parser, ...$args ) => '(' . implode( '|', $args ) . ')'
		);

		$mockMessageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$mockMessageLocalizer->method( 'msg' )->willReturnCallback(
			function ( ...$args ) {
				$msg = $this->createMock( Message::class );
				$msg->method( 'plain' )->willReturn( '<li>(' . implode( '|', $args ) . ')</li>' );
				return $msg;
			}
		);

		$formatter = new ReferenceListFormatter(
			$mockErrorReporter,
			$this->createMock( AnchorFormatter::class ),
			$mockMessageLocalizer
		);

		$refs = array_map( [ TestUtils::class, 'refFromArray' ], $refs );
		$output = $formatter->formatReferences( $mockParser, $refs, true, false );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function provideFormatReferences() {
		return [
			'Empty' => [
				'refs' => [],
				'expectedOutput' => ''
			],
			'Minimal ref' => [
				'refs' => [
					0 => [
						'count' => 1,
						'key' => 1,
						'text' => 't',
					]
				],
				'expectedOutput' => '<div class="mw-references-wrap"><ol class="references">' . "\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n</ol></div>"
			],
			'Ref with extends' => [
				'refs' => [
					0 => [
						'count' => 1,
						'extends' => 'a',
						'extendsIndex' => 1,
						'key' => 2,
						'number' => 10,
						'text' => 't2',
					],
					1 => [
						'count' => 1,
						'key' => 3,
						'number' => 11,
						'text' => 't3',
					],
					'a' => [
						'count' => 1,
						'key' => 1,
						'name' => 'a',
						'number' => 9,
						'text' => 't1',
					],
				],
				'expectedOutput' => '<div class="mw-references-wrap"><ol class="references">' . "\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t1</span>' . "\n" .
					'|)<ol class="mw-extended-references"><li>(cite_references_link_one|||' .
					'<span class="reference-text">t2</span>' . "\n|)</li>\n" .
					"</ol></li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t3</span>' .
					"\n|)</li>\n" .
					'</ol></div>'
			],
			'Subref of subref' => [
				'refs' => [
					0 => [
						'count' => 1,
						'extends' => 'a',
						'extendsIndex' => 1,
						'key' => 1,
						'number' => 1,
						'text' => 't1',
					],
					'a' => [
						'count' => 1,
						'extends' => 'b',
						'extendsIndex' => 1,
						'key' => 2,
						'name' => 'a',
						'number' => 1,
						'text' => 't2',
					],
					'b' => [
						'count' => 1,
						'key' => 3,
						'name' => 'b',
						'number' => 1,
						'text' => 't3',
					],
				],
				'expectedOutput' => '<div class="mw-references-wrap"><ol class="references">' . "\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t3</span>' . "\n" .
					'|)<ol class="mw-extended-references"><li>(cite_references_link_one|||' .
					'<span class="reference-text">t1 (cite_error_ref_nested_extends|a|b)</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t2</span>' .
					"\n|)</li>\n</ol></li>\n" .
					'</ol></div>'
			],
			'Use columns' => [
				'refs' => array_map(
					static fn ( $i ) => [ 'count' => 1, 'key' => $i, 'text' => 't' ],
					range( 0, 10 )
				),
				'expectedOutput' => '<div class="mw-references-wrap mw-references-columns"><ol class="references">' .
					"\n" . '<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n" .
					'<li>(cite_references_link_one|||<span class="reference-text">t</span>' .
					"\n|)</li>\n</ol></div>"
			],
		];
	}

	/**
	 * @dataProvider provideCloseIndention
	 */
	public function testCloseIndention( $closingLi, $expectedOutput ) {
		/** @var ReferenceListFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new ReferenceListFormatter(
			$this->createMock( ErrorReporter::class ),
			$this->createMock( AnchorFormatter::class ),
			$this->createMock( ReferenceMessageLocalizer::class )
		) );

		$output = $formatter->closeIndention( $closingLi );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function provideCloseIndention() {
		return [
			'No indention' => [ false, '' ],
			'Indention string' => [ "</li>\n", "</ol></li>\n" ],
			'Indention without string' => [ true, '</ol>' ],
		];
	}

	/**
	 * @dataProvider provideFormatListItem
	 */
	public function testFormatListItem(
		array $ref,
		string $expectedOutput
	) {
		$mockErrorReporter = $this->createMock( ErrorReporter::class );
		$mockErrorReporter->method( 'plain' )->willReturnCallback(
			static fn ( $parser, ...$args ) => '(' . implode( '|', $args ) . ')'
		);

		$anchorFormatter = $this->createMock( AnchorFormatter::class );
		$anchorFormatter->method( 'backLink' )->willReturnCallback(
			static fn ( ...$args ) => implode( '+', $args )
		);
		$anchorFormatter->method( 'jumpLinkTarget' )->willReturnArgument( 0 );

		$mockMessageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$mockMessageLocalizer->method( 'localizeSeparators' )->willReturnArgument( 0 );
		$mockMessageLocalizer->method( 'localizeDigits' )->willReturnArgument( 0 );
		$mockMessageLocalizer->method( 'msg' )->willReturnCallback(
			function ( ...$args ) {
				$msg = $this->createMock( Message::class );
				$msg->method( 'plain' )->willReturn( '(' . implode( '|', $args ) . ')' );
				return $msg;
			}
		);

		/** @var ReferenceListFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new ReferenceListFormatter(
			$mockErrorReporter,
			$anchorFormatter,
			$mockMessageLocalizer
		) );

		$parser = $this->createNoOpMock( Parser::class );
		$ref = TestUtils::refFromArray( $ref );
		$output = $formatter->formatListItem( $parser, 1, $ref, false );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function provideFormatListItem() {
		return [
			'Success' => [
				'ref' => [
					'count' => 1,
					'key' => 1,
					'text' => 't',
				],
				'expectedOutput' => '(cite_references_link_one|1|1+|<span class="reference-text">t</span>' . "\n|)"
			],
			'With dir' => [
				'ref' => [
					'count' => 1,
					'dir' => 'rtl',
					'key' => 1,
					'text' => 't',
				],
				'expectedOutput' => '(cite_references_link_one|1|1+|<span class="reference-text">t</span>' .
					"\n" . '| class="mw-cite-dir-rtl")'
			],
			'Incomplete follow' => [
				'ref' => [
					'follow' => 'f',
					'key' => 1,
					'text' => 't',
				],
				'expectedOutput' => "<p id=\"f\"><span class=\"reference-text\">t</span>\n</p>"
			],
			'Count one' => [
				'ref' => [
					'count' => 1,
					'key' => 5,
					'name' => 'a',
					'text' => 't',
				],
				'expectedOutput' => '(cite_references_link_one|1-5|1+5-0|<span class="reference-text">t</span>'
					. "\n|)"
			],
			'Anonymous' => [
				'ref' => [
					'count' => 1,
					'key' => 5,
					'number' => 3,
					'text' => 't',
				],
				'expectedOutput' => '(cite_references_link_one|5|5+|<span class="reference-text">t</span>' . "\n|)"
			],
			'Count many' => [
				'ref' => [
					'count' => 3,
					'key' => 5,
					'name' => 'a',
					'number' => 3,
					'text' => 't',
				],
				'expectedOutput' => '(cite_references_link_many|1-5|(cite_references_link_many_format|1+5-0|3.0|' .
				'(cite_references_link_many_format_backlink_labels))' .
				'(cite_references_link_many_sep)(cite_references_link_many_format|1+5-1|3.1|' .
				'(cite_error_references_no_backlink_label))(cite_references_link_many_and)' .
				'(cite_references_link_many_format|1+5-2|3.2|(cite_error_references_no_backlink_label' .
				'))|<span class="reference-text">t</span>' . "\n|)"
			],
		];
	}

	/**
	 * @dataProvider provideReferenceText
	 */
	public function testReferenceText(
		?string $text,
		bool $isSectionPreview,
		string $expectedOutput
	) {
		$mockErrorReporter = $this->createMock( ErrorReporter::class );
		$mockErrorReporter->method( 'plain' )->willReturnCallback(
			static fn ( $parser, ...$args ) => '(' . implode( '|', $args ) . ')'
		);

		/** @var ReferenceListFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new ReferenceListFormatter(
			$mockErrorReporter,
			$this->createMock( AnchorFormatter::class ),
			$this->createMock( ReferenceMessageLocalizer::class )
		) );

		$parser = $this->createNoOpMock( Parser::class );
		$ref = TestUtils::refFromArray( [ 'text' => $text ] );
		$output = $formatter->referenceText( $parser, 1, $ref, $isSectionPreview );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function provideReferenceText() {
		return [
			'No text, not preview' => [
				'text' => null,
				'isSectionPreview' => false,
				'expectedOutput' => '(cite_error_references_no_text|1)'
			],
			'No text, is preview' => [
				'text' => null,
				'isSectionPreview' => true,
				'expectedOutput' => '(cite_warning_sectionpreview_no_text|1)'
			],
			'Has text' => [
				'text' => 'text',
				'isSectionPreview' => true,
				'expectedOutput' => '<span class="reference-text">text</span>' . "\n"
			],
			'Trims text' => [
				'text' => "text\n\n",
				'isSectionPreview' => true,
				'expectedOutput' => '<span class="reference-text">text</span>' . "\n"
			],
		];
	}

	/**
	 * @dataProvider provideReferencesFormatEntryAlternateBacklinkLabel
	 */
	public function testReferencesFormatEntryAlternateBacklinkLabel(
		?string $expectedLabel, ?string $labelList, int $offset
	) {
		$mockMessage = $this->createMock( Message::class );
		$mockMessage->method( 'exists' )->willReturn( (bool)$labelList );
		$mockMessage->method( 'plain' )->willReturn( $labelList ?? '<missing-junk>' );

		$mockMessageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$mockMessageLocalizer->method( 'msg' )
			->willReturn( $mockMessage );

		$errorReporter = $this->createMock( ErrorReporter::class );
		$errorReporter->expects( $expectedLabel ? $this->never() : $this->once() )
			->method( 'plain' );

		/** @var ReferenceListFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new ReferenceListFormatter(
			$errorReporter,
			$this->createMock( AnchorFormatter::class ),
			$mockMessageLocalizer
		) );

		$parser = $this->createNoOpMock( Parser::class );
		$label = $formatter->referencesFormatEntryAlternateBacklinkLabel( $parser, $offset );
		if ( $expectedLabel !== null ) {
			$this->assertSame( $expectedLabel, $label );
		}
	}

	public static function provideReferencesFormatEntryAlternateBacklinkLabel() {
		yield [ 'aa', 'aa ab ac', 0 ];
		yield [ 'ab', 'aa ab ac', 1 ];
		yield [ 'å', 'å b c', 0 ];
		yield [ null, 'a b c', 10 ];
	}

	/**
	 * @dataProvider provideReferencesFormatEntryNumericBacklinkLabel
	 */
	public function testReferencesFormatEntryNumericBacklinkLabel(
		string $expectedLabel, int $base, int $offset, int $max
	) {
		$mockMessageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$mockMessageLocalizer->method( 'localizeSeparators' )->willReturnArgument( 0 );
		$mockMessageLocalizer->method( 'localizeDigits' )->willReturnArgument( 0 );

		/** @var ReferenceListFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new ReferenceListFormatter(
			$this->createMock( ErrorReporter::class ),
			$this->createMock( AnchorFormatter::class ),
			$mockMessageLocalizer
		) );

		$label = $formatter->referencesFormatEntryNumericBacklinkLabel( $base, $offset, $max );
		$this->assertSame( $expectedLabel, $label );
	}

	public static function provideReferencesFormatEntryNumericBacklinkLabel() {
		yield [ '1.2', 1, 2, 9 ];
		yield [ '1.02', 1, 2, 99 ];
		yield [ '1.002', 1, 2, 100 ];
		yield [ '1.50005', 1, 50005, 50005 ];
		yield [ '2.1', 2, 1, 1 ];
	}

	/**
	 * @dataProvider provideLists
	 */
	public function testListToText( array $list, $expected ) {
		$mockMessageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$mockMessageLocalizer->method( 'msg' )->willReturnCallback(
			function ( ...$args ) {
				$msg = $this->createMock( Message::class );
				$msg->method( 'plain' )->willReturn( '(' . implode( '|', $args ) . ')' );
				return $msg;
			}
		);

		/** @var ReferenceListFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new ReferenceListFormatter(
			$this->createMock( ErrorReporter::class ),
			$this->createMock( AnchorFormatter::class ),
			$mockMessageLocalizer
		) );
		$this->assertSame( $expected, $formatter->listToText( $list ) );
	}

	public static function provideLists() {
		return [
			[
				'list' => [],
				'expected' => ''
			],
			[
				// This is intentionally using numbers to test the to-string cast
				'list' => [ 1 ],
				'expected' => '1'
			],
			[
				'list' => [ 1, 2 ],
				'expected' => '1(cite_references_link_many_and)2'
			],
			[
				'list' => [ 1, 2, 3 ],
				'expected' => '1(cite_references_link_many_sep)2(cite_references_link_many_and)3'
			],
		];
	}

}
