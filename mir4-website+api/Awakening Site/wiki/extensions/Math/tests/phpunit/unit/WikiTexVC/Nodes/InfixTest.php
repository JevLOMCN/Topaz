<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Infix;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Infix
 */
class InfixTest extends MediaWikiUnitTestCase {

	public function testEmptyInfix() {
		$this->expectException( ArgumentCountError::class );
		new Infix();
		throw new ArgumentCountError( 'Should not create an empty infix' );
	}

	public function testOneArgumentInfix() {
		$this->expectException( ArgumentCountError::class );
		new Infix( '\\f' );
		throw new ArgumentCountError( 'Should not create an infix with one argument' );
	}

	public function testIncorrectTypeInfix() {
		$this->expectException( TypeError::class );
		new Infix( '\\atop', 'x', 'y' );
		throw new TypeError( 'Should not create an infix with incorrect type' );
	}

	public function testBasicInfix() {
		$infix = new Infix( '\\atop',
			new TexArray( new Literal( 'a' ) ),
			new TexArray( new Literal( 'b' ) ) );
		$this->assertEquals( '{a \\atop b}', $infix->render(), 'Should create a basic infix' );
	}

	public function testGetters() {
		$infix = new Infix( '\\atop',
			new TexArray( new Literal( 'a' ) ),
			new TexArray( new Literal( 'b' ) ) );
		$this->assertNotEmpty( $infix->getOp() );
		$this->assertNotEmpty( $infix->getArg1() );
		$this->assertNotEmpty( $infix->getArg2() );
	}

	public function testCurliesInfix() {
		$f = new Infix( '\\atop',
			new TexArray( new Literal( 'a' ) ),
			new TexArray( new Literal( 'b' ) ) );
		$this->assertEquals( '{a \\atop b}', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}

	public function testExtractIdentifiersInfix() {
		$f = new Infix( '\\atop',
			new TexArray( new Literal( 'a' ) ),
			new TexArray( new Literal( 'b' ) ) );
		$this->assertEquals( [ 'a', 'b' ], $f->extractIdentifiers(),
			'Should extract identifiers' );
	}
}
