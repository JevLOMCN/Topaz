<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLnodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase
 */
class BaseTest extends MediaWikiUnitTestCase {

	public function testEmpty() {
		$this->assertEquals(
			'<test/>',
			( new MMLbase( 'test' ) )->getEmpty()
		);
	}

	public function testEnd() {
		$this->assertEquals(
			'</test>',
			( new MMLbase( 'test' ) )->getEnd()
		);
	}

	public function testEncapsulate() {
		$this->assertEquals(
			'<test>&lt;script>alert(document.cookies);&lt;/script></test>',
			( new MMLbase( 'test' ) )
				->encapsulate( '<script>alert(document.cookies);</script>' )
		);
	}

	public function testEncapsulateRaw() {
		$this->assertEquals(
			'<test><script>alert(document.cookies);</script></test>',
			( new MMLbase( 'test' ) )
				->encapsulateRaw( '<script>alert(document.cookies);</script>' )
		);
	}

	public function testAttributes() {
		$this->assertEquals(
			'<test data-mjx-texclass="texClass1" a="b">',
			( new MMLbase(
				'test',
				'texClass1',
				[ TAG::CLASSTAG => 'texClass2', 'a' => 'b' ] ) )
				->getStart()
		);
	}

	public function testName() {
		$this->assertEquals(
			'test',
			( new MMLbase( 'test' ) )->name()
		);
	}
}
