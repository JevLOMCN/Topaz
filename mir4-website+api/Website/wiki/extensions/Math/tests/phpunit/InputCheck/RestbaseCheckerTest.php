<?php

use MediaWiki\Extension\Math\InputCheck\RestbaseChecker;
use MediaWiki\Extension\Math\Tests\MathMockHttpTrait;

/**
 * @group Math
 *
 * @license GPL-2.0-or-later
 *
 * @covers \MediaWiki\Extension\Math\InputCheck\RestbaseChecker
 */
class RestbaseCheckerTest extends MediaWikiIntegrationTestCase {
	use MathMockHttpTrait;

	public function testValid() {
		$this->setupGoodMathRestBaseMockHttp();

		$checker = new RestbaseChecker( '\sin x^2' );
		$this->assertNull( $checker->getError() );
		$this->assertTrue( $checker->isValid() );
		$this->assertNull( $checker->getError() );
		$this->assertSame( '\\sin x^{2}', $checker->getValidTex() );
	}

	public function testInvalid() {
		$this->setupBadMathRestBaseMockHttp();

		$checker = new RestbaseChecker( '\sin\newcommand' );
		$this->assertNull( $checker->getError() );
		$this->assertFalse( $checker->isValid() );
		$this->assertStringContainsString(
			Message::newFromKey( 'math_unknown_function', '\newcommand' )
				->inContentLanguage()
				->escaped(),
			$checker->getError()
				->inContentLanguage()
				->escaped()
		);
		$this->assertNull( $checker->getValidTex() );
	}

	public function testErrorSyntax() {
		$this->setupSyntaxErrorRestBaseMockHttp();

		$checker = new RestbaseChecker( '\left(' );
		$this->assertFalse( $checker->isValid() );
		$this->assertStringContainsString(
			Message::newFromKey( 'math_syntax_error' )
				->inContentLanguage()
				->escaped(),
			$checker->getError()
				->inContentLanguage()
				->escaped()
		);
	}
}
