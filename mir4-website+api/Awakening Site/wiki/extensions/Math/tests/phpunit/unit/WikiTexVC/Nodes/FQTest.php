<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Curly;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\FQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\FQ
 */
class FQTest extends MediaWikiUnitTestCase {

	public function testEmptyFQ() {
		$this->expectException( ArgumentCountError::class );
		new FQ();
		throw new ArgumentCountError( 'Should not create an empty fq' );
	}

	public function testOneArgumentFQ() {
		$this->expectException( ArgumentCountError::class );
		new FQ( new Literal( 'a' ) );
		throw new ArgumentCountError( 'Should not create a fq with one argument' );
	}

	public function testIncorrectTypeFQ() {
		$this->expectException( TypeError::class );
		new FQ( 'a', 'b', 'c' );
		throw new TypeError( 'Should not create a fq with incorrect type' );
	}

	public function testBasicFQ() {
		$fq = new FQ( new Literal( 'a' ), new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertEquals( 'a_{b}^{c}', $fq->render(), 'Should create a basic fq' );
	}

	public function testGetters() {
		$fq = new FQ( new Literal( 'a' ), new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertNotEmpty( $fq->getBase() );
		$this->assertNotEmpty( $fq->getUp() );
		$this->assertNotEmpty( $fq->getDown() );
	}

	public function testRenderEmptyFq() {
		$fq = new FQ( new Curly( new TexArray() ), new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertStringContainsString( ( new MMLmrow() )->getEmpty(), $fq->renderMML() );
	}

	public function testLatin() {
		$fq = new FQ( new Literal( 'a' ), new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertStringContainsString( 'msubsup', $fq->renderMML() );
	}

	public function testSum() {
		$fq = new FQ( new Literal( '\sum' ), new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertStringContainsString( 'munderover', $fq->renderMML() );
	}

	public function testGreek() {
		$fq = new FQ( new Literal( '\\alpha' ), new Literal( 'b' ), new Literal( 'c' ) );
		$this->assertStringContainsString( 'msubsup', $fq->renderMML() );
	}
}
