<?php
namespace MediaWiki\Extension\Math\WikiTexVC\Intent;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * This is a testbench for the currently experimental intent annotation feature.
 * The base files for intent tests and intent annotated are extracted from W3 examples in HTML
 * To (re-)generate the json testfiles from HTML an external program in nodejs is used, this can
 * be found in GitHub: ..... tbd .... add link
 *
 * The output of this test is finally used to create a speech comparison with the mentioned program.
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 */
final class IntentParserTest extends MediaWikiUnitTestCase {
	private static $FILENAMEINTENTTESTS = __DIR__ . "/intent_mathml_testing_extracted.json";
	private static $FILENAMEINTENTANNOTATED = __DIR__ . "/intent_mathml_testing_latex_annotated.json";
	private static $APPLYFILTER = false;
	private static $FILTERSTART = 3;
	private static $FILTERLENGTH = 2;
	private static $GENERATEJSONFILE = false;
	private static $GENERATEDJSONFILE = __DIR__ . "/IntentParserTestLocal-Output.json";
	private static $SKIPPEDINDICES = [ 67 ];

	public static function setUpBeforeClass(): void {
		self::writeToFile( "[\n", "w" );
	}

	public static function tearDownAfterClass(): void {
		self::writeToFile( "\n]", "a" );
	}

	public static function writeToFile( $data, $mode ): void {
		if ( !self::$GENERATEJSONFILE ) {
			return;
		}
		$fp = fopen( self::$GENERATEDJSONFILE, $mode );
		fwrite( $fp, $data );
		fclose( $fp );
	}

	public static function writeToJSONFile( $jsonData, $addComma = false ): void {
		if ( !self::$GENERATEJSONFILE ) {
			return;
		}
		$jsonString = json_encode( $jsonData, JSON_PRETTY_PRINT );
		$fp = fopen( self::$GENERATEDJSONFILE, 'a' );
		if ( $addComma ) {
			$jsonString = $jsonString . ",";
		}
		fwrite( $fp, $jsonString );
		fclose( $fp );
	}

	/**
	 * @dataProvider provideTestCases
	 */
	public function testTexVC( $title, $tc ) {
		$texVC = new TexVC();
		if ( $tc->skipped == true || in_array( $tc->ctr, self::$SKIPPEDINDICES, true ) ) {
			$this->assertTrue( true );
			return;
		}
		# Fetch result from TexVC(PHP)
		$resultT = $texVC->check( $tc->latexi, [
			'debug' => false,
			'usemathrm' => $tc->usemathrm ?? false,
			"useintent" => true,
			"usemhchem" => $tc->usemhchem,
			'oldtexvc' => $tc->oldtexvc ?? false
		] );
		if ( !isset( $resultT["input"] ) ) {
			if ( $tc->shouldfail ) {
				$this->assertTrue( true );
				return;
			}
		}
		$mathMLtexVC = MMLTestUtil::getMMLwrapped( $resultT["input"] );
		$writeObj = [
			"id" => $tc->id,
			"latex" => $tc->latex,
			"latexi" => $tc->latexi,
			"MathML_texvc" => $mathMLtexVC,
			"MathML_default" => $tc->mathML_default,
			"MathML_explicit" => $tc->mathML_explicit,
			"Name" => $tc->name,
		];
		self::writeToJSONFile( $writeObj, $tc->ctr != 86 ? true : false );
		$this->assertTrue( true );
	}

	public static function provideTestCases() {
		$resIntent = MMLTestUtil::getJSON( self::$FILENAMEINTENTTESTS );
		$resIntent2 = MMLTestUtil::getJSON( self::$FILENAMEINTENTANNOTATED );
		$f = [];
		// Adding running indices for location of tests.
		foreach ( $resIntent as $index => $tcIntent ) {
			$tcIntent->ctr = $index;
			$tcIntent2 = $resIntent2[$index];
			$tcIntent->latexi = isset( $tcIntent2->latexi ) ? $tcIntent2->latexi : "";
			$tcIntent->shouldfail = isset( $tcIntent2->shouldfail ) ? $tcIntent2->shouldfail : false;
			$tcIntent->skipped = isset( $tcIntent2->skipped ) ? $tcIntent2->skipped : false;
			$tcIntent->usemhchem = isset( $tcIntent2->usemhchem ) ? $tcIntent2->usemhchem : false;
			array_push( $f, [ $tcIntent->name, (object)$tcIntent ] );
		}
		// Filtering results by index if necessary
		if ( self::$APPLYFILTER ) {
			$f = array_slice( $f, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $f;
	}
}
