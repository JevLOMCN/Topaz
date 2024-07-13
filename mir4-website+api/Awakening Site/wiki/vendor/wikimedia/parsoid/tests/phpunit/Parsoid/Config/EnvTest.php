<?php

namespace Test\Parsoid\Config;

use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Utils\Title;

/**
 * @covers \Wikimedia\Parsoid\Config\Env
 */
class EnvTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideResolveTitle
	 */
	public function testResolveTitle( $args, $expect, $ns = 4, $titleString = 'Wikipedia:Foo/bar/baz' ) {
		$siteConfig = $this->getMockBuilder( MockSiteConfig::class )
			->setConstructorArgs( [ [] ] )
			->onlyMethods( [ 'namespaceHasSubpages' ] )
			->getMock();
		$siteConfig->method( 'namespaceHasSubpages' )->willReturnCallback( static function ( $ns ) {
			return $ns !== 0;
		} );

		$title = Title::newFromText( $titleString, $siteConfig );
		$pageConfig = $this->getMockBuilder( MockPageConfig::class )
			->setConstructorArgs( [ $siteConfig, [], null ] )
			->onlyMethods( [ 'getLinkTarget', 'getRevisionContent' ] )
			->getMock();
		$pageConfig->method( 'getLinkTarget' )->willReturn( $title );
		$pageConfig->method( 'getRevisionContent' )->willReturn(
			new MockPageContent( [ 'main' => 'bogus' ] )
		);

		$env = new MockEnv( [ 'pageConfig' => $pageConfig, 'siteConfig' => $siteConfig ] );
		$this->assertSame( $expect, $env->resolveTitle( ...$args ) );
	}

	public function provideResolveTitle(): array {
		return [
			[ [ ' xxx ' ], 'xxx' ],
			[ [ '#fragment' ], 'Wikipedia:Foo/bar/baz#fragment' ],
			[ [ ':xxx' ], 'xxx' ],
			[ [ '/abc' ], 'Wikipedia:Foo/bar/baz/abc' ],
			[ [ '../abc' ], 'Wikipedia:Foo/bar/abc' ],
			[ [ '../../abc' ], 'Wikipedia:Foo/abc' ],
			[ [ '../../../abc' ], '../../../abc' ],

			[ [ ':xxx' ], 'xxx', 0, 'Foo/bar/baz' ],
			[ [ 'xxx///' ], 'xxx///', 0, 'Foo/bar/baz' ],
			[ [ '/abc' ], '/abc', 0, 'Foo/bar/baz' ],
			[ [ '../abc' ], '../abc', 0, 'Foo/bar/baz' ],
			[ [ '../../abc' ], '../../abc', 0, 'Foo/bar/baz' ],
			[ [ '../../../abc' ], '../../../abc', 0, 'Foo/bar/baz' ],

			[ [ ':xxx', true ], ':xxx' ],
			[ [ 'xxx///', true ], 'xxx///' ],
			[ [ '/abc', true ], '/abc' ],
			[ [ '../abc', true ], '../abc' ],
			[ [ '../../abc', true ], '../../abc' ],
			[ [ '../../../abc', true ], '../../../abc' ],

			[ [ 'xxx///' ], 'xxx///' ], // Is this right?
			[ [ '/xxx///' ], 'Wikipedia:Foo/bar/baz/xxx' ],
			[ [ '../xxx///' ], 'Wikipedia:Foo/bar/xxx' ],

			[ [ 'xxx///' ], 'xxx///', 0, 'Foo/bar/baz' ],
			[ [ '/xxx///' ], '/xxx///', 0, 'Foo/bar/baz' ],
			[ [ '../xxx///' ], '../xxx///', 0, 'Foo/bar/baz' ],

			[ [ 'xxx///', true ], 'xxx///' ],
			[ [ '/xxx///', true ], '/xxx///' ],
			[ [ '../xxx///', true ], '../xxx///' ],
		];
	}

}
