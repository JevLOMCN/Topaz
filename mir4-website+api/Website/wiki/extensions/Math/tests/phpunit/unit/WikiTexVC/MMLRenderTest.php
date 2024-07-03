<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * These are some specific testcases by MML-Rendering by WikiTexVC.
 * They are explicitly described here instead of in JSON files because
 * Mathoid or LaTeXML do not generate suitable results for reference.
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 */
class MMLRenderTest extends MediaWikiUnitTestCase {
	public function testMathCalUnicode() {
		$input = "\\mathcal{O},  \\mathcal{K}, \\mathcal{t}, \\mathcal{c}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( '&#x1D4AA;', $mathMLtexVC );
		$this->assertStringContainsString( '&#x1D4A6;', $mathMLtexVC );
		$this->assertStringContainsString( '&#x1D4C9;', $mathMLtexVC );
		$this->assertStringContainsString( '&#x1D4B8;', $mathMLtexVC );
	}

	public function testDoubleStruckLiteralUnicode() {
		$input = "\\mathbb{Q},  \\R, \\Complex, \\mathbb{4}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( '&#x211A;', $mathMLtexVC );
		$this->assertStringContainsString( '&#x211D;', $mathMLtexVC );
		$this->assertStringContainsString( '&#x2102;', $mathMLtexVC );
		$this->assertStringContainsString( '&#x1D7DC;', $mathMLtexVC );
	}

	public function testNoLimits() {
		$input = "\\displaystyle \int\\nolimits_0^\infty f(x) dx";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( 'movablelimits="false"', $mathMLtexVC );
		$this->assertStringNotContainsString( "nolimits", $mathMLtexVC );
	}

	public function testGenfracStretching() {
		$input = "\\tbinom{n}{k} \\dbinom{n}{k} \\binom{n}{k}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "maxsize", $mathMLtexVC );
	}

	public function testBracketSizesOpen() {
		$input = "\bigl( \Bigl( \biggl( \Biggl( ";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( 'minsize', $mathMLtexVC );
		$this->assertStringContainsString( 'maxsize', $mathMLtexVC );
	}

	public function testBracketSizesClose() {
		$input = "\bigr) \Bigr) \biggr) \Biggr) ";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( 'minsize', $mathMLtexVC );
		$this->assertStringContainsString( 'maxsize', $mathMLtexVC );
	}

