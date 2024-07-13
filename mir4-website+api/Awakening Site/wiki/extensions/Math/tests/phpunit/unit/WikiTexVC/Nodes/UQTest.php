<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1nb;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\UQ;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\UQ
 */
class UQTest extends MediaWikiUnitTestCase {

	public function testEmptyUQ() {
		$this->expectException( ArgumentCountError::class );
		new UQ();
		throw new ArgumentCountError( 'Should not create an empty uq' );
	}

	public function testOneArgumentUQ() {
		$this->expectException( ArgumentCountError::class );
		new UQ( new Literal( 'a' ) );
		throw new ArgumentCountError( 'Should not create a uq with one argument' );
	}

	public function testIncorrectTypeUQ() {
		$this->expectException( TypeError::class );
		new UQ( 'a', 'b' );
		throw new RuntimeException( 'Should not create a uq with incorrect type' );
	}

	public function testBasicUQ() {
		$uq = new UQ( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( 'a^{b}', $uq->render(), 'Should create a basic uq' );
	}

	public function testGetters() {
		$uq = new UQ( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertNotEmpty( $uq->getBase() );
		$this->assertNotEmpty( $uq->getUp() );
	}

	public function testEmptyBaseUQ() {
		$uq = new UQ( new TexNode(), new Literal( 'b' ) );
		$this->assertEquals( '^{b}', $uq->render(), 'Should create an empty base uq' );
	}

	public function testOperatorname() {
		$uq = new UQ( new Fun1nb( '\\operatorname', new Literal( 'a' ) ), new Literal( 'b' ) );
		$this->assertStringContainsString( '<msup', $uq->renderMML(),
			'Operator superscript should be rendered after the operator.' );
	}

	public function testOverOperator() {
		$uq = new UQ( new Fun1nb( '\\overarc', new Literal( 'a' ) ), new Literal( 'b' ) );
		$this->assertStringContainsString( '<mover', $uq->renderMML(),
			'Over_operators be rendered over the operator.' );
	}
}
