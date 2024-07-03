<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal
 */
class LiteralTest extends MediaWikiUnitTestCase {

	public function testBaseclass() {
		$this->expectException( ArgumentCountError::class );
		new Literal();
		throw new ArgumentCountError( 'Should not create an empty literal' );
	}

	public function testOneArgument() {
		$this->expectException( ArgumentCountError::class );
		new Literal( 'a', 'b' );
		throw new ArgumentCountError( 'Should not create a literal with more than one argument' );
	}

	public function testArgumentType() {
		$this->expectException( TypeError::class );
		new Literal( new Texnode() );
		throw new TypeError( 'Should not create a literal with incorrect type' );
	}

	public function testOnlyOneArgument() {
		$lit = new Literal( 'hello world' );
		$this->assertEquals( 'hello world', $lit->render(),
			'Should create an literal with only one argument' );
	}

	public function testRenderNodeBase() {
		$lit = new Literal( 'hello world' );
		$node = new TexNode( $lit );
		$this->assertEquals( 'hello world', $node->render(),
			'Should render within node base class' );
	}

	public function testNode() {
		$lit = new Literal( 'hello world' );
		$node = new TexNode( $lit );
		$this->assertEquals( 'hello world', $node->render(),
			'Should render within node base class' );
	}

	public function testExtractIdentifierModifications() {
		$n = new Literal( 'a' );
		$this->assertEquals( [ 'a' ], $n->getModIdent(),
			'Should extract identifier modifications' );
	}

	public function testExtraSpace() {
		$n = new Literal( '\\ ' );
		$this->assertEquals( [ '\\ ' ], $n->getModIdent(),
			'Identifier modifications should report extra space' );
	}

	public function testGetters() {
		$lit = new Literal( 'hello world' );
		$this->assertNotEmpty( $lit->getExtendedLiterals() );
		$this->assertNotEmpty( $lit->getLiterals() );
		$this->assertNotEmpty( $lit->getArg() );
	}

	public function testExtractSubscripts() {
		$n = new Literal( '\\beta' );
		$this->assertEquals( [ '\\beta' ], $n->extractSubscripts(),
			'Should extract subscripts' );
	}

	public function testVLineNotInMatrix() {
		$n = new Literal( '\\vline' );
		$this->assertEquals( '<mi>\vline</mi>', $n->renderMML(),
			'vline should fall through' );
	}

	public function testVLineInMatrix() {
		$n = new Literal( '\\vline' );
		$this->assertStringContainsString( '|</mo>', $n->renderMML( [], [ 'inMatrix' => true ] ),
			'vline should render a vertical bar operator in matrix context.' );
	}

	public function testHBoxLiterals() {
		$n = new Literal( 'in box' );
		$this->assertStringContainsString( 'in box', $n->renderMML( [], [ 'inHBox' => true ] ),
			'hboxes should not be wrapped in to mi elements.' );
	}

}
