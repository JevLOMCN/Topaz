<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\ChemWord;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\ChemWord
 */
class ChemWordTest extends MediaWikiUnitTestCase {

	public function testEmptyChemWord() {
		$this->expectException( ArgumentCountError::class );
		new ChemWord();
		throw new ArgumentCountError( 'Should not create an empty ChemWord' );
	}

	public function testOneArgumentChemWord() {
		$this->expectException( ArgumentCountError::class );
		new ChemWord( new Literal( 'a' ) );
		throw new ArgumentCountError( 'Should not create a ChemWord with one argument' );
	}

	public function testIncorrectTypeChemWord() {
		$this->expectException( TypeError::class );
		new ChemWord( 'a', 'b' );
		throw new RuntimeException( 'Should not create a ChemWord with incorrect type' );
	}

	public function testBasicFunctionChemWord() {
		$chemWord = new ChemWord( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( 'ab', $chemWord->render(), 'Should create a basic function' );
	}

	public function testGetters() {
		$chemWord = new ChemWord( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertNotEmpty( $chemWord->getLeft() );
		$this->assertNotEmpty( $chemWord->getRight() );
	}

	public function testExtractIdentifiersBox() {
		$chemWord = new ChemWord( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( [], $chemWord->extractIdentifiers(), 'Should extract identifiers' );
	}
}
