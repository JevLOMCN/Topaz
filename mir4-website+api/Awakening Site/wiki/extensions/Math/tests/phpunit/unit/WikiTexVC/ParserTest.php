<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC;

use MediaWiki\Extension\Math\WikiTexVC\Parser;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Parser
 */
class ParserTest extends MediaWikiUnitTestCase {
	private $testCases;
	private $parser;

	protected function setUp(): void {
		parent::setUp();
		$this->parser = new Parser();

		$this->testCases = [
			[
				'in' => '',
			],
			[
				'in' => 'a',
			],
			[
				'in' => 'a^2',
			],
			[
				'in' => 'a^2+b^{2}',
			],
			[
				'in' => 'l_a^2+l_b^2=l_c^2',
			]
		];
	}

	public function testSimpleParse() {
		foreach ( $this->testCases as $case ) {
			$this->parser->parse( $case['in'], [ 'debug' => true ] );
			$this->assertTrue( true, 'Should parse: ' . $case['in'] );
		}
	}

	public function testExample() {
		$this->parser->parse( '\\sin(x)+{}{}\\cos(x)^2 newcommand', [ 'debug' => true ] );
		$this->assertTrue( true, 'Should parse texVC example' );
	}

	public function testSpecific() {
		 $resultR = $this->parser->parse( '\\reals' );
		 $resultS = $this->parser->parse( '\\mathbb{R}' );
		 $this->assertEquals( $resultR, $resultS, 'Should parse texVC specific functions' );
	}
}
