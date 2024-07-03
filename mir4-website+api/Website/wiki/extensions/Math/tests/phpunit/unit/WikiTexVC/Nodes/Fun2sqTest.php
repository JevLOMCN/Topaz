<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2sq;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2sq
 */
class Fun2sqTest extends MediaWikiUnitTestCase {

	public function testEmptyFun2sq() {
		$this->expectException( ArgumentCountError::class );
		new Fun2sq();
		throw new ArgumentCountError( 'Should not create an empty fun2sq' );
	}

	public function testOneArgumentFun2sq() {
		$this->expectException( ArgumentCountError::class );
		new Fun2sq( '\\f' );
		throw new ArgumentCountError( 'Should not create a fun2sq with one argument' );
	}

	public function testIncorrectTypeFun2sq() {
		$this->expectException( TypeError::class );
		new Fun2sq( '\\f', 'x', 'y' );
		throw new TypeError( 'Should not create a fun2sq with incorrect type' );
	}

	public function testBasicFunctionFun2sq() {
		$f = new Fun2sq( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( '{\\f[a]{b}}', $f->render(), 'Should create a basic function' );
	}

	public function testCurliesFun2sq() {
		$f = new Fun2sq( '\\f', new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( '{\\f[a]{b}}', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}
}
