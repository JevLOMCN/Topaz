<?php

namespace MediaWiki\Extension\Math\InputCheck;

use HashBagOStuff;
use MediaWikiIntegrationTestCase;
use Message;
use WANObjectCache;

/**
 * @group Math
 * @license GPL-2.0-or-later
 * tbd move this to unittests
 * @covers \MediaWiki\Extension\Math\InputCheck\LocalChecker
 */
class LocalCheckerTest extends MediaWikiIntegrationTestCase {

	private const SAMPLE_KEY =
		'global:MediaWiki\Extension\Math\InputCheck\LocalChecker:d5f40adbd26ff8b19b2c33289d7334b6';

	public function testValid() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '\sin x^2' );
		$this->assertNull( $checker->getError() );
		$this->assertTrue( $checker->isValid() );
		$this->assertNull( $checker->getError() );
		$this->assertSame( '\\sin x^{2}', $checker->getValidTex() );
	}

	public function testValidTypeTex() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '\sin x^2', 'tex' );
		$this->assertTrue( $checker->isValid() );
	}

	public function testValidTypeChem() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '{\\displaystyle {\\ce {\\cdot OHNO_{2}}}}', 'chem' );
		$this->assertTrue( $checker->isValid() );
	}

	public function testValidTypeInline() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '{\\textstyle \\log2 }', 'inline-tex' );
		$this->assertTrue( $checker->isValid() );
	}

	public function testInvalidType() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '\sin x^2', 'INVALIDTYPE' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
		$this->assertInstanceOf( Message::class, $checker->getError() );
		$this->assertFalse( $checker->isValid() );
		$this->assertNull( $checker->getPresentationMathMLFragment() );
	}

	public function testInvalid() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '\sin\newcommand' );
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
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '\left(' );
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

	public function testGetMML() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), 'e^{i \pi} + 1 = 0' );
		$mml = $checker->getPresentationMathMLFragment();
		$this->assertStringContainsString( '<mn>0</mn>', $mml );
	}

	public function testGetMMLEmpty() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '' );
		$mml = $checker->getPresentationMathMLFragment();
		$this->assertSame( '', $mml );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\InputCheck\LocalChecker::getInputCacheKey
	 */
	public function testGetKey() {
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '\sin x^2', 'tex' );
		$this->assertSame( self::SAMPLE_KEY, $checker->getInputCacheKey() );
	}

	public function testCache() {
		$fakeWAN = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$fakeContent = [ 'status' => '+', 'output' => 'out', 'mathml' => 'mml' ];
		$fakeWAN->set( self::SAMPLE_KEY,
			$fakeContent,
			WANObjectCache::TTL_INDEFINITE,
			[ 'version' => LocalChecker::VERSION ] );
		$checker = new LocalChecker( $fakeWAN, '\sin x^2', 'tex' );
		$this->assertSame( $fakeContent['output'], $checker->getValidTex() );
		$this->assertSame( $fakeContent['mathml'], $checker->getPresentationMathMLFragment() );
		$this->assertSame( true, $checker->isValid() );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\InputCheck\LocalChecker::runCheck
	 */
	public function testRunChecks() {
		$fakeContent = [
			'status' => '+',
			'mathml' => '<mi>sin</mi><mo>&#x2061;</mo><msup><mi>x</mi>'
						. '<mrow data-mjx-texclass="ORD"><mn>2</mn></mrow></msup>',
			'output' => '\\sin x^{2}'
		];
		$checker = new LocalChecker( WANObjectCache::newEmpty(), '\sin x^2', 'tex' );
		$actual = $checker->runCheck();
		$this->assertArrayEquals( $fakeContent, $actual );
	}
}
