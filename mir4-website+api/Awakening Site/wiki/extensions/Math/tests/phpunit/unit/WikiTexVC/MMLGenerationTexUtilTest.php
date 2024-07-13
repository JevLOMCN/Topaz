<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLComparator;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtilHTML;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * This test is checking the MathML generation from LaTeX by WikiTexVC.
 * It creates a list of basic LaTeX statements from the supported functions
 * of WikiTexVC from TexUtil.php.
 *
 *
 * For Re-Generating the testfile in case of change of macros in texutil.json:
 *
 * 1. Create a new ExportedTexUtilKeys.json by running this test with the ExportKeys flag
 * 2. Create a new TexUtil-Ref file from the new ExportedKeys.json file with maintenances scripts, by running such cmd:
 * "php extensions/Math/maintenance/JsonToMathML.php
 * /var/www/html/extensions/Math/tests/phpunit/unit/WikiTexVC/ExportedTexUtilKeys.json
 * /var/www/html/extensions/Math/TexUtil-Ref.json -i 2"
 * 3. If generated correctly shift the TexUtil-Ref file in it's usual folder
 * WIP: This currently just generates MathML with WikiTexVC, but does not do
 * a comparison.
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 */
class MMLGenerationTexUtilTest extends MediaWikiUnitTestCase {
	private static $SIMILARITYTRESH = 0.7;
	private static $SKIPXMLVALIDATION = true;
	private static $APPLYFILTER = false;
	private static $APPLYCATEGORYFILTER = false;
	private static $SKIPPEDCATEGORIES = [ "mhchemtexified_required" ];
	private static $FILTERSTART = 38;
	private static $FILTERLENGTH = 1;

	private static $GENERATEHTML = false;
	private static $GENERATEDHTMLFILE = __DIR__ . "/MMLGenerationTexUtilTest-Output.html";
	private static $MMLREFFILE = __DIR__ . "/TexUtil-Ref.json";

	/** @var bool export the updated TexUtil-Tex to "./ExportedTexUtilKeys.json" */
	private static $EXPORT_KEYS = false;

	private static $SKIPPEDINDICES = [ 434, 489 ];

