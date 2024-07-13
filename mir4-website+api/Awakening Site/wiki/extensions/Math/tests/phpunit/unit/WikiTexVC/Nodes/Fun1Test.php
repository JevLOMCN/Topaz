<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Curly;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\FQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1
 */
class Fun1Test extends MediaWikiUnitTestCase {

	public function testEmptyFun1() {
		$this->expectException( ArgumentCountError::class );
		new Fun1();
		throw new ArgumentCountError( 'Should not create an empty fun1' );
	}

	public function testOneArgumentFun1() {
		$this->expectException( ArgumentCountError::class );
		new Fun1( '\\f' );
		throw new ArgumentCountError( 'Should not create a fun1 with one argument' );
	}

	public function testIncorrectTypeFun1() {
		$this->expectException( TypeError::class );
		new Fun1( '\\f', 'x' );
		throw new RuntimeException( 'Should not create a fun1 with incorrect type' );
	}

	public function testBasicFunctionFun1() {
		$f = new Fun1( '\\f', new Literal( 'a' ) );
		$this->assertEquals( '{\\f {a}}', $f->render(),
			'Should create a basic function' );
	}

	public function testCurliesFun1() {
		$f = new Fun1( '\\f', new Literal( 'a' ) );
		$this->assertEquals( '{\\f {a}}', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}

	public function testExtractIdentifiersFun1() {
		$f = new Fun1( '\\mathbf', new Literal( 'B' ) );
		$this->assertEquals( [ '\\mathbf{B}' ], $f->extractIdentifiers(),
			'Should extract identifiers' );
	}

	public function testExtractExtendedLiteralsFun1() {
		$f = new Fun1( '\\mathbf', new Literal( '\\infty' ) );
		$this->assertEquals( [], $f->extractIdentifiers(),
			'Should not extract extended literals as identifiers.' );
	}

	public function testGetters() {
		$f = new Fun1( '\\mathbf', new Literal( '\\infty' ) );
		$this->assertNotEmpty( $f->getFname() );
		$this->assertNotEmpty( $f->getArg() );
	}

	public function testExtractPhantomIdentifiers() {
		$f = new Fun1( '\\hphantom', new Literal( 'A' ) );
		$this->assertEquals( [], $f->extractIdentifiers(),
			'Should not extract phantom identifiers.' );
	}

	public function testIgnoreUnknownFunctions() {
		$f = new Fun1( '\\unknown', new Literal( 'A' ) );
		$this->assertEquals( [ 'A' ], $f->extractIdentifiers(),
			'Should ignore unknown functions.' );
	}

	public function testExtractIdentifierMods() {
		$f = new Fun1( '\\mathbf', new Literal( 'B' ) );
		$this->assertEquals( [ '\\mathbf{B}' ], $f->getModIdent(),
			'Should extract identifier modifications.' );
	}

	public function testExtractSubscripts() {
		$f = new Fun1( '\\mathbf', new Literal( 'B' ) );
		$this->assertEquals( [ '\\mathbf{B}' ],
			$f->extractSubscripts(), 'Should extract subscripts.' );
	}

	public function testExtractSubscriptsExtendedLits() {
		$f = new Fun1( '\\mathbf', new Literal( '\\infty' ) );
		$this->assertEquals( [ '\\mathbf{\\infty}' ], $f->extractSubscripts(),
			'Should extract subscripts for extended literals.' );
	}

	public function testExtractSubscriptsEmptyMods() {
		$f = new Fun1( '\\mathbf', new Literal( '' ) );
		$this->assertEquals( [], $f->extractSubscripts(),
			'Should not extract subscripts for empty mods.' );
	}

	public function testMathRmLiteral() {
		$f = new Fun1( '\\mathrm', new Literal( 'b' ) );
		$this->assertStringContainsString( 'mathvariant="normal"', $f->renderMML() );
	}

	public function testMathRmCurly() {
		$f = new Fun1( '\\mathrm', new Curly( new TexArray(
			new Literal( 'a' ),
			new Literal( 'b' ),
		) ) );
		$rendering = $f->renderMML();
		preg_match_all( '/mathvariant="normal"/', $rendering, $matches );
		$this->assertCount( 2, $matches[0] );
	}

	public function testMathRmDq() {
		$f = new Fun1( '\\mathrm', new Curly( new TexArray(
			new DQ( new Literal( 'a' ), new Literal( 'b' ) )
		) ) );
		$rendering = $f->renderMML();
		preg_match_all( '/mathvariant="normal"/', $rendering, $matches );
		$this->assertCount( 2, $matches[0] );
	}

	public function testMathRmFq() {
		$f = new Fun1( '\\mathrm', new Curly( new TexArray(
			new FQ( new Literal( 'a' ), new Literal( 'b' ), new Literal( 'c' ) )
		) ) );
		$rendering = $f->renderMML();
		preg_match_all( '/mathvariant="normal"/', $rendering, $matches );
		$this->assertCount( 2, $matches[0] );
	}
}
