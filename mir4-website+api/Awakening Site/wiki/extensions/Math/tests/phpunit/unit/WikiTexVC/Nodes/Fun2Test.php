<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2
 */
class Fun2Test extends MediaWikiUnitTestCase {

	public function testEmptyFun2() {
		$this->expectException( ArgumentCountError::class );
		new Fun2();
		throw new ArgumentCountError( 'Should not create an empty fun2' );
	}

	public function testOneArgumentFun2() {
		$this->expectException( ArgumentCountError::class );
		new Fun2( '\\f' );
		throw new ArgumentCountError( 'Should not create a fun2 with one argument' );
	}

	public function testIncorrectTypeFun2() {
		$this->expectException( TypeError::class );
		new Fun2( '\\f', 'x', 'y' );
		throw new RuntimeException( 'Should not create a fun2 with incorrect types' );
	}

	public function testBasicFunctionFun2() {
		$f = new Fun2( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( '{\\f {a}{b}}', $f->render(),
			'Should create a basic function' );
	}

	public function testGetters() {
		$f = new Fun2( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertNotEmpty( $f->getFname() );
		$this->assertNotEmpty( $f->getArg1() );
		$this->assertNotEmpty( $f->getArg2() );
	}

	public function testCurliesFun2() {
		$f = new Fun2( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( '{\\f {a}{b}}', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}

	public function testExtractIdentifiersFun2() {
		$f = new Fun2( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( [ 'a', 'b' ], $f->extractIdentifiers(),
			'Should extract identifiers' );
	}
}
