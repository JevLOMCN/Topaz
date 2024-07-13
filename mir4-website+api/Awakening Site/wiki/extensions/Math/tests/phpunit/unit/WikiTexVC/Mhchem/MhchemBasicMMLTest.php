<?php

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * Some simple tests for testing MML output of TeXVC for
 * equations containing mhchem. Test parsing the new TeX-commands introduced
 * to WikiTexVC for parsing texified mhchem output.
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 *
 */
final class MhchemBasicMMLTest extends MediaWikiUnitTestCase {

	public function testGUIStyleNotation() {
		$input = "{\displaystyle \ce{ C6H5-CHO }}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->renderMML();
		$this->assertStringContainsString( '<mpadded', $mml );
		$this->assertStringContainsString( '<mphantom', $mml );
	}

	public static function provideTestCasesLetters() {
		return [
			[ "Alpha", "A" ],
			[ "Beta", "B" ],
			[ "Chi", "X" ],
			[ "Epsilon", "E" ],
			[ "Eta", "H" ],
			[ "Iota", "I" ],
			[ "Kappa", "K" ],
			[ "Mu", "M" ],
			[ "Nu", "N" ],
			[ "Omicron", "O" ],
			[ "Rho", "P" ],
			[ "Tau", "T" ],
			[ "Zeta", "Z" ]
		];
	}

	/**
	 * @dataProvider provideTestCasesLetters
	 */
	public function testmhchemLetters( $case, $result ) {
		$input = "\ce{\\" . $case . " \ca }";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->renderMML();
		$this->assertStringContainsString( '<mi', $mml );
		$this->assertStringContainsString( $result . '</mi>', $mml );
		$this->assertStringContainsString( '<mo>&#x223C;</mo>', $mml );
	}

	public function testHarpoonsLeftRight() {
		$input = "A \\longLeftrightharpoons L";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->renderMML();
		$this->assertStringContainsString( '<mpadded height="0" depth="0">', $mml );
		$this->assertStringContainsString( '<mspace ', $mml );
	}

	public function testHarpoonsRightLeft() {
		$input = "A \\longRightleftharpoons R";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->renderMML();
		$this->assertStringContainsString( '<mo>&#x2212;</mo>', $mml );
		$this->assertStringContainsString( '&#x21C0;', $mml );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">', $mml );
		$this->assertStringContainsString( '<mspace ', $mml );
	}

	public function testArrowsLeftRight() {
		$input = "A \\longleftrightarrows C";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->renderMML();
		$this->assertStringContainsString( '<mo stretchy="false">&#x27F5;</mo>', $mml );
		$this->assertStringContainsString( '<mo stretchy="false">&#x27F6;</mo>', $mml );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">', $mml );
		$this->assertStringContainsString( '<mspace ', $mml );
	}

	public function testTripleDash() {
		$input = "\\tripledash \\frac{a}{b}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mo>&#x2014;</mo>',
			$res['input']->renderMML() );
	}

	public function testMathchoiceDisplaystyle() {
		$input = "\\displaystyle{\\mathchoice{a}{b}{c}{d}}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mstyle displaystyle="true" scriptlevel="0"><mi>a</mi></mstyle>',
			$res['input']->renderMML() );
	}

	public function testMathchoiceTextstyle() {
		$input = "\\textstyle{\\mathchoice{a}{b}{c}{d}}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mstyle displaystyle="false" scriptlevel="0"><mi>b</mi></mstyle>',
			$res['input']->renderMML() );
	}

	public function testMathchoiceScriptstyle() {
		$input = "\\scriptstyle{\\mathchoice{a}{b}{c}{d}}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mstyle displaystyle="false" scriptlevel="1"><mi>c</mi></mstyle>',
			$res['input']->renderMML() );
	}

	public function testMathchoiceScriptScriptstyle() {
		$input = "\\scriptscriptstyle{\\mathchoice{a}{b}{c}{d}}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mstyle displaystyle="false" scriptlevel="2"><mi>d</mi></mstyle>',
			$res['input']->renderMML() );
	}

	public function testMskip() {
		$input = "\\ce{Cr^{+3}(aq)}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$checkRes = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mspace width="0.111em"></mspace>',
			$checkRes["input"]->renderMML() );
	}

	public function testMkern() {
		$input = "\\ce{A, B}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$checkRes = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mspace width="0.333em"></mspace>',
			$checkRes["input"]->renderMML() );
	}

	public function testRaise() {
		$input = "\\raise{.2em}{-}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="+.2em" depth="-.2em" voffset="+.2em">',
			$checkRes["input"]->renderMML() );
	}

	public function testLower() {
		$input = "\\lower{1em}{-}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="-1em" depth="+1em" voffset="-1em">',
			$checkRes["input"]->renderMML() );
	}

	public function testLower2() {
		$input = "\\lower{-1em}{b}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="+1em" depth="-1em" voffset="+1em">',
			$checkRes["input"]->renderMML() );
	}

	public function testLlap() {
		$input = "\\llap{4}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded width="0" lspace="-1width"><mn>4</mn></mpadded>',
			$checkRes["input"]->renderMML() );
	}

	public function testRlap() {
		$input = "\\rlap{-}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded width="0"><mo>&#x2212;</mo></mpadded>',
			$checkRes["input"]->renderMML() );
	}

	public function testSmash1() {
		$input = "\ce{\\smash[t]{2}}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="0">', $checkRes["input"]->renderMML() );
	}

	public function testSmash2() {
		$input = "\ce{\\smash[b]{x}}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded depth="0">', $checkRes["input"]->renderMML() );
	}

	public function testSmash3() {
		$input = "\ce{\\smash[bt]{2}}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">',
			$checkRes["input"]->renderMML() );
	}

	public function testSmash4() {
		$input = "\ce{\\smash[tb]{2}}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">',
			$checkRes["input"]->renderMML() );
	}

	public function testSmash5() {
		$input = "\ce{\\smash{2}}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$ar = $checkRes["input"]->renderMML();
		$this->assertStringContainsString( '<mpadded height="0" depth="0"', $ar );
	}

}
