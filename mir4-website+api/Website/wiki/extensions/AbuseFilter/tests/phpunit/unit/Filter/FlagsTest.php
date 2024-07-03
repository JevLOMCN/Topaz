<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Filter;

use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Filter\Flags
 */
class FlagsTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 * @covers ::getEnabled
	 * @covers ::getDeleted
	 * @covers ::getHidden
	 * @covers ::getGlobal
	 */
	public function testGetters() {
		$enabled = true;
		$deleted = false;
		$hidden = true;
		$global = false;
		$flags = new Flags( $enabled, $deleted, $hidden, $global );

		$this->assertSame( $enabled, $flags->getEnabled(), 'enabled' );
		$this->assertSame( $deleted, $flags->getDeleted(), 'deleted' );
		$this->assertSame( $hidden, $flags->getHidden(), 'hidden' );
		$this->assertSame( $global, $flags->getGlobal(), 'global' );
	}

	/**
	 * @param mixed $value
	 * @param string $setter
	 * @param string $getter
	 * @covers ::setEnabled
	 * @covers ::setDeleted
	 * @covers ::setHidden
	 * @covers ::setGlobal
	 * @dataProvider provideSetters
	 */
	public function testSetters( $value, string $setter, string $getter ) {
		$flags = new Flags( true, true, true, true );

		$flags->$setter( $value );
		$this->assertSame( $value, $flags->$getter() );
	}

	/**
	 * @return array
	 */
	public static function provideSetters() {
		return [
			'enabled' => [ true, 'setEnabled', 'getEnabled' ],
			'deleted' => [ false, 'setDeleted', 'getDeleted' ],
			'hidden' => [ true, 'setHidden', 'getHidden' ],
			'global' => [ false, 'setGlobal', 'getGlobal' ],
		];
	}
}
