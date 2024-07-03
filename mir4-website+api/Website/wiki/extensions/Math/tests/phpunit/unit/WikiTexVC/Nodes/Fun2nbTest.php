<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2nb;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2nb
 */
class Fun2nbTest extends MediaWikiUnitTestCase {

	public function testEmptyFun2nb() {
		$this->expectException( ArgumentCountError::class );
		new Fun2nb();
		throw new ArgumentCountError( 'Should not create an empty fun2nb' );
	}

	public function testOneArgumentFun2nb() {
		$this->expectException( ArgumentCountError::class );
		new Fun2nb( '\\f' );
		throw new ArgumentCountError( 'Should not create a fun2nb with one argument' );
	}

	public function testIncorrectTypeFun2nb() {
		$this->expectException( TypeError::class );
		new Fun2nb( '\\f', 'x', 'y' );
		throw new TypeError( 'Should not create a fun2nb with incorrect type' );
	}

	public function testBasicFunctionFun2nb() {
		$fq = new Fun2nb( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( '\\f {a}{b}', $fq->render(), 'Should create a basic function' );
	}

	public function testCurliesFun2nb() {
		$f = new Fun2nb( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( '{\\f {a}{b}}', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}
}