	public function testLimOperatorSpacing() {
		$input = "\liminf v, \limsup w \injlim x \projlim y";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "lim inf", $mathMLtexVC );
		$this->assertStringContainsString( "lim sup", $mathMLtexVC );
		$this->assertStringContainsString( "inj lim", $mathMLtexVC );
		$this->assertStringContainsString( "proj lim", $mathMLtexVC );
	}

	public function testTrimNull() {
		$input = "\\bigl( \\begin{smallmatrix}a&b\\\\ c&d\\end{smallmatrix} \\bigr)";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "mtable", $mathMLtexVC );
	}

	public function testApplyOperator1() {
		$input = "\sup x";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testApplyOperator2() {
		$input = "\sup";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testApplyOperator3() {
		$input = "\sup \sin";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testApplyFunction1() {
		$input = "\sin x";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testApplyFunction2() {
		$input = "\sin";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testApplyFunction3() {
		$input = "\sin{x}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testApplyFunction4() {
		$input = "\sin \sin";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testApplyFunction5() {
		$input = "\cos(x)";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2061;</mo>", $mathMLtexVC );
	}

	public function testSpacesNoMstyle() {
		$input = "\bmod \, \!";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "mspace", $mathMLtexVC );
		$this->assertStringNotContainsString( "mstyle", $mathMLtexVC );
	}

	public function testBigl() {
		$input = "\bigl(";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( TexClass::OPEN, $mathMLtexVC );
	}

	public function testMsupNumChild1() {
		$input = "\sum^{^N}_{k}";
		$mathMLtexVC = str_replace( [ "\n", " " ], "", $this->generateMML( $input ) );
		$this->assertStringContainsString( "<msup><mi/><mrow", $mathMLtexVC );
	}

	public function testMsupNumChild2() {
		$input = "\sum^{a^N}_{k}";
		$mathMLtexVC = str_replace( [ "\n", " " ], "", $this->generateMML( $input ) );
		$this->assertStringNotContainsString( "<msup><mi/><mrow", $mathMLtexVC );
	}

	public function testUndersetNumChild() {
		$input = "\underset{\mathrm{def}}{}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "munder", $mathMLtexVC );
	}

	public function testUndersetNumChild2() {
		$input = "\underset{\mathrm{def}}{\mathrm{g}}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "munder", $mathMLtexVC );
	}

	public function testAlignLeft() {
		$input = " \begin{align} f(x) & = (a+b)^2 \\ & = a^2+2ab+b^2 \\ \\end{align} ";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "columnalign=\"left\"", $mathMLtexVC );
		$this->assertStringContainsString( "mtable", $mathMLtexVC );
	}

	public function testLeftRightAttributes() {
		$input = "\\left( \\right) \\left[ \\right]";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo", $mathMLtexVC );
		$this->assertStringNotContainsString( "stretchy", $mathMLtexVC );
	}

	public function testIntbar1() {
		$input = "\intbar";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo", $mathMLtexVC );
		$this->assertStringContainsString( "&#x2A0D;", $mathMLtexVC );
	}

	public function testIntBar2() {
		$input = "\intBar";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo", $mathMLtexVC );
		$this->assertStringContainsString( "&#x2A0E;", $mathMLtexVC );
	}

	public function testAtop() {
		$input = "{ a \atop b }";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mfrac linethickness=\"0\">", $mathMLtexVC );
	}

	public function testChoose() {
		$input = "{ a \choose b }";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mfrac linethickness=\"0\">", $mathMLtexVC );
		$this->assertStringContainsString( ")", $mathMLtexVC );
		$this->assertStringContainsString( "(", $mathMLtexVC );
	}

	public function testOver() {
		$input = "{a \over b }";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mfrac>", $mathMLtexVC );
	}

	public function testBigcup() {
		$input = "\bigcup_{i=_1}^n E_i";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "munderover", $mathMLtexVC );
	}

	public function testBigcap() {
		$input = "\bigcap_{i=_1}^n E_i";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "munderover", $mathMLtexVC );
	}

	public function testScriptAlignment() {
		$input = "\log_{10} f";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "<mo></mo>", $mathMLtexVC );
		$this->assertStringContainsString( "<msub>", $mathMLtexVC );
	}

	public function testNotOperatorname() {
		$input = "\\not\operatorname{R}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mpadded width=\"0\">", $mathMLtexVC );
		$this->assertStringContainsString( "<mtext>&#x29F8;</mtext>", $mathMLtexVC );
	}

	public function testUnder() {
		$input = "\\underbrace{ a+b+\\cdots+z }_{26}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<munder>", $mathMLtexVC );
		$this->assertStringNotContainsString( "<msub>", $mathMLtexVC );
	}

	public function testSumDisplaystyle() {
		$input = "\sum_{k=1}^N k^2";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mstyle displaystyle=\"true\" scriptlevel=\"0\">", $mathMLtexVC );
	}

	public function testSumTextstyle() {
		$input = "\\textstyle \sum_{k=1}^N k^2";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mstyle displaystyle=\"false\" scriptlevel=\"0\">", $mathMLtexVC );
	}

	public function testPilcrow() {
		$input = "\P";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#xB6;</mo>\n</math>", $mathMLtexVC );
	}

	public function testLesserThan() {
		$input = "<, \\nless,";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&lt;</mo>", $mathMLtexVC );
	}

	public function testSidesetError() {
		$input = "\\sideset{_1^2}{_3^4}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "merror", $mathMLtexVC );
	}

	public function testSidesetSum() {
		$input = "\\sideset{_1^2}{_3^4}\\sum";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "merror", $mathMLtexVC );
		$this->assertStringContainsString( "mmultiscripts", $mathMLtexVC );
		$this->assertStringContainsString( "<mprescripts/>", $mathMLtexVC );
		$this->assertStringContainsString( ">&#x2211;", $mathMLtexVC );
	}

	public function testSidesetProd() {
		$input = "\\sideset{_1^2}{_3^4}\\prod";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "merror", $mathMLtexVC );
		$this->assertStringContainsString( "mmultiscripts", $mathMLtexVC );
		$this->assertStringContainsString( "<mprescripts/>", $mathMLtexVC );
		$this->assertStringContainsString( ">&#x220F;", $mathMLtexVC );
	}

	public function testSidesetFQ() {
		$input = "\\sideset{_1^2}{_3^4}\prod_a^b";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "merror", $mathMLtexVC );
		$this->assertStringContainsString( "mmultiscripts", $mathMLtexVC );
		$this->assertStringContainsString( "<mprescripts/>", $mathMLtexVC );
		$this->assertStringContainsString( ">&#x220F;", $mathMLtexVC );
		$this->assertStringContainsString( "<mi>a</mi>", $mathMLtexVC );
		$this->assertStringContainsString( "<mi>b</mi>", $mathMLtexVC );
		$this->assertStringContainsString( "movablelimits", $mathMLtexVC );
	}

	public function testLimitsProd() {
		$input = "\\prod\\limits_{j=1}^k A_{\\alpha_j}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "munderover", $mathMLtexVC );
		$this->assertStringContainsString( "&#x220F;", $mathMLtexVC, );
		$this->assertStringContainsString( "msub", $mathMLtexVC, );
	}

	public function testLimitsSum() {
		$input = "\\sum\\limits_{j=1}^k A_{\\alpha_j}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "munderover", $mathMLtexVC );
		$this->assertStringContainsString( "&#x2211;", $mathMLtexVC, );
		$this->assertStringContainsString( "msub", $mathMLtexVC, );
	}

	public function testLimitsLim() {
		$input = "\\lim_{x \\to 2}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "lim", $mathMLtexVC );
		$this->assertStringContainsString( "munder", $mathMLtexVC, );
	}

	public function testRenderSpaceSemicolon() {
		$input = "{\\;}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "mspace", $mathMLtexVC, );
	}

	public function testSpacesAndCommas() {
		$input = "{a}{b , c}\\,";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>,", $mathMLtexVC );
		$this->assertStringContainsString( "mspace", $mathMLtexVC, );
	}

	public function testPrecedingSubscriptsFQ() {
		$input = "{}_1^2\\!\\Omega_3^4";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mrow " . Tag::CLASSTAG . "=\"ORD\"/>", $mathMLtexVC );
		$this->assertStringContainsString( "msubsup", $mathMLtexVC, );
	}

	public function testPrecedingSubscriptsDQ() {
		$input = "{}_pF_q";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mrow " . Tag::CLASSTAG . "=\"ORD\"/>", $mathMLtexVC );
		$this->assertStringContainsString( "msub", $mathMLtexVC, );
	}

	public function testPilcrowAndSectionSign() {
		$input = "\\P P \\S S";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mi>P", $mathMLtexVC );
		$this->assertStringContainsString( "<mi>S", $mathMLtexVC, );
		$this->assertStringContainsString( "&#xB6;", $mathMLtexVC, );
		$this->assertStringContainsString( "&#xA7;", $mathMLtexVC, );
	}

	public function testDerivatives1() {
		$input = "b_{f''}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2033;", $mathMLtexVC );
		$this->assertStringContainsString( "msup", $mathMLtexVC, );
	}

	public function testDerivatives2() {
		$input = "f''''(x)";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2057;", $mathMLtexVC );
		$this->assertStringContainsString( "msup", $mathMLtexVC, );
	}

	public function testLimitsTextstyle() {
		$input = "\\textstyle \\lim_{n \\to \\infty}x_n";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "lim", $mathMLtexVC );
		$this->assertStringContainsString( "munder", $mathMLtexVC, );
		$this->assertStringContainsString( "movablelimits=\"true\"", $mathMLtexVC );
	}

	public function testColorGeneration1() {
		$input = "\\color{Dandelion}{Dandelion}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "#FDBC42", $mathMLtexVC );
	}

	public function testColorGeneration2() {
		$input = "\\color{ForestGreen}{ForestGreen}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "#009B55", $mathMLtexVC );
	}

	public function testColorGeneration3() {
		$input = "\\color{Rhodamine}{Rhodamine}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "#EF559F", $mathMLtexVC );
	}

	public function testStyle1() {
		$input = "\\displaystyle \{U(\omega )\cdot \sigma _{H}(\omega )\} z";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mstyle displaystyle=\"true\" scriptlevel=\"0\">", $mathMLtexVC );
		$this->assertStringContainsString( "<mi>z</mi>", $mathMLtexVC );
		$this->assertStringContainsString( "</mstyle>", $mathMLtexVC, );
	}

	public function testStyle2() {
		$input = "\\displaystyle abc \\textstyle def";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mstyle displaystyle=\"false\" scriptlevel=\"0\">", $mathMLtexVC );
		$this->assertStringContainsString( "</mstyle>", $mathMLtexVC, );
	}

	public function testStyle3() {
		$input = "\\scriptstyle{abc} def \\textstyle ghi";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mstyle displaystyle=\"false\" scriptlevel=\"1\">", $mathMLtexVC );
		$this->assertStringContainsString( "</mstyle>", $mathMLtexVC, );
	}

	public function testStyle4() {
		$input = "\\textstyle b > \\textstyle \\delta";
		$mathMLtexVC = $this->generateMML( $input );
		$count = 0;
		str_replace( "<mstyle displaystyle", "", $mathMLtexVC, $count );
		$this->assertEquals( 2, $count );
	}

	public function testSpaceText() {
		$input = "\\text{if}~n\ \\text{is even} ";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mspace", $mathMLtexVC );
		$this->assertStringNotContainsString( "&#xA0;", $mathMLtexVC );
	}

	public function testSpaceOther() {
		// It is expected to render CR as whitespace
		$input = "\,e_{x}=\sum _{t=1}^{\infty }\ _{t}p_{x}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mspace", $mathMLtexVC );
	}

	public function testGenfracDQ() {
		$input = "\\binom{m}{k - j}_{\!\!q}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mi>q</mi>", $mathMLtexVC );
		$this->assertStringContainsString( "msub", $mathMLtexVC );
	}

	public function testDQZeroArgs() {
		$input = "nF^{_{}}/RT";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "msup", $mathMLtexVC );
	}

	public function testMatrixDQ() {
		$input = "\\begin{pmatrix} S \\\\Se\\\\Te\\end{pmatrix}_2";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "mtable", $mathMLtexVC );
		$this->assertStringContainsString( "msub", $mathMLtexVC );
		$this->assertStringContainsString( "<mn>2</mn>", $mathMLtexVC );
	}

	public function testAttributeDuplicate() {
		// This checks that there is some non-erronous output caused by double class attributes
		$input  = "\\mu_{\\operatorname{\\inf}}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "&#x3BC;", $mathMLtexVC );
	}

	private function generateMML( $input, $chem = false ) {
		$texVC = new TexVC();
		$resultT = $texVC->check( $input, [
			'debug' => false,
			'usemathrm' => false,
			'oldtexvc' => false,
			'usemhchem' => $chem
		] );

		return MMLTestUtil::getMMLwrapped( $resultT["input"] ) ?? "<math> error texvc </math>";
	}
}
