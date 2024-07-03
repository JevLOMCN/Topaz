<?php

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use MediaWikiUnitTestCase;

/**
 * Some simple tests to test specific functions within
 * MhchemParser in PHP.
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 */
final class MhchemBasicTest extends MediaWikiUnitTestCase {

	public function testZk() {
		// ReplaceFirst in MhchemTexify is introduced with this test, is this meant to be replace in javascript ?
		$input = "\ce{{Zk_{c} e^2 m_{e}}}";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{{\mathrm{Zk_{c}~e^2 m_{e}}}}", $out );
	}

	public function testTextPattern() {
		// With this test a fix for the a^2 pattern to ^\\\_ instead ^\\x was introduced
		$input = "K^\\text{b, 2}";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{\mathrm{K}{\\vphantom{A}}^{\\text{b, 2}}}", $out );
	}

	public function testFormulaDollarPattern() {
		$input = "\ce{H_{n + 2}}";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{\mathrm{H}{\\vphantom{A}}_{\smash[t]{n  + 2}}}", $out );
	}

	public function testPhantom() {
		$input = "\ce{-C-O-}";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{{-}\mathrm{C}{-}\mathrm{O}{\\vphantom{A}}^{-}}", $out );
	}

	public function testH20withPU() {
		$input = "H2O (\pu{1g})";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{\mathrm{H}{\\vphantom{A}}_{\smash[t]{2}}\mathrm{O}~({1~\mathrm{g}})}", $out );
	}

