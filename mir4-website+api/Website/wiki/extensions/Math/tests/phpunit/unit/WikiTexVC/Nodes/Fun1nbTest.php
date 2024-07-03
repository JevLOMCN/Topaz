<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1nb;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1nb
 */
class Fun1nbTest extends MediaWikiUnitTestCase {

	public function testEmptyFun1nb() {
		$this->expectException( ArgumentCountError::class );
		new Fun1nb();
		throw new ArgumentCountError( 'Should not create an empty fun1nb' );
	}

	public function testOneArgumentFun1nb() {
		$this->expectException( ArgumentCountError::class );
		new Fun1nb( '\\f' );
		throw new ArgumentCountError( 'Should not create a fun1nb with one argument' );
	}

	public function testIncorrectTypeFun1nb() {
		$this->expectException( TypeError::class );
		new Fun1nb( '\\f', 'x' );
		throw new TypeError( 'Should not create a fun1nb with incorrect type' );
	}

	public function testBasicFunctionFun1nb() {
		$fun1nb = new Fun1nb( '\\f', new Literal( 'a' ) );
		$this->assertEquals( '\\f {a} ', $fun1nb->render(), 'Should create a basic function' );
	}

	public function testGetters() {
		$fun1nb = new Fun1nb( '\\f', new Literal( 'a' ) );
		$this->assertNotEmpty( $fun1nb->getFname() );
		$this->assertNotEmpty( $fun1nb->getArg() );
	}

	public function testCurliesFun1nb() {
		$f = new Fun1nb( '\\f', new Literal( 'a' ) );
		$this->assertEquals( '{\\f {a} }', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}
}
