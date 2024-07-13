<?php

use DataValues\NumberValue;
use DataValues\StringValue;
use MediaWiki\Extension\Math\MathValidator;

/**
 * @covers \MediaWiki\Extension\Math\MathValidator
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathValidatorTest extends MediaWikiIntegrationTestCase {
	use MockHttpTrait;

	private const VADLID_TEX = "\sin x";
	private const INVADLID_TEX = "\\notExists";

	public function testNotStringValue() {
		$validator = new MathValidator();
		$this->expectException( InvalidArgumentException::class );
		$validator->validate( new NumberValue( 0 ) );
	}

	public function testNullValue() {
		$validator = new MathValidator();
		$this->expectException( InvalidArgumentException::class );
		$validator->validate( null );
	}

	public function testValidInput() {
		$this->installMockHttp( $this->makeFakeHttpRequest( file_get_contents( __DIR__ .
			'/InputCheck/data/mathoid/sinx.json' ) ) );
		$validator = new MathValidator();
		$result = $validator->validate( new StringValue( self::VADLID_TEX ) );
		$this->assertInstanceOf( \ValueValidators\Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testInvalidInput() {
		$this->installMockHttp( $this->makeFakeHttpRequest( file_get_contents( __DIR__ .
			'/InputCheck/data/mathoid/invalidF.json' ), 400 ) );
		$validator = new MathValidator();
		$result = $validator->validate( new StringValue( self::INVADLID_TEX ) );
		$this->assertInstanceOf( \ValueValidators\Result::class, $result );
		$this->assertFalse( $result->isValid() );
	}
}
