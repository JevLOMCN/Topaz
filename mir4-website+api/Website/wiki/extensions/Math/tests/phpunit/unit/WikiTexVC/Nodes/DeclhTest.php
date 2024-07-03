<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Declh;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Declh
 */
class DeclhTest extends MediaWikiUnitTestCase {

	public function testEmptyDeclh() {
		$this->expectException( ArgumentCountError::class );
		new Declh();
		throw new ArgumentCountError( 'Should not create an empty Declh' );
	}

	public function testOneArgumentDeclh() {
		$this->expectException( ArgumentCountError::class );
		new Declh( '\\f' );
		throw new ArgumentCountError( 'Should not create a Declh with one argument' );
	}

	public function testIncorrectTypeDeclh() {
		$this->expectException( TypeError::class );
		new Declh( '\\f', 'x' );
		throw new RuntimeException( 'Should not create a Declh with incorrect type' );
	}

	public function testBasicFunctionDeclh() {
		$f = new Declh( '\\rm', new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( '{\\rm {a}}', $f->render(), 'Should create a basic function' );
	}

	public function testGetters() {
		$f = new Declh( '\\rm', new TexArray( new Literal( 'a' ) ) );
		$this->assertNotEmpty( $f->getFname() );
		$this->assertNotEmpty( $f->getArg() );
	}

	public function testTwoArgsFunctionDeclh() {
		$f = new Declh( '\\rm',
			new TexArray( new Literal( 'a' ), new Literal( 'b' ) ) );
		$this->assertEquals( '{\\rm {ab}}',
			$f->render(), 'Should create a function with two arguments' );
	}

	public function testCurliesDeclh() {
		$f = new Declh( '\\f', new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( '{\\f {a}}', $f->inCurlies(), 'Should create exactly one set of curlies' );
	}

	public function testExtractIdentifiersDeclh() {
		$f = new Declh( '\\rm', new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( [ 'a' ], $f->extractIdentifiers(), 'Should extract identifiers' );
	}

	public function testExtractNoIdentifiersDeclh() {
		$f = new Declh( '\\rm', new TexArray() );
		$this->assertEquals( [], $f->extractIdentifiers(), 'Should extract identifiers' );
	}

	public function testExtractIdentifiersMultiDeclh() {
		$f = new Declh( '\\rm', new TexArray( new Literal( 'a' ), new Literal( 'b' ) ) );
		$this->assertEquals( [ 'ab' ], $f->extractIdentifiers(), 'Should extract multiple identifiers' );
	}

	public function testNotExtractSomeSubscripts() {
		$f = new Declh( '\\bf', new TexArray( new Literal( '' ) ) );
		$this->assertEquals( [], $f->extractSubscripts(),
			'Should not extract empty font modifier subscripts identifiers' );
	}

	public function testSubscriptsForFontMod() {
		$mods = [ 'rm', 'it', 'cal', 'bf' ];
		foreach ( $mods as $mod ) {
			$f = new Declh( "\\{$mod}", new TexArray( new Literal( 'a' ) ) );
			$this->assertEquals( [ "\\math{$mod}{a}" ], $f->extractSubscripts(),
				"Should extract subscripts for {$mod} font modification" );
		}
	}

	public function testRenderMML() {
		$f = new Declh( '\\bf', new TexArray( new Literal( 'a' ) ) );
		$this->assertStringContainsString( 'mathvariant="bold"', $f->renderMML(), 'MathML should render bold' );
	}
}
