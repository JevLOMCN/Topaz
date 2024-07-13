<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray
 */
class TexArrayTest extends MediaWikiUnitTestCase {

	public function testIsTexNode() {
		$this->assertTrue( new TexArray() instanceof TexNode,
			'Should create an instance of TexNode' );
	}

	public function testConcatOutput() {
		$ta = new TexArray( new Literal( 'a' ), new Literal( 'b' ) );
		$this->assertEquals( 'ab', $ta->render(), 'Should concatenate its input' );
	}

	public function testExtractCurlies() {
		$n = new TexNode( new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( '{a}', $n->inCurlies(),
			'Should create exactly one pair of curlies' );
	}

	public function testExtractIdentifiers() {
		$n = new TexArray( new Literal( 'd' ) );
		$this->assertEquals( [ 'd' ], $n->extractIdentifiers(),
			'Should extract identifiers' );
	}

	public function testExtractIdentifiersFromArg() {
		$n = new TexArray();
		$this->assertEquals( [ 'd' ],
			$n->extractIdentifiers( [ new Literal( 'd' ) ] ),
			'Should extract identifiers from the argument' );
	}

	public function testExtractSplitIdentifiers() {
		$n = new TexArray( new Literal( 'a' ), new Literal( '\'' ) );
		$this->assertEquals( [ 'a\'' ], $n->extractIdentifiers(),
			'Should extract split identifiers' );
	}

	public function testNotConfuseIntegralsIdentifiers() {
		$n = new TexArray( new Literal( 'd' ), new Literal( '\\int' ) );
		$this->assertEquals( [ 'd' ], $n->extractIdentifiers(),
			'Should not confuse integrals and identifiers' );
	}

	public function testNotConfuseIntegralD() {
		$n = new TexArray( new Literal( '\\int' ), new Literal( 'd' ) );
		$this->assertEquals( [], $n->extractIdentifiers(), 'Should not confuse integral d with d identifier' );
	}

	public function testNotConfuseUprightIntegralD() {
		$n = new TexArray( new Literal( '\\int' ), new Literal( '\\mathrm{d}' ) );
		$this->assertEquals( [], $n->extractIdentifiers(),
			'Should not confuse upright integral d with d identifier' );
	}

	public function testExtractIdentifierMods() {
		$n = new TexArray( new TexNode( '' ) );
		$this->assertEquals( [], $n->getModIdent(), 'Should extract identifier modifications' );
	}

	public function testExtractSubscripts() {
		$n = new TexArray( new TexNode( '' ) );
		$this->assertEquals( [], $n->extractSubscripts(), 'Should extract subscripts' );
	}

	public function testUnshift() {
		$n = new TexArray( new TexNode( '' ) );
		$n->unshift( 'test1', 'test2' );
		$this->assertEquals( 3, $n->getLength(), 'Should unshift elements' );
	}
}
