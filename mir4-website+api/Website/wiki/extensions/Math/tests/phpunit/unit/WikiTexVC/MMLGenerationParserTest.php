<?php
namespace MediaWiki\Extension\Math\WikiTexVC;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLComparator;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtilHTML;
use MediaWikiUnitTestCase;
use Psr\Log\InvalidArgumentException;

/**
 * This is a very basic test for running more cases for MathML generation.
 * WIP: This tests is for running the specified testfiles in development.
 * Categories can be selected within 'provideTestCases' functions.
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 */
final class MMLGenerationParserTest extends MediaWikiUnitTestCase {
	private static $SIMILARITYTRESH = 0.7;
	private static $SKIPXMLVALIDATION = true;

	private static $SELECTEDCATEGORY1 = "texvctreebugs";
	private static $FILENAME1 = __DIR__ . "/tex-2-mml.json";
	private static $FILENAME2 = __DIR__ . "/ParserTest-Ref.json";
	private static $SELECTEDFILE = 0; // 0 , 1 ... for selecting file
	private static $APPLYFILTER = false;
	private static $FILTERSTART = 0;
	private static $FILTERLENGTH = 50;

	private static $GENERATEHTML = false;
	private static $GENERATEDHTMLFILE = __DIR__ . "/MMLGenerationParserTest-Output.html";

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public static function setUpBeforeClass(): void {
		MMLTestUtilHTML::generateHTMLstart( self::$GENERATEDHTMLFILE, [ "name", "Tex-Input",
			"MathML(LaTeXML)", "MathML(Mathoid)", "MathML(WikiTexVC)", "F-Similarity" ], self::$GENERATEHTML );
	}

	public static function tearDownAfterClass(): void {
		MMLTestUtilHTML::generateHTMLEnd( self::$GENERATEDHTMLFILE, self::$GENERATEHTML );
	}

	/**
	 * @dataProvider provideTestCases
	 */
	public function testTexVC( $title, $tc ) {
		$texVC = new TexVC();

		if ( $tc->skipped ?? false ) {
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $tc->ctr, $tc->input,
				"skipped", "skipped" ], false, self::$GENERATEHTML );
			$this->assertTrue( true );
			return;
		}
		# Fetch result from WikiTexVC(PHP)
		$resultT = $texVC->check( $tc->input, [
			'debug' => false,
			'usemathrm' => $tc->usemathrm ?? false,
			'oldtexvc' => $tc->oldtexvc ?? false
		] );

		$mathMLtexVC = MMLTestUtil::getMMLwrapped( $resultT["input"] );
		if ( self::$SELECTEDFILE == 0 ) {
			// File 0 has no refs, is just for checking basics.
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $tc->ctr,
				$tc->input, $tc->mmlLaTeXML ?? "tbd", "tbd",
				$mathMLtexVC, -0.0 ], false, self::$GENERATEHTML );
			$this->assertTrue( true );
			return;
		}
		$mmlComparator = new MMLComparator();
		$compRes = $mmlComparator->compareMathML( $tc->mmlMathoid, $mathMLtexVC );
		MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $tc->ctr,
			$tc->input, $tc->mmlLaTeXML ?? "tbd", $tc->mmlMathoid ?? "tbd",
			$mathMLtexVC, $compRes['similarityF'] ], false, self::$GENERATEHTML );

		if ( !self::$SKIPXMLVALIDATION ) {
			if ( !$tc->mmlMathoid ) {
				$this->fail( "No Mathoid reference found for: " . $tc->input );
			}
			if ( $compRes['similarityF'] >= self::$SIMILARITYTRESH ) {
				$this->assertTrue( true );
			} else {
				$this->assertXmlStringEqualsXmlString( $tc->mmlMathoid, $mathMLtexVC );
			}
		} else {
			$this->assertTrue( true );
		}
	}

	public static function provideTestCases() {
		switch ( self::$SELECTEDFILE ) {
			case 0:
				return self::provideTestCases1();
			case 1:
				return self::provideTestCases2();
			default:
				self::throwException( new InvalidArgumentException( "No correct file specified" ) );
				return [];
		}
	}

	/**
	 * Provide testcases and filter and format them for
	 * the first testfile 'tex-2-mml.json'.
	 * @return array
	 */
	public static function provideTestCases1() {
		$res = MMLTestUtil::getJSON( self::$FILENAME1 );
		$f = $res->{self::$SELECTEDCATEGORY1};

		// Adding running indices for location of tests.
		$indexCtr = 0;
		foreach ( $f as $tc ) {
			$tc[1]->ctr = $indexCtr;
			$indexCtr += 1;
		}
		// Filtering results by index if necessary
		if ( self::$APPLYFILTER ) {
			$f = array_slice( $f, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $f;
	}

	/**
	 * Provide testcases and filter and format them for
	 * the second testfile 'ParserTest.json'.
	 * @return array
	 */
	public static function provideTestCases2() {
		$res = MMLTestUtil::getJSON( self::$FILENAME2 );
		$f = [];
		// Adding running indices for location of tests.
		$indexCtr = 0;
		foreach ( $res as $tc ) {
			$tc->ctr = $indexCtr;
			$tc->input = $tc->tex; // Just to have uniform access here
			$indexCtr += 1;
			array_push( $f, [ "title N/A", $tc ] );
		}
		// Filtering results by index if necessary
		if ( self::$APPLYFILTER ) {
			$f = array_slice( $f, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $f;
	}
}
