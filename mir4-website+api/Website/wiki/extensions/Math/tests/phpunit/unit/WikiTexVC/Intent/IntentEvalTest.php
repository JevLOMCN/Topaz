<?php
namespace MediaWiki\Extension\Math\WikiTexVC\Intent;

use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * Test for intent validation functions and checking basic usage of the experimental
 * intent annotation feature.
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 */
final class IntentEvalTest extends MediaWikiUnitTestCase {
	public function testTwoArgumentApplication() {
		$input = "first-derivative(\$a)(\$b)";
		$texvc = new TexVC();
		$ret = $texvc->checkIntent( $input );
		$this->assertTrue( $ret );
	}

	public function testIntentShouldFailIfNotActive() {
		$texVC = new TexVC();
		$resultT = $texVC->check( "\\intent{\\binom{n}{k}}{intent='binomial(\$n,\$k)'}", [
			"useintent" => false,
		] );
		$this->assertEquals( "C", $resultT["status"] );
		$this->assertStringContainsString( "virtual intent package required", $resultT["details"] );
	}
}
