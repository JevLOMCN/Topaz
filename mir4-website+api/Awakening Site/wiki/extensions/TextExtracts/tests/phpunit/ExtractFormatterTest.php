<?php

namespace TextExtracts\Test;

use MediaWikiIntegrationTestCase;
use TextExtracts\ExtractFormatter;

/**
 * @covers \TextExtracts\ExtractFormatter
 * @group TextExtracts
 *
 * @license GPL-2.0-or-later
 */
class ExtractFormatterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider provideExtracts
	 */
	public function testExtracts( $expected, $text, $plainText ) {
		$fmt = new ExtractFormatter( $text, $plainText );
		// .metadata class will be added via $wgExtractsRemoveClasses on WMF
		$fmt->remove( [ 'div', 'figure', '.metadata' ] );
		$text = $fmt->getText();
		$this->assertSame( $expected, $text );
	}

	public static function provideExtracts() {
		// phpcs:ignore Generic.Files.LineLength
		$dutch = '<b>Dutch</b> (<span class="unicode haudio" style="white-space:nowrap;"><span class="fn"><a href="/wiki/File:Nl-Nederlands.ogg" title="About this sound"><img alt="About this sound" src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Loudspeaker.svg/11px-Loudspeaker.svg.png" width="11" height="11" srcset="https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Loudspeaker.svg/17px-Loudspeaker.svg.png 1.5x, https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Loudspeaker.svg/22px-Loudspeaker.svg.png 2x" /></a>&#160;<a href="https://upload.wikimedia.org/wikipedia/commons/d/db/Nl-Nederlands.ogg" class="internal" title="Nl-Nederlands.ogg"><i>Nederlands</i></a></span>&#160;<small class="metadata audiolinkinfo" style="cursor:help;">(<a href="/w/index.php?title=Wikipedia:Media_help&amp;action=edit&amp;redlink=1" class="new" title="Wikipedia:Media help (page does not exist)"><span style="cursor:help;">help</span></a>·<a href="/wiki/File:Nl-Nederlands.ogg" title="File:Nl-Nederlands.ogg"><span style="cursor:help;">info</span></a>)</small></span>) is a <a href="/w/index.php?title=West_Germanic_languages&amp;action=edit&amp;redlink=1" class="new" title="West Germanic languages (page does not exist)">West Germanic language</a> and the native language of most of the population of the <a href="/w/index.php?title=Netherlands&amp;action=edit&amp;redlink=1" class="new" title="Netherlands (page does not exist)">Netherlands</a>';
		$tocText = 'Lead<div id="toc" class="toc">TOC goes here</div>
<h1>Section</h1>
<p>Section text</p>';
		// phpcs:ignore Generic.Files.LineLength
		$figureText = '<b>Test 123</b>beforetext<figure><span><img alt="About this sound" src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Loudspeaker.svg/11px-Loudspeaker.svg.png" width="11" height="11" srcset="https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Loudspeaker.svg/17px-Loudspeaker.svg.png 1.5x, https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Loudspeaker.svg/22px-Loudspeaker.svg.png 2x" /></span><figcaption>Very loud speaker</figcaption></figure>aftertext';

		return [
			[
				'Dutch ( Nederlands ) is a West Germanic language and the native language of ' .
					'most of the population of the Netherlands',
				$dutch,
				true,
			],

			'HTML cleanup in HTML mode' => [
				"\u{00A0}A &amp; <b>B</b>",
				"&#x0A;&nbsp;<a>A</a> &amp; <b>&#x42;</b>\r\n",
				false
			],
			'HTML cleanup in plain text mode' => [
				'A & B',
				"&#x0A;&nbsp;<a>A</a> &amp; <b>&#x42;</b>\r\n",
				true
			],

			[
				"<span><span lang=\"baz\">qux</span></span>",
				'<span class="foo"><span lang="baz">qux</span></span>',
				false,
			],
			[
				"<span><span lang=\"baz\">qux</span></span>",
				'<span style="foo: bar;"><span lang="baz">qux</span></span>',
				false,
			],
			[
				"<span><span lang=\"qux\">quux</span></span>",
				'<span class="foo"><span style="bar: baz;" lang="qux">quux</span></span>',
				false,
			],
			[
				// Verify that TOC is properly removed (HTML mode)
				"Lead\n<h1>Section</h1>\n<p>Section text</p>",
				$tocText,
				false,
			],
			[
				// Verify that TOC is properly removed (plain text mode)
				"Lead\n\n\x01\x021\2\1Section\nSection text",
				$tocText,
				true,
			],
			[
				// Verify that text in figures is removed (plain)
				'Test 123beforetextaftertext',
				$figureText,
				true,
			],
			[
				// Verify that text in figures is removed (html)
				'<b>Test 123</b>beforetextaftertext',
				$figureText,
				false,
			]
		];
	}

}