	public function testPUt1010() {
		$input = "10^-10 m";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "pu" );
		$this->assertEquals( "{10^{-10}~\mathrm{m}}", $out );
	}

	public function testPUtimes() {
		$input = "7.8 \\times 10^-10 m";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "pu" );
		$this->assertEquals( "{7.8\\times 10^{-10}~\mathrm{m}}", $out );
	}

	public function testPUE10() {
		$input = "E10";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "pu" );
		$this->assertEquals( "{10^{10}}", $out );
	}

	public function testXParenthesis() {
		$input = "\${x}$";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{x }", $out );
	}

	public function testH2OAmount() {
		$input = "\$n$/2 H2O";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals(
			"{\mathchoice{\\textstyle\\frac{n}{2}}{\\frac{n}{2}}{\\frac{n}{2}}{\\frac{n}{2}}\,\mathrm{H}" .
			"{\\vphantom{A}}_{\smash[t]{2}}\mathrm{O}}", $out );
	}

	public function testH1() {
		$input = "H^°";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{\mathrm{H}{\\vphantom{A}}^{°}}", $out );
	}

	public function testDollar1() {
		$input = "A $\pm$ B";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals( "{\mathrm{A} {}\pm{} \mathrm{B}}", $out );
	}

	public function testDollar2() {
		$input = "1/2\$n\$ H2O";
		$mhchemParser = new MhchemParser();
		$out = $mhchemParser->toTex( $input, "ce" );
		$this->assertEquals(
			"{\mathchoice{\\textstyle\\frac{1}{2}}{\\frac{1}{2}}{\\frac{1}{2}}" .
			"{\\frac{1}{2}}n \,\mathrm{H}{\\vphantom{A}}_{\smash[t]{2}}\\mathrm{O}}", $out );
	}

	public function testApattern() {
		$input = "^0_-1n-";
		$pattern = "/^\^([0-9]+|[^\\_])/";

		$matches = [];
		$match = preg_match( $pattern, $input, $matches );

		$mhchemPatterns = new MhchemPatterns();
		$matchesR = $mhchemPatterns->match( "^a", $input );

		$this->assertSame( 1, $match );
		$this->assertSame( "0", $matchesR["match_"] );
		$this->assertEquals( "_-1n-", $matchesR["remainder"] );
	}

	public function testLettersPattern() {
		$input = "mu-Cl";
		$pattern = '/^(?:[a-zA-Z\x{03B1}-\x{03C9}\x{0391}-\x{03A9}?@]|(?:\\\\(?:alpha|beta|gamma|delta|epsilon|zeta|' .
			'eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi|rho|sigma|tau|upsilon|phi|chi|psi|omega|Gamma' .
			'|Delta|Theta|Lambda|Xi|Pi|Sigma|Upsilon|Phi|Psi|Omega)(?:\s+|\{\}|(?![a-zA-Z]))))+/u';
		$matches = [];
		$match = preg_match( $pattern, $input, $matches );
		$this->assertTrue( $match == 1 );
	}

	public function testStateOfAggregationPattern() {
		$input = "(\\ca\$c\$)";
		$match = preg_match( '/^(?:\((?:\\\\ca\s?)?\$[amothc]\$\))/', $input );
		$this->assertTrue( $match == 1 );
	}

	public function testCelsiusPattern() {
		$input = "°C";
		$output = preg_replace( "/\x{00B0}C|\^oC|\^{o}C/u", "{}^{\\circ}C", $input );
		$target = "{}^{\circ}C";
		$this->assertEquals( $target, $output );
	}

	public function testPatternsMatchObsGroups() {
		$mhchemPatterns = new MhchemPatterns();
		$a = $mhchemPatterns->findObserveGroups( "(aq)", "",
			new MhchemRegExp( '/^\\([a-z]{1,3}(?=[\\),])/' ), ")", "" );
		$target = [
			"match_" => "(aq)",
			"remainder" => ""
		];
		$this->assertEquals( $target, $a );
	}

	public function testIssetJS() {
		$this->assertFalse( MhchemUtil::issetJS( "" ) );
		$this->assertFalse( MhchemUtil::issetJS( null ) );
		$this->assertFalse( MhchemUtil::issetJS( false ) );
		$this->assertFalse( MhchemUtil::issetJS( 0 ) );

		// checkEmpty(new Object("")); tbd
		$this->assertTrue( MhchemUtil::issetJS( new \stdClass( "" ) ) );
		$this->assertTrue( MhchemUtil::issetJS( "abc" ) );
		$this->assertTrue( MhchemUtil::issetJS( "false" ) );
		$this->assertTrue( MhchemUtil::issetJS( [] ) );
		$this->assertTrue( MhchemUtil::issetJS( "0" ) );
		$this->assertTrue( MhchemUtil::issetJS( 123 ) );

		// Also the function should not crash when checking nested non-existent properties but return false
		$test1 = [ "b" => 123 ];
		$this->assertFalse( MhchemUtil::issetJS( $test1["a"] ?? null ) );
		$this->assertTrue( MhchemUtil::issetJS( $test1["b"] ) );
	}

	public function testTransitionsInitTex() {
		$empty = [
			"pattern" => "empty",
			"task" => [
				"action_" => [
					[ "type_" => "copy" ]
				],
				"stateArray" => [
					"0"
				]
			],
		];

		$ce = [
			"pattern" => "\\ce{(...)}",
			"task" => [
				"action_" => [
					[ "type_" => "write", "option" => "{" ],
					[ "type_" => "ce" ],
					[ "type_" => "write", "option" => "}" ],
				],
				"stateArray" => [
					"0"
				]
			],
		];

		$mhchemParser = new MhchemParser();
		$mhchemStateMachines = new MhchemStateMachines( $mhchemParser );
		$transitions = $mhchemStateMachines->stateMachines["tex"]["transitions"];

		// When done eval(count($transition), NUMBER)
		$emptyGen = $transitions[0][0];
		$ceGen = $transitions[0][1];
		$this->assertEquals( $empty, $emptyGen );
		$this->assertEquals( $ce, $ceGen );
	}

	public function testTransitionsInitCe() {
		$mhchemParser = new MhchemParser();
		$mhchemStateMachines = new MhchemStateMachines( $mhchemParser );
		$transitions = $mhchemStateMachines->stateMachines["ce"]["transitions"];
		// When done eval(count($transition), NUMBER)
		$this->assertCount( 23, $transitions );
		$this->assertCount( 61, $transitions["0"] );
		$this->assertCount( 50, $transitions["qD"] );
	}

	public function testMHPatternsMatch() {
		$mhchemPatterns = new MhchemPatterns();
		$pattern  = "\ce{(...)}";
		$matches = $mhchemPatterns->match( $pattern, "\ce{CO2 + C -> 2 CO}" );
		$this->assertEquals( "CO2 + C -> 2 CO", $matches["match_"] );
		$this->assertSame( "", $matches["remainder"] );
	}

	public function testMHPatternsMatch2() {
		$mhchemPatterns = new MhchemPatterns();
		$matches = $mhchemPatterns->match( 'letters', "C" );
		$this->assertNotNull( $matches );
	}

	public function testMMHPatternsMatch3() {
		$mhchemPatterns = new MhchemPatterns();
		$matches2 = $mhchemPatterns->match( '->', "C" );
		$this->assertNull( $matches2 );
	}

	public function testMHChemParserGo1() {
		$target = [
			"type_" => "chemfive",
			"a" => [],
			"b" => [],
			"p" => [],
			"o" => [
				[
					"type_" => "rm",
					"p1" => "C"
				]
			],
			"q" => [],
			"d" => []
		];

		$mhchemParser = new MhchemParser();
		$output = $mhchemParser->go( "C", "ce" );

		$this->assertEquals( $target, $output[0] );
	}

	public function testMHChemParserGo2() {
		$target = [
			"type_" => "chemfive",
			"a" => [],
			"b" => [],
			"p" => [],
			"o" => [
				[
					"type_" => "rm",
					"p1" => "CO"
				]
			],
			"q" => [
				"2"
			],
			"d" => []
		];

		$mhchemParser = new MhchemParser();
		$output = $mhchemParser->go( "CO2", "ce" );

		$this->assertEquals( $target, $output[0] );
	}

	public function testMHChemParserGo3() {
		$target = [
			[
				"type_" => "arrow",
				"r" => "->",
				"rd" => [],
				"rq" => []
			]
		];

		$mhchemParser = new MhchemParser();
		$output = $mhchemParser->go( "->", "ce" );

		$this->assertEquals( $target, $output );
	}

	public function testMHChemParserGo4() {
		$target = [
			[
				"type_" => "chemfive",
				"a" => [],
				"b" => [],
				"p" => [],
				"o" => [
					"+"
				],
				"q" => [],
				"d" => []
			]
		];

		$mhchemParser = new MhchemParser();
		$output = $mhchemParser->go( "+", "ce" );

		$this->assertEquals( $target, $output );
	}

	public function testMHChemParserGo5() {
		// order of patterns correct, hit different pattern
		$mhchemParser = new MhchemParser();
		$output = $mhchemParser->go( "CO2 +  ", "ce" );
		$this->assertCount( 2, $output );
		$this->assertEquals( "chemfive", $output[0]["type_"] );
		$this->assertEquals( "operator", $output[1]["type_"] );
	}

	public function testMHChemParserGo6() {
		$mhchemParser = new MhchemParser();
		$output = $mhchemParser->go( "CO2 + C -> 2 CO", "ce" );
		$this->assertCount( 5, $output );
		$this->assertEquals( "chemfive", $output[0]["type_"] );
		$this->assertEquals( "operator", $output[1]["type_"] );
		$this->assertEquals( "chemfive", $output[2]["type_"] );
		$this->assertEquals( "arrow", $output[3]["type_"] );
		$this->assertEquals( 2, $output[4]["a"][0] );
		$this->assertEquals( "CO", $output[4]["o"][0]["p1"] );
	}

	public function testMHChemTexify() {
		$mhchemParser = new MhchemParser();
		$output = $mhchemParser->toTex( "CO2", "ce" );
		$this->assertEquals( "{\\mathrm{CO}{\\vphantom{A}}_{\\smash[t]{2}}}", $output );
	}

	public function testConcatArray1() {
		$a = [];
		$b = [ "{" ];
		$target = [ "{" ];
		MhchemUtil::concatArray( $a, $b );
		$this->assertEquals( $target, $a );
	}

	public function testConcatArray2() {
		$a = [];
		// This is an object in javascript typescript, in PHP this is currently an array.
		$b = [ "type_" => "rm", "p1" => "C" ];
		$target = [ $b ];
		MhchemUtil::concatArray( $a, $b );
		$this->assertEquals( $target, $a );
	}

	public function testConcatArray3() {
		$a = [ "{" ];
		$b = [ "type_" => "chemfive" ];

		$target = [ $a[0], $b ];
		MhchemUtil::concatArray( $a, $b );
		$this->assertEquals( $target, $a );
	}

}