	/**
	 * @dataProvider provideTestCases
	 */
	public function testTexVC( $title, $input ) {
		if ( in_array( $input->ctr, self::$SKIPPEDINDICES, true ) ) {
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $title, $input->tex, $input->mmlLaTeXML,
				$input->mmlMathoid, "skipped", "skipped" ], false, self::$GENERATEHTML );
			$this->assertTrue( true );
			return;
		}

		$texVC = new TexVC();
		$useMHChem = self::getMHChem( $title ) || $input->type === "chem";

		// Fetching the result from WikiTexVC
		$resultT = $texVC->check( $input->tex, [
			'debug' => false,
			'usemathrm' => false,
			'oldtexvc' => false,
			'usemhchem' => $useMHChem,
			'usemhchemtexified' => true
		] );

		$mathMLtexVC = isset( $resultT["input"] ) ? MMLTestUtil::getMMLwrapped( $resultT["input"] ) :
			"<math> error texvc </math>";

		$mmlComparator = new MMLComparator();
		$usedMMLRef = $input->mmlMathoid ?? $input->mmlLaTeXML ?? "<math><merror> error no ref </merror></math>";

		if ( !$usedMMLRef ) {
			$usedMMLRef = $input->mmlLaTeXML;
		}
		$compRes = $mmlComparator->compareMathML( $usedMMLRef, $mathMLtexVC );
		MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $title, $input->tex, $input->mmlLaTeXML ??
			"no ref", $input->mmlMathoid ?? "no ref", $mathMLtexVC, $compRes['similarityF'] ],
			false, self::$GENERATEHTML );

		// Comparing the result either to MathML result from Mathoid
		if ( !self::$SKIPXMLVALIDATION ) {
			if ( $compRes['similarityF'] >= self::$SIMILARITYTRESH ) {
				$this->assertTrue( true );
			} else {
				$this->assertXmlStringEqualsXmlString( $usedMMLRef, $mathMLtexVC );
			}
		} else {
			$this->assertTrue( true );
		}
	}

	private const SETS = [
		'big_literals',
		'box_functions',
		'color_function',
		'declh_function',
		'definecolor_function',
		'fun_ar1',
		'fun_ar1nb',
		'fun_ar1opt',
		'fun_ar2',
		'fun_ar2nb',
		'fun_infix',
		'fun_mhchem',
		'hline_function',
		'latex_function_names',
		'left_function',
		'mediawiki_function_names',
		'mhchem_bond',
		'mhchem_macro_1p',
		'mhchem_macro_2p',
		'mhchem_macro_2pc',
		'mhchem_macro_2pu',
		'mhchem_single_macro',
		"mhchemtexified_required",
		'nullary_macro',
		'nullary_macro_in_mbox',
		'other_delimiters1',
		'other_delimiters2',
		'right_function'
	];

	private const ARG_CNTS = [
		"big_literals" => 1,
		"box_functions" => 1,
		"color_function" => 1,
		"definecolor_function" => 1,
		"fun_ar1" => 1,
		"fun_ar1nb" => 1,
		"fun_ar1opt" => 1,
		"fun_ar2" => 2,
		"fun_infix" => 1,
		"fun_ar2nb" => 5,
		"fun_mhchem" => 1,
		"left_function" => 1,
		"right_function" => 1,
		"mhchem_bond" => 1,
		"mhchem_macro_1p" => 1,
		"mhchem_macro_2p" => 2,
		"mhchem_macro_2pu" => 1
	];
	private const OTHER_ARGS = [
		"declh_function" => true,
	];

	private const SAMPLE_ARGS_RIGHT = [
		"big_literals" => '(',
		"fun_ar2nb" => '{_1^2}{_3^4}\\sum',
		"left_function" => '( \\right.',
		"mhchem_bond" => '{-}',
		"right_function" => ')',

	];

	private const SAMPLE_ARGS_LEFT = [
		"right_function" => '\\left(',
	];

	private const ENTRY_ARGS = [
		"\\atop" => "{ a \\atop b }",
		"\\choose" => "{ a \\choose b }",
		"\\over" => "{a \\over b }",
		"\\color" => "a {b \\color{red} c} d",
		"\\ce{\\color}" => "\\ce{a {b \\color{red} c} d}",
		"\\definecolor" => "\\definecolor{ultramarine}{RGB}{0,32,96} a {b \\color{ultramarine} c} d",
		"\\pagecolor" => "\\pagecolor{red} e^{i \\pi}",
		"\\hline" => "\n\\begin{array}{|c||c|} a & b  \\\\\n\\hline\n1&2 \n\\end{array}\n",
		"\\nolimits" => " \mathop{\\rm cos}\\nolimits^2",
		"\\llap" => "\\llap{40}",
		"\\rlap" => "\\rlap{120}",
		"\\smash" => "\\smash[t]{2}",
		"\\lower" => "\\lower{1em}{-}",
		"\\raise" => "\\raise{1em}{-}",
		"\\mkern" => "\\mkern{3mu}",
		"\\kern" => "\\kern{1.5mu}",
		"\\mskip" => "\\mskip{2mu}",
		"\\longLeftrightharpoons" => "\\longLeftrightharpoons{}",
		"\\longRightleftharpoons" => "\\longRightleftharpoons{}",
		"\\longleftrightarrows" => "\\longleftrightarrows{}",
		"\\longrightleftharpoons" => "\\longleftrightarrows{}",
		"\\tripledash" => "\\tripledash",
		"\\mathchoice" => "\\mathchoice{a}{b}{c}{d}",
		// "\\limits" =>" \mathop{\\rm cos}\\limits^2",
		"\\limits" => "\\lim\\limits_{x \\to 2}",
		"\\displaystyle"  => "\\frac{\\displaystyle \\sum_{k=1}^N k^2}{a}",
		"\\scriptscriptstyle" => "\\frac ab + \\scriptscriptstyle{\\frac cd + \\frac ef} + \\frac gh",
		"\\scriptstyle" => "{\\scriptstyle \\partial \\Omega}",
		"\\textstyle" => "\\textstyle \\sum_{k=1}^N k^2",
		// Failing examples: ="\\vbox{{a}{b}}""\\vbox{\\vhb{eight}\\vhb{gnat}}"
		// "\\vbox{\\hbox{eight}\\hbox{gnat}}";
		"\\vbox" => "\\vbox{ab}",
		"\\emph" => "\\mathit{\\emph{a}} \\emph{b}",
		// it seems not supported for math, not in any other en_wiki test etc. probably make sense
		// to drop or substitute with \\vert
		"\\vline" => "\n\\begin{array}{|c||c|} a & b \\vline c  \\\\\n\\hline\n1&2 \n\\end{array}\n",
	];

	/**
	 * Check from the test title if it is a mhchem-test.
	 * Return a boolean indicator for this.
	 * @param string $title test title
	 * @return bool indicator if the test is mhchem related
	 */
	public static function getMHChem( string $title ): bool {
		$useMHChem = false;
		if ( str_contains( $title, "chem" ) ) {
			$useMHChem = true;
		}
		return $useMHChem;
	}

	public static function setUpBeforeClass(): void {
		MMLTestUtilHTML::generateHTMLstart( self::$GENERATEDHTMLFILE, [ "name", "TeX-Input", "MathML(LaTeXML)",
			"MathML(Mathoid)", "MathML(WikiTexVC)", "F-Similarity" ], self::$GENERATEHTML );
	}

	public static function tearDownAfterClass(): void {
		MMLTestUtilHTML::generateHTMLEnd( self::$GENERATEDHTMLFILE, self::$GENERATEHTML );
	}

	/**
	 * Generate testcases with texutil, filter them and provide them to the testrunner.
	 * Fetch the corresponding reference MathML from the file defined as $MMLREFFILE
	 * @return array
	 */
	public static function provideTestCases() {
		$refFileContent = (array)MMLTestUtil::getJSON( self::$MMLREFFILE );
		$refAssociative = [];
		foreach ( $refFileContent as $entry ) {
			$refAssociative[$entry->tex] = $entry;
		}

		$groups = self::createGroups();
		$overAllCtr = 0;
		$finalCases = [];
		foreach ( $groups  as $category => $group ) {
			if ( self::$APPLYCATEGORYFILTER && in_array( $category, self::$SKIPPEDCATEGORIES, true ) ) {
				continue;
			}
			$indexCtr = 0;
			foreach ( $group as $case ) {
				$title = "set#" . $overAllCtr . ": " . $category . $indexCtr;
				if ( $refAssociative[$case] ) {
					$finalCase = $refAssociative[$case];
				} else {
					$type = str_starts_with( $case, "ce" ) ? "chem" : "tex";
					$finalCase = (object)[ "tex" => $case, "type" => $type, "ctr" => null ];
				}

				if ( $category === "mhchemtexified_required" ) {
					$finalCase->type = "chem";
				}
				$finalCase->ctr = $overAllCtr;

				$finalCases[$title] = [ $title, $finalCase ];
				$indexCtr++;
				$overAllCtr++;
			}
		}
		if ( self::$APPLYFILTER ) {
			$finalCases = array_slice( $finalCases, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		if ( self::$EXPORT_KEYS ) {
			// Creating a reference file for lookup in JsonToMathML maintenance script.
			$dataToExport = [];
			foreach ( $finalCases as $case ) {
				$dataToExport[$case[1]->tex] = $case[1]->type;
			}
			self::writeToFile( __DIR__ . "/ExportedTexUtilKeys.json", $dataToExport );
		}
		return $finalCases;
	}

	public static function writeToFile( string $fullPath, array $allEntries ): void {
		$jsonData = json_encode( $allEntries, JSON_PRETTY_PRINT );
		file_put_contents( $fullPath, $jsonData );
	}

	private static function addArgs( $set, $entry ) {
		if ( isset( self::ENTRY_ARGS[$entry] ) ) {
			// Some entries have specific mappings for non-group related arguments
			if ( str_starts_with( $set, "mhchem" ) ) {
				return '\\ce{' . self::ENTRY_ARGS[$entry] . '}';
			} else {
				return ( self::ENTRY_ARGS[$entry] );
			}
		}
		$count = !isset( self::ARG_CNTS[$set] ) ? 0 : self::ARG_CNTS[$set];
		$argsR = '';
		$argsL = '';
		if ( !isset( self::SAMPLE_ARGS_RIGHT[$set] ) ) {
			for ( $i = 0; $i < $count; $i++ ) {
				$argsR .= '{' . chr( 97 + $i ) . '}';
			}
		} else {
			$argsR = self::SAMPLE_ARGS_RIGHT[$set];
		}
		if ( isset( self::SAMPLE_ARGS_LEFT[$set] ) ) {
			$argsL = self::SAMPLE_ARGS_LEFT[$set];
		}
		if ( $argsR == '' && isset( self::OTHER_ARGS[$set] ) ) {
			if ( self::OTHER_ARGS[$set] ) {
				return "{" . $entry . " a }";
			}
		}
		if ( str_starts_with( $set, "mhchem" ) ) {
			$rendering = '\\ce{' . $argsL . $entry . $argsR . '}';
		} else {
			$rendering = $argsL . $entry . $argsR;
		}
		return $rendering;
	}

	private static function createGroups() {
		$groups = [];
		foreach ( self::SETS as $set ) {
			$entries = array_keys( TexUtil::getInstance()->getBaseElements()[$set] );
			foreach ( $entries as &$entry ) {
				$entry = self::addArgs( $set, $entry );
			}
			$groups[$set] = $entries;
		}
		return $groups;
	}
}
