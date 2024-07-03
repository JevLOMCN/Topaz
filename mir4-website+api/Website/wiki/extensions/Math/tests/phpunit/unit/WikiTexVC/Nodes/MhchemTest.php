<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Mhchem;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Mhchem
 */
class MhchemTest extends MediaWikiUnitTestCase {

	public function testEmptyMhchem() {
		$this->expectException( ArgumentCountError::class );
		new Mhchem();
		throw new ArgumentCountError( 'Should not create an empty Mhchem' );
	}

	public function testOneArgumentMhchem() {
		$this->expectException( ArgumentCountError::class );
		new Mhchem( '\\f' );
		throw new ArgumentCountError( 'Should not create a Mhchem with one argument' );
	}

	public function testIncorrectTypeMhchem() {
		$this->expectException( TypeError::class );
		new Mhchem( '\\f', 'x' );
		throw new TypeError( 'Should not create a Mhchem with incorrect type' );
	}

	public function testBasicFunctionMhchem() {
		$f = new Mhchem( '\\f', new Literal( 'a' ) );
		$this->assertEquals( '{\\f {a}}', $f->render(), 'Should create a basic function' );
	}

	public function testCurliesMhchem() {
		$f = new Mhchem( '\\f', new Literal( 'a' ) );
		$this->assertEquals( '{{\\f {a}}}', $f->inCurlies(),
			'Should create curlies' );
	}

	public function testExtractIdentifiersMhchem() {
		$n = new Mhchem( '\\f', new Literal( 'a' ) );
		$this->assertEquals( [], $n->extractIdentifiers(),
			'Should extract identifiers' );
	}
}
