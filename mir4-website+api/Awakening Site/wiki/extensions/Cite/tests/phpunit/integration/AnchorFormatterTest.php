<?php

namespace Cite\Tests\Integration;

use Cite\AnchorFormatter;
use MediaWiki\Parser\Sanitizer;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Cite\AnchorFormatter
 * @license GPL-2.0-or-later
 */
class AnchorFormatterTest extends \MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValue( 'FragmentMode', [ 'html5' ] );
	}

	public function testRefKey() {
		$formatter = new AnchorFormatter();

		$this->assertSame(
			'cite_ref-key',
			$formatter->backLink( 'key', null ) );
		$this->assertSame(
			'cite_ref-key_2',
			$formatter->backLink( 'key', '2' ) );
	}

	public function testGetReferencesKey() {
		$formatter = new AnchorFormatter();

		$this->assertSame(
			'cite_note-key',
			$formatter->jumpLink( 'key' ) );
	}

	/**
	 * @dataProvider provideKeyNormalizations
	 */
	public function testNormalizeKey( $key, $expected ) {
		/** @var AnchorFormatter $formatter */
		$formatter = TestingAccessWrapper::newFromObject( new AnchorFormatter() );
		$normalized = $formatter->normalizeKey( $key );
		$encoded = Sanitizer::safeEncodeAttribute( Sanitizer::escapeIdForLink( $normalized ) );
		$this->assertSame( $expected, $encoded );
	}

	public static function provideKeyNormalizations() {
		return [
			[ 'a b', 'a_b' ],
			[ 'a  __  b', 'a_b' ],
			[ ':', ':' ],
			[ "\t\n", '_' ],
			[ "'", '&#039;' ],
			[ "''", '&#039;&#039;' ],
			[ '"%&/<>?[]{|}', '&quot;%&amp;/&lt;&gt;?&#91;&#93;&#123;&#124;&#125;' ],
			[ 'ISBN', '&#73;SBN' ],

			[ 'nature%20phylo', 'nature%2520phylo' ],
			[ 'Mininova%2E26%2E11%2E2009', 'Mininova%252E26%252E11%252E2009' ],
			[ '%c8%98tiri_2019', '%25c8%2598tiri_2019' ],
			[ 'play%21', 'play%2521' ],
			[ 'Terry+O%26rsquo%3BN…</ref', 'Terry+O%2526rsquo%253BN…&lt;/ref' ],
			[ '9&nbsp;pm', '9&amp;nbsp;pm' ],
			[ 'n%25%32%30n', 'n%2525%2532%2530n' ],
			[ 'a_ %20a', 'a_%2520a' ],
		];
	}

}
