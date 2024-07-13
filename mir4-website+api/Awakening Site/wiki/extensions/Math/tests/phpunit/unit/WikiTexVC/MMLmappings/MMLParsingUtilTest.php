<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil
 */
class MMLParsingUtilTest extends MediaWikiUnitTestCase {

	public function testInvalidColor() {
		$result = MMLParsingUtil::parseDefineColorExpression( "INVALID" );
		$this->assertNull( $result );
	}

	public function testRGBOne() {
		$result = MMLParsingUtil::parseDefineColorExpression(
			"\\definecolor {ultramarine}{rgb}{0,0.12549019607843,0.37647058823529}" );
		$this->assertEquals( 'ultramarine', $result['name'] );
		$this->assertEquals( 'rgb', $result['type'] );
		$this->assertEquals( '#002060', $result['hex'] );
	}

	public function testInvalidColorString() {
		$result = MMLParsingUtil::parseDefineColorExpression(
			"\\definecolor {gray}{0.123}" );
		$this->assertNull( $result );
	}

}
