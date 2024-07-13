<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Big;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Big
 */
class BigTest extends MediaWikiUnitTestCase {

	public function testEmptyBig() {
		$this->expectException( ArgumentCountError::class );
		new Big();
		throw new ArgumentCountError( 'Should not create an empty big' );
	}

	public function testOneArgumentBig() {
		$this->expectException( ArgumentCountError::class );
		new Big( '\\big' );
		throw new ArgumentCountError( 'Should not create a big with one argument' );
	}

	public function testIncorrectTypeBig() {
		$this->expectException( TypeError::class );
		new Big( '\\big', new Literal( 'a' ) );
		throw new RuntimeException( 'Should not create a big with incorrect type' );
	}

	public function testBasicFunctionBig() {
		$big = new Big( '\\big', 'a' );
		$this->assertEquals( '{\\big a}', $big->render(), 'Should create a basic function' );
	}

	public function testGetters() {
		$big = new Big( '\\big', 'a' );
		$this->assertNotEmpty( $big->getArg() );
		$this->assertNotEmpty( $big->getFname() );
	}

	public function testExtractIdentifiersBig() {
		$big = new Big( '\\big', 'a' );
		$this->assertEquals( [], $big->extractIdentifiers(), 'Should extract identifiers' );
	}

	public function testCurliesBig() {
		$big = new Big( '\\big', 'a' );
		$this->assertEquals( '{\\big a}', $big->inCurlies(), 'Should create exactly one set of curlies' );
	}

	public function testRenderMML() {
		$big = new Big( '\\big', 'a' );
		$this->assertStringContainsString( '</mrow>', $big->renderMML(), 'Should render to MathML' );
	}
}
