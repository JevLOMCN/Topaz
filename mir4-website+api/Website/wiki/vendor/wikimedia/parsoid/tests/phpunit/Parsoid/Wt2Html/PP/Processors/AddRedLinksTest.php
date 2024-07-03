<?php

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Wt2Html\PP\Processors\AddRedLinks
 */
class AddRedLinksTest extends TestCase {

	/**
	 * @covers ::run
	 * @dataProvider provideRedLinks
	 */
	public function testRun( string $html, string $expected, string $message ) {
		$addRedLinks = new AddRedLinks();
		$env = new MockEnv( [] );
		$doc = ContentUtils::createAndLoadDocument( $html, [ 'markNew' => true ] );
		$body = DOMCompat::getBody( $doc );
		$addRedLinks->run( $env, $body, [], true );
		$actual = ContentUtils::ppToXML( $body, [ 'innerXML' => true ] );
		$this->assertEquals( $expected, $actual, $message );
	}

	public function provideRedLinks(): array {
		return [
			[
				'<a href="./Hello" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?action=edit&amp;redlink=1" title="Hello" rel="mw:WikiLink" ' .
					'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Simple redlink'
			],
			[
				'<a href="./Hello?param=p" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?param=p&amp;action=edit&amp;redlink=1" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Redlink with parameter and no fragment'
			],
			[
				'<a href="./Hello#frag" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?action=edit&amp;redlink=1#frag" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Redlink with fragment and no query string'
			],
			[
				'<a href="./Hello?param=p#frag" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?param=p&amp;action=edit&amp;redlink=1#frag" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Redlink with parameter and fragment'
			],
			[
				'<a href="./Hello?" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?action=edit&amp;redlink=1" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Redlink with empty query string and no fragment'
			],
			[
				// An empty fragment coming from wikitext ("[[Hello#]]") would not generate an empty
				// fragment in the link, but let's still have this in the coverage
				'<a href="./Hello#" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?action=edit&amp;redlink=1#" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Redlink with empty fragment and no query string'
			],
			[
				'<a href="./Hello?#anchor" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?action=edit&amp;redlink=1#anchor" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>' .
				'Hello</a>',
				'Redlink with empty query string and fragment'
			],
			[
				'<a href="./Hello?param=p#" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?param=p&amp;action=edit&amp;redlink=1#" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Redlink with query string and empty fragment'
			],
			[
				'<a href="./Hello#anchor?" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?action=edit&amp;redlink=1#anchor?" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>' .
				'Hello</a>',
				'Redlink with no query string and anchor containing a question mark'
			],
			[
				'<a href="./Hello?param=p#anchor?" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?param=p&amp;action=edit&amp;redlink=1#anchor?" title="Hello" ' .
				'rel="mw:WikiLink" class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>' .
				'Hello</a>',
				'Redlink with query string and anchor containing a question mark'
			],
			[
				'<a href="./Hello?#anchor?" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?action=edit&amp;redlink=1#anchor?" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>' .
				'Hello</a>',
				'Redlink with empty query string and anchor containing a question mark'
			],
			[
				'<a href="./Hello?param=p&amp;plop=c" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?param=p&amp;plop=c&amp;action=edit&amp;redlink=1" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'Ampersand encodings in source url'
			],
			[
				'<a href="./Hello?param=p&plop=c" title="Hello" rel="mw:WikiLink">Hello</a>',
				'<a href="./Hello?param=p&amp;plop=c&amp;action=edit&amp;redlink=1" title="Hello" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Hello"]}}\'>Hello</a>',
				'No ampersand encodings in source url'
			],
			[
			'<a href="./User:89119" title="User:89119" rel="mw:WikiLink">User:89119</a>',
				'<a href="./User:89119?action=edit&amp;redlink=1" title="User:89119" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title",' .
				'"params":["User:89119"]}}\'>User:89119</a>',
				'Inexistent user with a number as username'
			],
			[
				'<a href="./User:89119#frag" title="User:89119" rel="mw:WikiLink">User:89119</a>',
				'<a href="./User:89119?action=edit&amp;redlink=1#frag" title="User:89119" rel="mw:WikiLink" ' .
				'class="new" typeof="mw:LocalizedAttrs" ' .
				'data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title",' .
				'"params":["User:89119"]}}\'>User:89119</a>',
				'Inexistent user with a number as username and a fragment'
			]
		];
	}
}
