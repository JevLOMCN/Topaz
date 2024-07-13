<?php

namespace Test\Parsoid\ParserTests;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\ParserTests\TestFileReader;

/**
 * This class aims at testing the TestFileReader (which reads test files)
 * @coversDefaultClass \Wikimedia\Parsoid\ParserTests\TestFileReader
 */
class TestFileReaderTest extends TestCase {

	/** @covers ::__construct */
	public function testReadBasicTestFile() {
		$tf = TestFileReader::read( __DIR__ . '/data/basicTests.txt' );
		$this->assertCount( 2, $tf->testCases, 'Wrong number of parsed test cases' );
		$this->assertEquals( [ 'version' => 2 ], $tf->fileOptions, 'Wrong options' );
		$this->assertNull( $tf->knownFailuresPath, 'Known failure path should be null' );
		$this->assertNotEquals( $tf->testCases[0]->legacyHtml, $tf->testCases[0]->parsoidHtml,
			'Expected different HTML for Legacy and Parsoid (two declarations)' );
		$this->assertEquals( $tf->testCases[1]->legacyHtml, $tf->testCases[1]->parsoidHtml,
			'Expected same HTML for Legacy and Parsoid (single declaration)' );
	}

	/** @covers ::__construct */
	public function testReadWithKnownFailures() {
		$tf = TestFileReader::read( __DIR__ . '/data/testsWithKnownFailures.txt' );
		$this->assertEquals( __DIR__ . '/data/testsWithKnownFailures-knownFailures.json',
			$tf->knownFailuresPath, 'Wrong test known failures file' );
		$this->assertCount( 0, $tf->testCases[1]->knownFailures,
			'Expected no known failure read' );
		$this->assertCount( 2, $tf->testCases[0]->knownFailures,
			'Expected two known failures read' );
	}

	/** @covers ::__construct */
	public function testIllegalVersion() {
		$this->expectExceptionMessage( 'Duplicate version specification' );
		TestFileReader::read( __DIR__ . '/data/twoVersionsDecl.txt' );
	}

	/** @covers ::__construct */
	public function testNoEndTagInTest() {
		$this->expectException( 'Wikimedia\WikiPEG\SyntaxError' );
		TestFileReader::read( __DIR__ . '/data/testNoEndTag.txt' );
	}
}
