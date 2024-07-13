<?php
// phpcs:disable Generic.Files.LineLength.TooLong

namespace Test\Parsoid\Wt2Html\TreeBuilder;

use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Wt2Html\TreeBuilder\TreeBuilderStage;

class TreeBuilderStageTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\TreeBuilder\TreeBuilderStage
	 * @dataProvider provideTreeBuilder
	 */
	public function testTreeBuilder( array $tokens, string $expected ) {
		$mockEnv = new MockEnv( [] );
		$tb = new TreeBuilderStage( $mockEnv );
		$tb->resetState( [ 'toplevel' => true ] );
		$tb->processChunk( $tokens );
		$body = $tb->finalizeDOM();
		$this->assertEquals( $expected, DOMCompat::getInnerHTML( $body ) );
	}

	public function provideTreeBuilder(): array {
		return [
			[
				[
					new TagTk( 'p' ),
					'Testing 123',
					new EndTagTk( 'p' ),
					new EOFTk()
				],
				'<p data-object-id="0">Testing 123</p>'
			]
		];
	}

}
