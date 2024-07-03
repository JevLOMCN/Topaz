<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun4;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun4
 */
class Fun4Test extends MediaWikiUnitTestCase {

	public function testEmptyFun4() {
		$this->expectException( ArgumentCountError::class );
		new Fun4();
		throw new ArgumentCountError( 'Should not create an empty fun4' );
	}

	public function testOneArgumentFun4() {
		$this->expectException( ArgumentCountError::class );
		new Fun4( '\\f' );
		throw new ArgumentCountError( 'Should not create a fun4 with one argument' );
	}

	public function testIncorrectTypeFun4() {
		$this->expectException( TypeError::class );
		new Fun4( '\\f', 'a', 'b', 'c', 'd' );
		throw new RuntimeException( 'Should not create a fun4 with incorrect types' );
	}

	public function testBasicFunctionFun4() {
		$f = new Fun4( '\\f',
			new Literal( 'a' ),
			new Literal( 'b' ),
			new Literal( 'c' ),
			new Literal( 'd' ) );
		$this->assertEquals( '{\\f {a}{b}{c}{d}}', $f->render(),
			'Should create a basic function' );
	}

	public function testGetters() {
		$f = new Fun4( '\\f',
			new Literal( 'a' ),
			new Literal( 'b' ),
			new Literal( 'c' ),
			new Literal( 'd' ) );
		$this->assertNotEmpty( $f->getFname() );
		$this->assertNotEmpty( $f->getArg1() );
		$this->assertNotEmpty( $f->getArg2() );
		$this->assertNotEmpty( $f->getArg3() );
		$this->assertNotEmpty( $f->getArg4() );
	}

	public function testCurliesFun4() {
		$f = new Fun4( '\\f',
			new Literal( 'a' ),
			new Literal( 'b' ),
			new Literal( 'c' ),
			new Literal( 'd' ) );
		$this->assertEquals( '{\\f {a}{b}{c}{d}}', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}

	public function testExtractIdentifiersFun4() {
		$f = new Fun4( '\\f',
			new Literal( 'a' ),
			new Literal( 'b' ),
			new Literal( 'c' ),
			new Literal( 'd' ) );
		$this->assertEquals( [ 'a', 'b', 'c', 'd' ], $f->extractIdentifiers(),
			'Should extract identifiers' );
	}
}
