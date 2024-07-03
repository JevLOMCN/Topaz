<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Matrix;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Matrix
 */
class MatrixTest extends MediaWikiUnitTestCase {
	private $sampleMatrix;

	protected function setUp(): void {
		parent::setUp();
		$this->sampleMatrix = new Matrix( 'align',
			new TexArray( new TexArray( new Literal( 'a' ) ) ) );
	}

	public function testEmptyMatrix() {
		$this->expectException( ArgumentCountError::class );
		new Matrix();
		throw new TypeError( 'Should not create an empty matrix' );
	}

	public function testNestedArguments() {
		$this->expectException( InvalidArgumentException::class );
		new Matrix(
			'align',
			new TexArray(
				new Literal( 'a' ) ) );
		throw new TypeError( 'Nested arguments have to be type of TexArray' );
	}

	public function testInstanceOfTexNode() {
		// this was an instance of TexNode validation didnt verify in php, for review: is this workaround sufficient ?
		$this->assertEquals( 'MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\TexNode',
			get_parent_class( $this->sampleMatrix ),
			'Should create an instance of TexNode' );
	}

	public function testGetters() {
		$this->assertNotEmpty( $this->sampleMatrix->getTop() );
		$this->assertNotEmpty( $this->sampleMatrix->getMainarg() );
	}

	public function testRenderMatrix() {
		$this->assertEquals( '{\\begin{align}a\\end{align}}', $this->sampleMatrix->render() );
	}

	public function testExtractCurlies() {
		 $this->assertEquals( '{\\begin{align}a\\end{align}}', $this->sampleMatrix->inCurlies(),
			'Should not create extra curlies' );
	}

	public function testExtractIdentifiers() {
		$this->assertEquals( [ 'a' ], $this->sampleMatrix->extractIdentifiers(),
			'Should extract identifiers' );
	}
}
