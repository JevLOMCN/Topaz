<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Curly;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWikiUnitTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ
 */
class DQTest extends MediaWikiUnitTestCase {

	public function testEmptyDQ() {
		$this->expectException( ArgumentCountError::class );
		new DQ();
		throw new ArgumentCountError( 'Should not create an empty dq' );
	}

	public function testOneArgumentDQ() {
		$this->expectException( ArgumentCountError::class );
		new DQ( new Literal( 'a' ) );
		throw new ArgumentCountError( 'Should not create a dq with one argument' );
	}

	public function testIncorrectTypeDQ() {
		$this->expectException( TypeError::class );
		new DQ( 'a', 'b' );
		throw new RuntimeException( 'Should not create a dq with incorrect type' );
	}

	public function testBasicDQ() {
		$dq = new DQ( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( 'a_{b}', $dq->render(), 'Should create a basic dq' );
	}

	public function testGetters() {
		$dq = new DQ( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertNotEmpty( $dq->getBase() );
		$this->assertNotEmpty( $dq->getDown() );
	}

	public function testEmptyBaseDQ() {
		$dq = new DQ( new TexNode(), new Literal( 'b' ) );
		$this->assertEquals( '_{b}', $dq->render(), 'Should create an empty base dq' );
	}

	public function testRenderEmptyDq() {
		$dq = new DQ( new Curly( new TexArray() ), new Literal( 'b' ) );
		$this->assertStringContainsString( ( new MMLmrow() )->getEmpty(), $dq->renderMML() );
	}

}
