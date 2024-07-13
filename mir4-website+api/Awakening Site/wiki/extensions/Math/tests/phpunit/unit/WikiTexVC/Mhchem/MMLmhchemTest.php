<?php
namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use Exception;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtilHTML;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * This test checks the functionality MHCHem module within MediaWiki environment
 * It is running all defined testcases from "Mhchemv4mml.json" these are from test.html from javascript mhchemparser.
 * Mhchemv4mml.json can be recreated by running the maintenance script JsonToMathML with Mhchemv4tex.json
 * within a MediaWiki environment:
 * 'php extensions/Math/maintenance/JsonToMathML.php
 *   /var/www/html/extensions/Math/tests/phpunit/unit/WikiTexVC/Mhchem/Mhchemv4tex.json
 *   /var/www/html/extensions/Math/tests/phpunit/unit/WikiTexVC/Mhchem/Mhchemv4mml.json -i 3'
 *
 * Settings for running the testbench are defined as constants within this class.
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 */
final class MMLmhchemTest extends MediaWikiUnitTestCase {
	private static bool $LOGMHCHEM = false;
	private static bool $SKIPXMLVALIDATION = false;
	private static string $FILENAMEREF = __DIR__ . "/Mhchemv4mml.json";

	private static bool $APPLYFILTER = false;
	private static int $FILTERSTART = 92;
	private static int $FILTERLENGTH = 1;
	private static bool $GENERATEFILES = false;
	private static string $GENERATEDHTMLFILE = __DIR__ . "/MMLmhchemTest-Output.html";
	private static string $GENERATEDWIKIFILE = __DIR__ . "/chemtest.wiki";

	private static array $SKIPPEDINDICES = [ 36, 40, 50, 51, 91, 92, 93, 94 ];

	public static function writeWikifileHeader( $filename ) {
		unlink( $filename );
		self::writeToFile( $filename, "{| class=\"wikitable\"\n|-\n !colspan=\"3\"|\n" .
			"|+ comparison of rendering for chemical formulas \n" .
			"|- \n ! TeX !! Rendering(native) !! Rendering(default)}" );
	}

	public static function writeWikifileRow( $filename, $tex ) {
		$text = "|- \n"
			. "| <syntaxhighlight lang=\"latex\" enclose=\"none\">" . $tex . "</syntaxhighlight> \n"
			. "|<chem forcemathmode=\"native\">" . $tex . "</chem> \n"
			. "|<chem>" . $tex . "</chem>";
		self::writeToFile( $filename, $text );
	}

	public static function writeToFile( $filename, $line ) {
		$myfile = fopen( $filename, "a" );
		fwrite( $myfile, $line . "\n" );
		fclose( $myfile );
	}

	public static function setUpBeforeClass(): void {
		MMLTestUtilHTML::generateHTMLstart( self::$GENERATEDHTMLFILE, [ "name", "TeX-Input",
			"Tex-MhchemParser", "Tex-PHP-Mhchem", "MathML-LaTeXML", "MathML-WikiTexVC" ],
			self::$GENERATEFILES );

		if ( self::$GENERATEFILES ) {
			self::writeWikifileHeader( self::$GENERATEDWIKIFILE );
		}
	}

	public static function tearDownAfterClass(): void {
		MMLTestUtilHTML::generateHTMLEnd( self::$GENERATEDHTMLFILE, self::$GENERATEFILES );
	}

	/**
	 * @dataProvider provideTestCases
	 * @throws Exception
	 */
	public function testTexVC( $title, $tc ) {
		$texVC = new TexVC();

		if ( in_array( $tc->ctr, self::$SKIPPEDINDICES ) ) {
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $tc->ctr, $tc->tex,
				"skipped", "skipped", "skipped", "skipped" ], false, self::$GENERATEFILES );
			$this->assertTrue( true );
			return;
		}
		if ( self::$GENERATEFILES ) {
			self::writeWikifileRow( self::$GENERATEDWIKIFILE, $tc->tex );
		}

		# Fetch result from WikiTexVC(PHP)
		$mhchemParser = new MhchemParser( self::$LOGMHCHEM );
		$mhchemOutput = $mhchemParser->toTex( $tc->tex, $tc->typeC, true );

		$warnings = [];
		$resTexVC = $texVC->check( $mhchemOutput, [
			'debug' => false,
			'usemathrm' => true,
			'oldtexvc' => false,
			'usemhchem' => true,
			"usemhchemtexified" => true
		], $warnings, false );
		$mathMLtexVC = isset( $resTexVC["input"] ) ? MMLTestUtil::getMMLwrapped( $resTexVC["input"] ) :
			"<math> error texvc </math>";

		MMLTestUtilHTML::generateHTMLtableRow(
			self::$GENERATEDHTMLFILE, [ $title, $tc->tex, $tc->texNew, $mhchemOutput, $tc->mml_latexml ?? "no mathml",
			$mathMLtexVC ],
			false, self::$GENERATEFILES );

		if ( !self::$SKIPXMLVALIDATION ) {
			$this->assertEquals( $tc->texNew, $mhchemOutput );
		} else {
			$this->assertTrue( true );
		}
	}

	public static function provideTestCases() {
		$fileTestcases = MMLTestUtil::getJSON( self::$FILENAMEREF );

		$f = [];
		// Adding running indices for location of tests.
		$ctr = 0;
		foreach ( $fileTestcases as $tcF ) {
			$tc = [
				"ctr" => $ctr,
				"tex" => $tcF->tex,
				"texNew" => $tcF->texNew,
				"type" => $tcF->type,
				"typeC" => $tcF->typeC,
				"mml_mathoid" => $tcF->mmlMathoid,
				"mml_latexml" => $tcF->mmlLaTeXML,
			];
			$f[] = [ "tc#" . str_pad( $ctr, 3, '0', STR_PAD_LEFT ) . " " . $tcF->description,
				(object)$tc ];
			$ctr++;
		}
		// Filtering results by index if necessary
		if ( self::$APPLYFILTER ) {
			$f = array_slice( $f, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $f;
	}
}
