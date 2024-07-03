<?php

namespace Cite\Tests\Integration;

use Cite\AnchorFormatter;
use Cite\Cite;
use Cite\ErrorReporter;
use Cite\FootnoteMarkFormatter;
use Cite\ReferenceMessageLocalizer;
use Cite\Tests\TestUtils;
use Message;
use Parser;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Cite\FootnoteMarkFormatter
 * @license GPL-2.0-or-later
 */
class FootnoteMarkFormatterTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider provideLinkRef
	 */
	public function testLinkRef( string $group, array $ref, string $expectedOutput ) {
		$mockErrorReporter = $this->createMock( ErrorReporter::class );
		$mockErrorReporter->method( 'plain' )->willReturnCallback(
			static fn ( $parser, ...$args ) => implode( '|', $args )
		);
		$anchorFormatter = $this->createMock( AnchorFormatter::class );
		$anchorFormatter->method( 'jumpLink' )->willReturnArgument( 0 );
		$anchorFormatter->method( 'backLinkTarget' )->willReturnCallback(
			static fn ( ...$args ) => implode( '+', $args )
		);
		$messageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$messageLocalizer->method( 'localizeSeparators' )->willReturnArgument( 0 );
		$messageLocalizer->method( 'localizeDigits' )->willReturnArgument( 0 );
		$messageLocalizer->method( 'msg' )->willReturnCallback(
			function ( $key, ...$params ) {
				$customizedGroup = $key === 'cite_link_label_group-foo';
				$msg = $this->createMock( Message::class );
				$msg->method( 'isDisabled' )->willReturn( !$customizedGroup );
				$msg->method( 'plain' )->willReturn( $customizedGroup
					? 'a b c'
					: "($key|" . implode( '|', $params ) . ')'
				);
				return $msg;
			}
		);
		$mockParser = $this->createNoOpMock( Parser::class, [ 'recursiveTagParse' ] );
		$mockParser->method( 'recursiveTagParse' )->willReturnArgument( 0 );
		$formatter = new FootnoteMarkFormatter(
			$mockErrorReporter,
			$anchorFormatter,
			$messageLocalizer
		);

		$ref = TestUtils::refFromArray( $ref );
		$output = $formatter->linkRef( $mockParser, $group, $ref );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function provideLinkRef() {
		return [
			'Default label' => [
				'',
				[
					'name' => null,
					'number' => 50003,
					'key' => 50004,
				],
				'(cite_reference_link|50004+|50004|50003)'
			],
			'Default label, named group' => [
				'bar',
				[
					'name' => null,
					'number' => 3,
					'key' => 4,
				],
				'(cite_reference_link|4+|4|bar 3)'
			],
			'Custom label' => [
				'foo',
				[
					'name' => null,
					'number' => 3,
					'key' => 4,
				],
				'(cite_reference_link|4+|4|c)'
			],
			'Custom label overrun' => [
				'foo',
				[
					'name' => null,
					'number' => 10,
					'key' => 4,
				],
				'(cite_reference_link|4+|4|' .
					'cite_error_no_link_label_group&#124;foo&#124;cite_link_label_group-foo)'
			],
			'Named ref' => [
				'',
				[
					'name' => 'a',
					'number' => 3,
					'key' => 4,
					'count' => 1,
				],
				'(cite_reference_link|a+4-0|a-4|3)'
			],
			'Named ref reused' => [
				'',
				[
					'name' => 'a',
					'number' => 3,
					'key' => 4,
					'count' => 50002,
				],
				'(cite_reference_link|a+4-50001|a-4|3)'
			],
			'Subreference' => [
				'',
				[
					'name' => null,
					'number' => 3,
					'key' => 4,
					'extends' => 'b',
					'extendsIndex' => 50002,
				],
				'(cite_reference_link|4+|4|3.50002)'
			],
		];
	}

	/**
	 * @dataProvider provideCustomizedLinkLabels
	 */
	public function testFetchCustomizedLinkLabel( $expectedLabel, $offset, $group, $labelList ) {
		$mockMessageLocalizer = $this->createMock( ReferenceMessageLocalizer::class );
		$mockMessageLocalizer->method( 'msg' )->willReturnCallback(
			function ( ...$args ) use ( $labelList ) {
				$msg = $this->createMock( Message::class );
				$msg->method( 'isDisabled' )->willReturn( $labelList === null );
				$msg->method( 'plain' )->willReturn( $labelList );
				return $msg;
			}
		);
		$mockErrorReporter = $this->createMock( ErrorReporter::class );
		$mockErrorReporter->method( 'plain' )->willReturnCallback(
			static fn ( $parser, ...$args ) => implode( '|', $args )
		);
		/** @var FootnoteMarkFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new FootnoteMarkFormatter(
			$mockErrorReporter,
			$this->createMock( AnchorFormatter::class ),
			$mockMessageLocalizer
		) );

		$parser = $this->createNoOpMock( Parser::class );
		$output = $formatter->fetchCustomizedLinkLabel( $parser, $group, $offset );
		$this->assertSame( $expectedLabel, $output );
	}

	public static function provideCustomizedLinkLabels() {
		yield [ null, 1, '', null ];
		yield [ null, 2, '', null ];
		yield [ null, 1, 'foo', null ];
		yield [ null, 2, 'foo', null ];
		yield [ 'a', 1, 'foo', 'a b c' ];
		yield [ 'b', 2, 'foo', 'a b c' ];
		yield [ 'å', 1, 'foo', 'å β' ];
		yield [ 'cite_error_no_link_label_group|foo|cite_link_label_group-foo', 4, 'foo', 'a b c' ];
	}

	public function testDefaultGroupCannotHaveCustomLinkLabels() {
		/** @var FootnoteMarkFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new FootnoteMarkFormatter(
			$this->createNoOpMock( ErrorReporter::class ),
			$this->createNoOpMock( AnchorFormatter::class ),
			// Assert that ReferenceMessageLocalizer::msg( 'cite_link_label_group-' ) isn't called
			$this->createNoOpMock( ReferenceMessageLocalizer::class )
		) );

		$parser = $this->createNoOpMock( Parser::class );
		$this->assertNull( $formatter->fetchCustomizedLinkLabel( $parser, Cite::DEFAULT_GROUP, 1 ) );
	}

}
