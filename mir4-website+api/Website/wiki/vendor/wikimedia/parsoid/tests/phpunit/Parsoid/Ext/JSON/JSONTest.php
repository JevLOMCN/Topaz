<?php
// phpcs:disable Generic.Files.LineLength.TooLong

namespace Test\Parsoid\Ext\JSON;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Ext\JSON\JSON;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Parsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;

class JSONTest extends TestCase {
	private static $defaultContentVersion = Parsoid::AVAILABLE_VERSIONS[0];

	/**
	 * Create a DOM document using JSON to create the document body and verify correctness
	 * @covers \Wikimedia\Parsoid\Ext\JSON\JSON::toDOM
	 */
	public function testToDOM() {
		$json = new JSON();

		$pageContent = '{"1":2';    // malformed JSON example
		$opts = [ 'pageContent' => $pageContent ];

		// Test malformed JSON handler
		$env = new MockEnv( $opts );
		$API = new ParsoidExtensionAPI( $env );

		$expected = # '<!DOCTYPE html>' . "\n" .
				'<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="https://my.wiki.example/wikix/Special:Redirect/revision/1"><head prefix="mwr: https://my.wiki.example/wikix/Special:Redirect/"><meta charset="utf-8"/><meta property="mw:pageId" content="-1"/><meta property="mw:pageNamespace" content="0"/><meta property="mw:htmlVersion" content="' . self::$defaultContentVersion . '"/><meta property="mw:html:version" content="' . self::$defaultContentVersion . '"/><link rel="dc:isVersionOf" href="//my.wiki.example/wikix/TestPage"/><base href="//my.wiki.example/wikix/"/><title>TestPage</title><link rel="stylesheet" href="//my.wiki.example/wx/load.php?lang=en&amp;modules=mediawiki.skinning.content.parsoid%7Cmediawiki.skinning.interface%7Csite.styles&amp;only=styles&amp;skin=vector"/><meta http-equiv="content-language" content="en"/><meta http-equiv="vary" content="Accept"/></head><body data-parsoid="{}" lang="en" class="mw-content-rtl sitedir-rtl rtl mw-body-content parsoid-body mediawiki mw-parser-output" dir="rtl"><table typeof="mw:Error" data-mw=\'{"errors":[{"key":"bad-json"}]}\' data-parsoid="{}"></table></body></html>';

		$doc = $json->toDOM( $API );
		$response = DOMCompat::getOuterHTML( $doc->documentElement );
		$this->assertSame( $expected, $response );

		$pageContent =
			'{"array":[{"foo":"bar","key":["string1",null,false,true,0,1,123,456.789]}]}';
		$expected = # '<!DOCTYPE html>' . "\n" .
			'<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="https://my.wiki.example/wikix/Special:Redirect/revision/1"><head prefix="mwr: https://my.wiki.example/wikix/Special:Redirect/"><meta charset="utf-8"/><meta property="mw:pageId" content="-1"/><meta property="mw:pageNamespace" content="0"/><meta property="mw:htmlVersion" content="' . self::$defaultContentVersion . '"/><meta property="mw:html:version" content="' . self::$defaultContentVersion . '"/><link rel="dc:isVersionOf" href="//my.wiki.example/wikix/TestPage"/><base href="//my.wiki.example/wikix/"/><title>TestPage</title><link rel="stylesheet" href="//my.wiki.example/wx/load.php?lang=en&amp;modules=mediawiki.skinning.content.parsoid%7Cmediawiki.skinning.interface%7Csite.styles&amp;only=styles&amp;skin=vector"/><meta http-equiv="content-language" content="en"/><meta http-equiv="vary" content="Accept"/></head><body data-parsoid="{}" lang="en" class="mw-content-rtl sitedir-rtl rtl mw-body-content parsoid-body mediawiki mw-parser-output" dir="rtl"><table class="mw-json mw-json-object" data-parsoid="{}"><tbody data-parsoid="{}"><tr data-parsoid="{}"><th data-parsoid="{}">array</th><td data-parsoid="{}"><table class="mw-json mw-json-array" data-parsoid="{}"><tbody data-parsoid="{}"><tr data-parsoid="{}"><td data-parsoid="{}"><table class="mw-json mw-json-object" data-parsoid="{}"><tbody data-parsoid="{}"><tr data-parsoid="{}"><th data-parsoid="{}">foo</th><td class="value mw-json-string" data-parsoid="{}">bar</td></tr><tr data-parsoid="{}"><th data-parsoid="{}">key</th><td data-parsoid="{}"><table class="mw-json mw-json-array" data-parsoid="{}"><tbody data-parsoid="{}"><tr data-parsoid="{}"><td class="value mw-json-string" data-parsoid="{}">string1</td></tr><tr data-parsoid="{}"><td class="value mw-json-null" data-parsoid="{}">null</td></tr><tr data-parsoid="{}"><td class="value mw-json-boolean" data-parsoid="{}">false</td></tr><tr data-parsoid="{}"><td class="value mw-json-boolean" data-parsoid="{}">true</td></tr><tr data-parsoid="{}"><td class="value mw-json-number" data-parsoid="{}">0</td></tr><tr data-parsoid="{}"><td class="value mw-json-number" data-parsoid="{}">1</td></tr><tr data-parsoid="{}"><td class="value mw-json-number" data-parsoid="{}">123</td></tr><tr data-parsoid="{}"><td class="value mw-json-number" data-parsoid="{}">456.789</td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></body></html>';

		$opts = [ 'pageContent' => $pageContent ];

		// Test complex nested JSON object using string matching
		$env = new MockEnv( $opts );
		$API = new ParsoidExtensionAPI( $env );

		$doc = $json->toDOM( $API, $pageContent );
		$response = DOMCompat::getOuterHTML( $doc->documentElement );
		$this->assertSame( $expected, $response );
	}

	/**
	 * Create a DOM document using HTML and convert that to JSON and verify correctness
	 * @covers \Wikimedia\Parsoid\Ext\JSON\JSON::fromDOM
	 */
	public function testFromDOM() {
		$html = '<!DOCTYPE html>' . "\n" .
			'<html><head></head><body><table class="mw-json mw-json-object"><tbody><tr><th>' .
			'array</th><td><table class="mw-json mw-json-array"><tbody><tr><td>' .
			'<table class="mw-json mw-json-object"><tbody><tr><th>foo</th>' .
			'<td class="value mw-json-string">bar</td></tr><tr><th>key</th>' .
			'<td><table class="mw-json mw-json-array"><tbody><tr>' .
			'<td class="value mw-json-string">string1</td></tr><tr>' .
			'<td class="value mw-json-null">null</td></tr><tr>' .
			'<td class="value mw-json-boolean">false</td></tr><tr>' .
			'<td class="value mw-json-boolean">true</td></tr><tr>' .
			'<td class="value mw-json-number">0</td></tr><tr>' .
			'<td class="value mw-json-number">1</td></tr><tr>' .
			'<td class="value mw-json-number">123</td></tr><tr>' .
			'<td class="value mw-json-number">456.789</td></tr></tbody></table></td></tr>' .
			'</tbody></table></td></tr></tbody></table></td></tr></tbody></table></body></html>' .
			"\n";

		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );

		$opts = [ 'topLevelDoc' => $doc ];
		$env = new MockEnv( $opts );
		$API = new ParsoidExtensionAPI( $env );
		$json = new JSON();

		$expected = '{"array":[{"foo":"bar","key":["string1",null,false,true,0,1,123,456.789]}]}';

		$response = $json->fromDOM( $API );
		$this->assertSame( $expected, $response );
	}
}
