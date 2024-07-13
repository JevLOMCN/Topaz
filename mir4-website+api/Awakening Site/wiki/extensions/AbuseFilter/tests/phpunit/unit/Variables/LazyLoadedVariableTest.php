<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use MediaWiki\Extension\AbuseFilter\Variables\LazyLoadedVariable;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Variables\LazyLoadedVariable
 */
class LazyLoadedVariableTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 * @covers ::getMethod
	 * @covers ::getParameters
	 */
	public function testGetters() {
		$method = 'magic';
		$params = [ 'foo', true, 1, null ];
		$obj = new LazyLoadedVariable( $method, $params );
		$this->assertSame( $method, $obj->getMethod(), 'method' );
		$this->assertSame( $params, $obj->getParameters(), 'params' );
	}
}
