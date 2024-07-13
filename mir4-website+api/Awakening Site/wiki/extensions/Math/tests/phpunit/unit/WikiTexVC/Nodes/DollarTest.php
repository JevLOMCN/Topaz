<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Dollar;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Dollar
 */
class DollarTest extends MediaWikiUnitTestCase {

	public function testEmptyDollar() {
		$this->expectException( ArgumentCountError::class );
		new Dollar();
		throw new ArgumentCountError( 'Should not create an empty dollar' );
	}

	public function testOneArgumentDollar() {
		$this->expectException( ArgumentCountError::class );
		new Dollar( new TexArray(), new TexArray() );
		throw new ArgumentCountError( 'Should not create a dollar with more than one argument' );
	}

	public function testIncorrectTypeDollar() {
		$this->expectException( TypeError::class );
		new Dollar( new TexNode() );
		throw new RuntimeException( 'Should not create a dollar with incorrect type' );
	}

	public function testRenderTexDollar() {
		$dollar = new Dollar( new TexArray() );
		$this->assertEquals( '$$', $dollar->render(), 'Should render a dollar with empty tex array' );
	}

	public function testRenderListDollar() {
		$dollar = new Dollar( new TexArray(
		new Literal( 'hello' ),
		new Literal( ' ' ),
		new Literal( 'world' )
		) );
		$this->assertEquals( '$hello world$', $dollar->render(), 'Should render a list' );
	}

	public function testExtractIdentifiersDollar() {
		$dollar = new Dollar( new TexArray(
		new Literal( 'a' ),
		new Literal( 'b' ),
		new Literal( 'c' )
		) );
		$this->assertEquals( [], $dollar->extractIdentifiers(), 'Should extract identifiers' );
	}
}
