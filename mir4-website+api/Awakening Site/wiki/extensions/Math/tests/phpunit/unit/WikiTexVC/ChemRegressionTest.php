<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC;

use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Parser
 *
 * @group Stub
 */
class ChemRegressionTest extends MediaWikiUnitTestCase {
	private $texVC;
	private const CHUNK_SIZE = 100;

	private const FILEPATH = __DIR__ . '/chem-regression.json';

	public static function setUpBeforeClass(): void {
		if ( !file_exists( self::FILEPATH ) ) {
			self::markTestSkipped( 'No test file found at specified path: ' . self::FILEPATH );
		}
		parent::setUpBeforeClass();
	}

	protected function setUp(): void {
		parent::setUp();
		$this->texVC = new TexVC();
	}

	/**
	 * Reads the json file to an object
	 * @return array json with testcases
	 */
	private function getJSON(): array {
		$file = file_get_contents( self::FILEPATH );
		return json_decode( $file, true );
	}

	private function mkgroups( $arr, $n ) {
		$result = [];
		$group = [];
		$seen = [];
		foreach ( $arr as $elem ) {
			if ( array_key_exists( $elem["input"], $seen ) ) {
				continue;
			} else {
				$seen[$elem["input"]] = true;
			}
			array_push( $group, $elem );
			if ( count( $group ) >= $n ) {
				array_push( $result, $group );
				$group = [];
			}
		}
		if ( count( $group ) > 0 ) {
			array_push( $result, $group );
		}
		return $result;
	}

	public function testAllChemRegression() {
		$texVC = new TexVC();
		$groups = $this->mkgroups( $this->getJSON(), self::CHUNK_SIZE );
		foreach ( $groups as  $group ) {
			foreach ( $group as $testcase ) {
				$testHash = $testcase["inputhash"];
				$f = $testcase["input"];
				$type = $testcase["type"];
				try {
					$options = [
						"debug" => false,
						"usemathrm" => false,
						"oldtexvc" => false
					];

					if ( $type === "chem" ) {
						$options["usemhchem"] = true;
					}

					$result = $texVC->check( $testcase["input"], $options );
					$this->assertEquals( '+', $result["status"], $testHash . " with input: " . $f );
				} catch ( PhpPegJs\SyntaxError $ex ) {
					$message = "Syntax error: " . $ex->getMessage() .
						' at line ' . $ex->grammarLine . ' column ' .
						$ex->grammarColumn . ' offset ' . $ex->grammarOffset;

					$this->assertTrue( false, $message );
				}
			}
		}
	}
}
