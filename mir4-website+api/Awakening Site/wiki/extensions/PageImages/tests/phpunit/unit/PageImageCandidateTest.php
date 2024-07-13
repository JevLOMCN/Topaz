<?php

namespace MediaWiki\Tests\PageImages;

use File;
use MediaWiki\Title\Title;
use MediaWikiUnitTestCase;
use PageImages\PageImageCandidate;

/**
 * @covers \PageImages\PageImageCandidate
 * @package MediaWiki\Tests\PageImages
 */
class PageImageCandidateTest extends MediaWikiUnitTestCase {

	/**
	 * @param int|bool $width
	 * @param int|bool $height
	 * @return File
	 */
	private function fileMock( $width, $height ): File {
		$title = $this->createMock( Title::class );
		$title->method( 'getDBKey' )->willReturn( 'Testing' );
		$file = $this->createMock( File::class );
		$file->method( 'getTitle' )->willReturn( $title );
		$file->method( 'getWidth' )->willReturn( $width );
		$file->method( 'getHeight' )->willReturn( $height );
		return $file;
	}

	public static function provideTestCases() {
		yield 'file no width, no height' => [
			false, false,
			[], 'Testing', 0, 0, 0
		];
		yield 'file with width and height' => [
			42, 24,
			[], 'Testing', 42, 24, 0
		];
		yield 'file with width and height, handler, no width' => [
			42, 24,
			[ 'handler' => [] ], 'Testing', 42, 24, 0
		];
		yield 'file with width and height, handler, with width' => [
			42, 24,
			[ 'handler' => [ 'width' => 4224 ] ], 'Testing', 42, 24, 4224
		];
	}

	/**
	 * @dataProvider provideTestCases
	 * @param int|false $mockWidth
	 * @param int|false $mockHeight
	 * @param array $params
	 * @param string $expectedName
	 * @param int $expectedWidth
	 * @param int $expectedHeight
	 * @param int $expectedHandlerWidth
	 */
	public function testNewFromFileAndParams(
		$mockWidth,
		$mockHeight,
		array $params,
		string $expectedName,
		int $expectedWidth,
		int $expectedHeight,
		int $expectedHandlerWidth
	) {
		$file = $this->fileMock( $mockWidth, $mockHeight );
		$image = PageImageCandidate::newFromFileAndParams( $file, $params );
		$this->assertSame( $expectedName, $image->getFileName() );
		$this->assertSame( $expectedWidth, $image->getFullWidth() );
		$this->assertSame( $expectedHeight, $image->getFullHeight() );
		$this->assertSame( $expectedHandlerWidth, $image->getHandlerWidth() );
	}

	/**
	 * @dataProvider provideTestCases
	 * @param int|false $mockWidth
	 * @param int|false $mockHeight
	 * @param array $params
	 */
	public function testSerializeDeserialize( $mockWidth, $mockHeight, array $params ) {
		$file = $this->fileMock( $mockWidth, $mockHeight );
		$candidate = PageImageCandidate::newFromFileAndParams( $file, $params );
		$deserialized = PageImageCandidate::newFromArray( $candidate->jsonSerialize() );
		$this->assertSame( $candidate->getFileName(), $deserialized->getFileName() );
		$this->assertSame( $candidate->getFullHeight(), $deserialized->getFullHeight() );
		$this->assertSame( $candidate->getFullHeight(), $deserialized->getFullHeight() );
		$this->assertSame( $candidate->getHandlerWidth(), $deserialized->getHandlerWidth() );
	}
}
