<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\ChemFun2u;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\ChemFun2u
 */
class ChemFun2uTest extends MediaWikiUnitTestCase {

	public function testEmptyChemFun2u() {
		$this->expectException( ArgumentCountError::class );
		new ChemFun2u();
		throw new ArgumentCountError( 'Should not create an empty ChemFun2u' );
	}

	public function testOneArgumentChemFun2u() {
		$this->expectException( ArgumentCountError::class );
		new ChemFun2u( 'a' );
		throw new ArgumentCountError( 'Should not create a ChemFun2u with one argument' );
	}

	public function testIncorrectTypeChemFun2u() {
		$this->expectException( TypeError::class );
		new ChemFun2u( 'a', 'b', 'c' );
		throw new TypeError( 'Should not create a ChemFun2u with incorrect type' );
	}

	public function testBasicChemFun2u() {
		$fun2u = new ChemFun2u( 'a', new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertEquals( 'a{b}_{c}', $fun2u->render(), 'Should create a basic ChemFun2u' );
	}

	public function testGetters() {
		$fun2u = new ChemFun2u( 'a', new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertNotEmpty( $fun2u->getFname() );
		$this->assertNotEmpty( $fun2u->getLeft() );
		$this->assertNotEmpty( $fun2u->getRight() );
	}

	public function testExtractIdentifiers() {
		$fun2u = new ChemFun2u( 'a', new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertEquals( [], $fun2u->extractIdentifiers(), 'Should extract identifiers' );
	}
}
