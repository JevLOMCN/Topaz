<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Curly;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Curly
 */
class CurlyTest extends MediaWikiUnitTestCase {

	public function testEmptyDollar() {
		$this->expectException( ArgumentCountError::class );
		new Curly();
		throw new ArgumentCountError( 'Should not create an empty curly' );
	}

	public function testOneArgumentCurly() {
		$this->expectException( ArgumentCountError::class );
		new Curly( new TexArray( new TexNode( 'a' ) ), new TexArray( new TexNode( 'b' ) ) );
		throw new ArgumentCountError( 'Should not create a curly with more than one argument' );
	}

	public function testIncorrectTypeCurly() {
		$this->expectException( TypeError::class );
		new Curly( new TexNode() );
		throw new RuntimeException( 'Should not create a curly with incorrect type' );
	}

	public function testRenderTexCurly() {
		$curly = new Curly( new TexArray() );
		$this->assertEquals( '{}', $curly->render(), 'Should render a curly with empty tex array' );
	}

	public function testRenderListCurly() {
		$curly = new Curly( new TexArray(
		new Literal( 'hello' ),
		new Literal( ' ' ),
		new Literal( 'world' )
		) );
		$this->assertEquals( '{hello world}', $curly->render(), 'Should render a list' );
	}

	public function testGetters() {
		$curly = new Curly( new TexArray( new Literal( 'b' ) ) );
		$this->assertNotEmpty( $curly->getArgs() );
		$this->assertNotEmpty( $curly->getArg() );
	}

	public function testNoExtraCurliesDQ() {
		$dq = new DQ( new Literal( 'a' ),
			new Curly( new TexArray( new Literal( 'b' ) ) ) );
		$this->assertEquals( 'a_{b}', $dq->render(), 'Should not create extra curlies from dq' );
	}

	public function testNoExtraCurliesCurly() {
		$curly = new Curly( new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( '{a}', $curly->inCurlies(), 'Should not create extra curlies from curly' );
	}

	public function testExtractIdentifierModsCurly() {
		$curly = new Curly( new TexArray( new Literal( 'b' ) ) );
		$this->assertEquals( 'b', $curly->getModIdent(), 'Should extract identifier modifications' );
	}

	public function testExtractSubscirpts() {
		$curly = new Curly( new TexArray( new Literal( 'b' ) ) );
		$this->assertEquals( 'b', $curly->extractSubscripts(), 'Should extract subscripts' );
	}
}
