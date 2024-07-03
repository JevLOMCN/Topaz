<?php

namespace Test\Parsoid\Utils;

use Wikimedia\Parsoid\Utils\UrlUtils;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Utils\UrlUtils
 */
class UrlUtilsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers ::parseUrl
	 * @dataProvider provideParsedUrl
	 */
	public function testParseUrl( string $url, array $parsed ) {
		$parsed += [
			'scheme' => null,
			'authority' => null,
			'query' => null,
			'fragment' => null,
		];
		$this->assertEquals( $parsed, UrlUtils::parseUrl( $url ) );
	}

	/**
	 * @covers ::assembleUrl
	 * @dataProvider provideParsedUrl
	 */
	public function testAssembleUrl( string $url, array $parsed ) {
		$this->assertEquals( $url, UrlUtils::assembleUrl( $parsed ) );
	}

	public function provideParsedUrl(): array {
		return [
			'Full URL' => [
				'http://user@example.com/some/path?que/ry#fra/gme?nt',
				[
					'scheme' => 'http',
					'authority' => 'user@example.com',
					'path' => '/some/path',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Full URL, no fragment' => [
				'http://user@example.com/some/path?que/ry',
				[
					'scheme' => 'http',
					'authority' => 'user@example.com',
					'path' => '/some/path',
					'query' => 'que/ry',
				],
			],
			'Full URL, no query' => [
				'http://user@example.com/some/path#fra/gme?nt',
				[
					'scheme' => 'http',
					'authority' => 'user@example.com',
					'path' => '/some/path',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Full URL, no path' => [
				'http://user@example.com?que/ry#fra/gme?nt',
				[
					'scheme' => 'http',
					'authority' => 'user@example.com',
					'path' => '',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Full URL, no authority' => [
				'mailto:user@example.com?que/ry#fra/gme?nt',
				[
					'scheme' => 'mailto',
					'path' => 'user@example.com',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Full URL, empty authority' => [
				'file:///some/path?que/ry#fra/gme?nt',
				[
					'scheme' => 'file',
					'authority' => '',
					'path' => '/some/path',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Protocol-relative URL' => [
				'//user@example.com/some/path?que/ry#fra/gme?nt',
				[
					'authority' => 'user@example.com',
					'path' => '/some/path',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Path-absolute relative URL' => [
				'/some/path?que/ry#fra/gme?nt',
				[
					'path' => '/some/path',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Path-relative URL' => [
				'some/path?que/ry#fra/gme?nt',
				[
					'path' => 'some/path',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Minimal URL' => [
				'//example.com',
				[
					'authority' => 'example.com',
					'path' => '',
				],
			],
			'Fragment only' => [
				'#fra/gme?nt',
				[
					'path' => '',
					'fragment' => 'fra/gme?nt',
				],
			],
			'Empty path' => [
				'?que/ry#fra/gme?nt',
				[
					'path' => '',
					'query' => 'que/ry',
					'fragment' => 'fra/gme?nt',
				],
			],
		];
	}

	/**
	 * @covers ::removeDotSegments
	 * @dataProvider provideRemoveDotSegments
	 */
	public function testRemoveDotSegments( $path, $expect ) {
		$this->assertEquals( $expect, UrlUtils::removeDotSegments( $path ) );
	}

	public function provideRemoveDotSegments() {
		return [
			[ '/a/b/c/./../../g', '/a/g' ],
			[ 'mid/content=5/../6', 'mid/6' ],
			[ '/a//../b', '/a/b' ],
			[ '/.../a', '/.../a' ],
			[ '.../a', '.../a' ],
			[ '', '' ],
			[ '/', '/' ],
			[ '//', '//' ],
			[ '.', '' ],
			[ '..', '' ],
			[ '...', '...' ],
			[ '/.', '/' ],
			[ '/..', '/' ],
			[ './', '' ],
			[ '../', '' ],
			[ './a', 'a' ],
			[ '../a', 'a' ],
			[ '../../a', 'a' ],
			[ '.././a', 'a' ],
			[ './../a', 'a' ],
			[ '././a', 'a' ],
			[ '../../', '' ],
			[ '.././', '' ],
			[ './../', '' ],
			[ '././', '' ],
			[ '../..', '' ],
			[ '../.', '' ],
			[ './..', '' ],
			[ './.', '' ],
			[ '/../../a', '/a' ],
			[ '/.././a', '/a' ],
			[ '/./../a', '/a' ],
			[ '/././a', '/a' ],
			[ '/../../', '/' ],
			[ '/.././', '/' ],
			[ '/./../', '/' ],
			[ '/././', '/' ],
			[ '/../..', '/' ],
			[ '/../.', '/' ],
			[ '/./..', '/' ],
			[ '/./.', '/' ],
			[ 'b/../../a', '/a' ],
			[ 'b/.././a', '/a' ],
			[ 'b/./../a', '/a' ],
			[ 'b/././a', 'b/a' ],
			[ 'b/../../', '/' ],
			[ 'b/.././', '/' ],
			[ 'b/./../', '/' ],
			[ 'b/././', 'b/' ],
			[ 'b/../..', '/' ],
			[ 'b/../.', '/' ],
			[ 'b/./..', '/' ],
			[ 'b/./.', 'b/' ],
			[ '/b/../../a', '/a' ],
			[ '/b/.././a', '/a' ],
			[ '/b/./../a', '/a' ],
			[ '/b/././a', '/b/a' ],
			[ '/b/../../', '/' ],
			[ '/b/.././', '/' ],
			[ '/b/./../', '/' ],
			[ '/b/././', '/b/' ],
			[ '/b/../..', '/' ],
			[ '/b/../.', '/' ],
			[ '/b/./..', '/' ],
			[ '/b/./.', '/b/' ],
		];
	}

	/**
	 * @covers ::expandUrl
	 * @dataProvider provideExpandUrl
	 */
	public function testExpandUrl( $url, $base, $expect ) {
		$this->assertSame( $expect, UrlUtils::expandUrl( $url, $base ) );
	}

	public function provideExpandUrl() {
		return [
			[ 'g:h', 'http://a/b/c/d;p?q', 'g:h' ],
			[ 'g', 'http://a/b/c/d;p?q', 'http://a/b/c/g' ],
			[ './g', 'http://a/b/c/d;p?q', 'http://a/b/c/g' ],
			[ 'g/', 'http://a/b/c/d;p?q', 'http://a/b/c/g/' ],
			[ '/g', 'http://a/b/c/d;p?q', 'http://a/g' ],
			[ '//g', 'http://a/b/c/d;p?q', 'http://g' ],
			[ '?y', 'http://a/b/c/d;p?q', 'http://a/b/c/d;p?y' ],
			[ 'g?y', 'http://a/b/c/d;p?q', 'http://a/b/c/g?y' ],
			[ '#s', 'http://a/b/c/d;p?q', 'http://a/b/c/d;p?q#s' ],
			[ 'g#s', 'http://a/b/c/d;p?q', 'http://a/b/c/g#s' ],
			[ 'g?y#s', 'http://a/b/c/d;p?q', 'http://a/b/c/g?y#s' ],
			[ ';x', 'http://a/b/c/d;p?q', 'http://a/b/c/;x' ],
			[ 'g;x', 'http://a/b/c/d;p?q', 'http://a/b/c/g;x' ],
			[ 'g;x?y#s', 'http://a/b/c/d;p?q', 'http://a/b/c/g;x?y#s' ],
			[ '', 'http://a/b/c/d;p?q', 'http://a/b/c/d;p?q' ],
			[ '.', 'http://a/b/c/d;p?q', 'http://a/b/c/' ],
			[ './', 'http://a/b/c/d;p?q', 'http://a/b/c/' ],
			[ '..', 'http://a/b/c/d;p?q', 'http://a/b/' ],
			[ '../', 'http://a/b/c/d;p?q', 'http://a/b/' ],
			[ '../g', 'http://a/b/c/d;p?q', 'http://a/b/g' ],
			[ '../..', 'http://a/b/c/d;p?q', 'http://a/' ],
			[ '../../', 'http://a/b/c/d;p?q', 'http://a/' ],
			[ '../../g', 'http://a/b/c/d;p?q', 'http://a/g' ],
			[ '../../../g', 'http://a/b/c/d;p?q', 'http://a/g' ],
			[ '../../../../g', 'http://a/b/c/d;p?q', 'http://a/g' ],
			[ '/./g', 'http://a/b/c/d;p?q', 'http://a/g' ],
			[ '/../g', 'http://a/b/c/d;p?q', 'http://a/g' ],
			[ 'g.', 'http://a/b/c/d;p?q', 'http://a/b/c/g.' ],
			[ '.g', 'http://a/b/c/d;p?q', 'http://a/b/c/.g' ],
			[ 'g..', 'http://a/b/c/d;p?q', 'http://a/b/c/g..' ],
			[ '..g', 'http://a/b/c/d;p?q', 'http://a/b/c/..g' ],
			[ './../g', 'http://a/b/c/d;p?q', 'http://a/b/g' ],
			[ './g/.', 'http://a/b/c/d;p?q', 'http://a/b/c/g/' ],
			[ 'g/./h', 'http://a/b/c/d;p?q', 'http://a/b/c/g/h' ],
			[ 'g/../h', 'http://a/b/c/d;p?q', 'http://a/b/c/h' ],
			[ 'g;x=1/./y', 'http://a/b/c/d;p?q', 'http://a/b/c/g;x=1/y' ],
			[ 'g;x=1/../y', 'http://a/b/c/d;p?q', 'http://a/b/c/y' ],
			[ 'g?y/./x', 'http://a/b/c/d;p?q', 'http://a/b/c/g?y/./x' ],
			[ 'g?y/../x', 'http://a/b/c/d;p?q', 'http://a/b/c/g?y/../x' ],
			[ 'g#s/./x', 'http://a/b/c/d;p?q', 'http://a/b/c/g#s/./x' ],
			[ 'g#s/../x', 'http://a/b/c/d;p?q', 'http://a/b/c/g#s/../x' ],
			[ 'http:g', 'http://a/b/c/d;p?q', 'http:g' ],
			[ 'foo/bar', 'http://example.com', 'http://example.com/foo/bar' ],
			[ 'foo/bar', 'a:xyz', 'a:foo/bar' ],
		];
	}

}
