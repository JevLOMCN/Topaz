<?php

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;

class AddLinkAttributesTest extends TestCase {

	private function verifyNoFollow( string $html, int $pageNs, string $expected ): void {
		$siteConfig = new MockSiteConfig( [] );
		$pageConfig = new MockPageConfig( $siteConfig, [ 'pagens' => $pageNs ], null );
		$mockEnv = new MockEnv( [ 'pageConfig' => $pageConfig, "siteConfig" => $siteConfig ] );
		$doc = ContentUtils::createAndLoadDocument( $html );
		$body = DOMCompat::getBody( $doc );
		$addLinkClasses = new AddLinkAttributes();
		$addLinkClasses->run( $mockEnv, $body );

		$innerHtml = DOMCompat::getInnerHTML( $body );
		$pattern = '/ ' . DOMDataUtils::DATA_OBJECT_ATTR_NAME . '="\d+"/';
		$actual = preg_replace( $pattern, '', $innerHtml );
		$this->assertEquals( $expected, $actual );
	}

	private function verifyTarget( string $html, string $target, string $expected ) {
		$siteConfig = new MockSiteConfig( [ 'externallinktarget' => $target ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], null );
		$mockEnv = new MockEnv( [ 'pageConfig' => $pageConfig, "siteConfig" => $siteConfig ] );
		$doc = ContentUtils::createAndLoadDocument( $html );
		$body = DOMCompat::getBody( $doc );
		$addLinkClasses = new AddLinkAttributes();
		$addLinkClasses->run( $mockEnv, $body );

		$innerHtml = DOMCompat::getInnerHTML( $body );
		$pattern = '/ ' . DOMDataUtils::DATA_OBJECT_ATTR_NAME . '="\d+"/';
		$actual = preg_replace( $pattern, '', $innerHtml );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\PP\Processors\AddLinkAttributes::run
	 * @dataProvider provideNoFollow
	 * @param string $html
	 * @param int $pageNs
	 * @param string $expected
	 */
	public function testNoFollow( string $html, int $pageNs, string $expected ): void {
		$this->verifyNoFollow( $html, $pageNs, $expected );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\PP\Processors\AddLinkAttributes::run
	 * @dataProvider provideTarget
	 * @param string $html
	 * @param string $target
	 * @param string|false $expected
	 */
	public function testTarget( string $html, $target, string $expected ): void {
		$this->verifyTarget( $html, $target, $expected );
	}

	public function provideTarget() {
		return [
			[
				'<a href="http://www.example.com/plop" rel="mw:ExtLink">example.com</a>',
				"_blank",
				// The mocked SiteConfig excludes example.com from the "nofollow" rule, hence why
				// it's not added here
				'<a href="http://www.example.com/plop" rel="mw:ExtLink noreferrer noopener"' .
				' class="external text" target="_blank">example.com</a>'
			],
			[
				'<a href="http://www.somethingelse.org/plop" rel="mw:ExtLink">somethingelse.org</a>',
				"_self",
				'<a href="http://www.somethingelse.org/plop" rel="mw:ExtLink nofollow"' .
				' class="external text" target="_self">somethingelse.org</a>'
			],
			[
				'<a href="http://www.somethingelse.org/plop" rel="mw:ExtLink">somethingelse.org</a>',
				false,
				'<a href="http://www.somethingelse.org/plop" rel="mw:ExtLink nofollow"' .
				' class="external text">somethingelse.org</a>'
			],
		];
	}

	public function provideNoFollow() {
		// The mocked SiteConfig sets nofollow exceptions for the domain example.com and for the
		// namespace 1
		return [
			[
				'<a href="http://www.example.com/plop" rel="mw:ExtLink">example.com</a>', 0,
				'<a href="http://www.example.com/plop" rel="mw:ExtLink"' .
					' class="external text">example.com</a>'
			],
			[
				'<a href="http://www.example.org/plop" rel="mw:ExtLink">example.org</a>', 1,
				'<a href="http://www.example.org/plop" rel="mw:ExtLink"' .
					' class="external text">example.org</a>'
			],
			[
				'<a href="http://www.example.org/plop" rel="mw:ExtLink">example.org</a>', 0,
				'<a href="http://www.example.org/plop" rel="mw:ExtLink nofollow"' .
					' class="external text">example.org</a>'
			],
			[
				'<a href="./Foo" rel="mw:WikiLink">Foo</a>', 0,
					'<a href="./Foo" rel="mw:WikiLink">Foo</a>'
			]
		];
	}
}
