<?php

namespace TextExtracts\Test;

use TextExtracts\TextTruncator;

/**
 * @covers \TextExtracts\TextTruncator
 * @group TextExtracts
 *
 * @license GPL-2.0-or-later
 */
class TextTruncatorTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @dataProvider provideGetFirstSentences
	 * @param string $text
	 * @param string $sentences
	 * @param string $expected
	 */
	public function testGetFirstSentences( $text, $sentences, $expected ) {
		$truncator = new TextTruncator( false );
		$this->assertSame( $expected, $truncator->getFirstSentences( $text, $sentences ) );
	}

	public static function provideGetFirstSentences() {
		$longLine = str_repeat( 'word ', 1000000 );
		return [
			[
				'Foo is a bar. Such a smart boy. But completely useless.',
				2,
				'Foo is a bar. Such a smart boy.',
			],
			[
				'Foo is a bar. Such a smart boy. But completely useless.',
				1,
				'Foo is a bar.',
			],
			[
				'Foo is a bar. Such a smart boy.',
				2,
				'Foo is a bar. Such a smart boy.',
			],
			[
				'Foo is a bar.',
				1,
				'Foo is a bar.',
			],
			[
				'Foo is a bar.',
				2,
				'Foo is a bar.',
			],
			[
				'',
				1,
				'',
			],
			'0 sentences mean empty result' => [
				'Foo is a bar. Such a smart boy.',
				0,
				'',
			],
			"Don't explode on negative input" => [
				'Foo is a bar. Such a smart boy.',
				-1,
				'',
			],
			'More sentences requested than is available' => [
				'Foo is a bar. Such a smart boy.',
				3,
				'Foo is a bar. Such a smart boy.',
			],
			// Exclamation points too!!!
			[
				'Foo is a bar! Such a smart boy! But completely useless!',
				1,
				'Foo is a bar!',
			],
			// A tricky one
			[
				"Acid phosphatase (EC 3.1.3.2) is a chemical you don't want to mess with. " .
					"Polyvinyl acetate, however, is another story.",
				1,
				"Acid phosphatase (EC 3.1.3.2) is a chemical you don't want to mess with.",
			],
			// No clear sentences
			[
				"foo\nbar\nbaz",
				2,
				'foo',
			],
			// Bug T118621
			[
				'Foo was born in 1977. He enjoys listening to Siouxsie and the Banshees.',
				1,
				'Foo was born in 1977.',
			],
			// Bug T115795 - Test no cropping after initials
			[
				'P.J. Harvey is a singer. She is awesome!',
				1,
				'P.J. Harvey is a singer.',
			],
			// Bug T115817 - Non-breaking space is not a delimiter
			[
				html_entity_decode( 'Pigeons (lat.&nbsp;Columbidae) are birds. ' .
					'They primarily feed on seeds.' ),
				1,
				html_entity_decode( 'Pigeons (lat.&nbsp;Columbidae) are birds.' ),
			],
			// Bug T145231 - various problems with regexes
			[
				$longLine,
				3,
				trim( $longLine ),
			],
			[
				str_repeat( 'Sentence. ', 70000 ),
				65536,
				trim( str_repeat( 'Sentence. ', 65536 ) ),
			],

			'Preserve whitespace before end character' => [
				'Aa . Bb',
				1,
				'Aa .',
			],
		];
	}

	/**
	 * @dataProvider provideGetFirstChars
	 * @param string $text
	 * @param string $chars
	 * @param string $expected
	 */
	public function testGetFirstChars( $text, $chars, $expected ) {
		$truncator = new TextTruncator( false );
		$this->assertSame( $expected, $truncator->getFirstChars( $text, $chars ) );
	}

	public static function provideGetFirstChars() {
		$text = 'Lullzy lulz are lullzy!';
		$html = 'foo<tag>bar</tag>';
		$longText = str_repeat( 'тест ', 50000 );
		$longTextExpected = trim( str_repeat( 'тест ', 13108 ) );

		return [
			[ $text, -8, '' ],
			[ $text, 0, '' ],
			[ $text, 100, $text ],
			[ $text, 1, 'Lullzy' ],
			[ $text, 6, 'Lullzy' ],
			// [ $text, 7, 'Lullzy' ],
			[ $text, 8, 'Lullzy lulz' ],
			// HTML processing
			[ $html, 1, 'foo' ],
			// let HTML sanitizer clean it up later
			[ $html, 4, 'foo<tag>' ],
			[ $html, 12, 'foo<tag>bar</tag>' ],
			[ $html, 13, 'foo<tag>bar</tag>' ],
			[ $html, 16, 'foo<tag>bar</tag>' ],
			[ $html, 17, 'foo<tag>bar</tag>' ],
			// T143178 - previously, characters were extracted using regexps which failed when
			// requesting 64K chars or more.
			[ $longText, 65536, $longTextExpected ],
		];
	}

	public function testTidyIntegration() {
		$truncator = new TextTruncator( true );

		$text = '<b>Aa. Bb.</b>';
		$this->assertSame( '<p><b>Aa.</b></p>', $truncator->getFirstSentences( $text, 1 ) );
		$this->assertSame( '<p><b>Aa</b></p>', $truncator->getFirstChars( $text, 4 ) );
	}

}
