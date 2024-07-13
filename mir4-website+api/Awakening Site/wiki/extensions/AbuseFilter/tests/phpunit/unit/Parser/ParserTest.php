<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Parser;

use Generator;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;
use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use TestLogger;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 *
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPTreeParser
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPTreeNode
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPSyntaxTree
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPParserState
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPToken
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\Exception\ExceptionBase
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\SyntaxChecker
 */
class ParserTest extends ParserTestCase {
	/**
	 * @param string $rule The rule to parse
	 * @dataProvider readTests
	 */
	public function testParser( $rule ) {
		$this->assertTrue( $this->getParser()->parse( $rule ) );
	}

	public static function readTests() {
		$testPath = __DIR__ . "/../../../parserTests";
		$testFiles = glob( $testPath . "/*.t" );

		foreach ( $testFiles as $testFile ) {
			$testName = basename( substr( $testFile, 0, -2 ) );
			$rule = trim( file_get_contents( $testFile ) );

			yield $testName => [ $rule ];
		}
	}

	/**
	 * Test expression evaluation
	 *
	 * @param string $expr The expression to evaluate
	 * @param string $expected The expected result
	 * @dataProvider provideExpressions
	 */
	public function testEvaluateExpression( $expr, $expected ) {
		$actual = $this->getParser()->evaluateExpression( $expr );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Data provider for testEvaluateExpression
	 *
	 * @return array
	 */
	public static function provideExpressions() {
		return [
			[ '1 === 1', true ],
			[ 'rescape( "abc* (def)" )', 'abc\* \(def\)' ],
			[ 'str_replace( "foobarbaz", "bar", "-" )', 'foo-baz' ],
			[ 'str_replace_regexp( "foo1bar1baz", "\d", "" )', 'foobarbaz' ],
			[ 'str_replace_regexp( "foobarbaz", "(bar)", "$1baz" )', 'foobarbazbaz' ],
			[ 'rmdoubles( "foobybboo" )', 'fobybo' ],
			[ 'lcase("FÁmí")', 'fámí' ],
			[ 'substr( "foobar", 0, 3 )', 'foo' ],
		];
	}

	/**
	 * Test empty (or almost empty) syntax and ensure it doesn't match
	 *
	 * @param string $code
	 * @dataProvider provideEmptySyntax
	 */
	public function testEmptySyntax( $code ) {
		$this->assertFalse( $this->getParser()->parse( $code ) );
	}

	/**
	 * Data provider for testEmptySyntax
	 *
	 * @return array
	 */
	public static function provideEmptySyntax() {
		return [
			[ '' ],
			[ ';;;;' ]
		];
	}

	/**
	 * Test a filter only containing a pair of empty parentheses.
	 */
	public function testEmptyParenthesisOnly() {
		$code = '()';
		$this->expectException( UserVisibleException::class );
		$this->expectExceptionMessage( 'unexpectedtoken' );
		$this->getParser()->parse( $code );
	}

	/**
	 * Ensure that AbuseFilterTokenizer::OPERATOR_RE matches the contents
	 * and order of AbuseFilterTokenizer::$operators.
	 */
	public function testOperatorRe() {
		$quotedOps = array_map(
			static function ( $op ) {
				return preg_quote( $op, '/' );
			},
			AbuseFilterTokenizer::OPERATORS
		);
		$operatorRe = '/(' . implode( '|', $quotedOps ) . ')/A';
		$this->assertEquals( AbuseFilterTokenizer::OPERATOR_RE, $operatorRe );
	}

	/**
	 * Ensure the number of conditions counted for given expressions is right.
	 *
	 * @param string $rule The rule to parse
	 * @param int $expected The expected amount of used conditions
	 * @dataProvider condCountCases
	 */
	public function testCondCount( $rule, $expected ) {
		$this->assertEquals( $expected, $this->getParser()->checkConditions( $rule )->getCondsUsed(), "Rule: $rule" );
	}

	/**
	 * Data provider for testCondCount method.
	 * @return array
	 */
	public static function condCountCases() {
		return [
			[ '((("a" == "b")))', 1 ],
			[ 'contains_any("a", "b", "c")', 1 ],
			[ '"a" == "b" & "b" == "c"', 1 ],
			[ '"a" == "b" | "b" == "c"', 2 ],
			[ '"a" in "b" + "c" in "d" + "e" in "f"', 3 ],
			[ 'true', 0 ],
			[ '"a" == "a" | "c" == "d"', 1 ],
			[ '"a" == "b" & "c" == "d"', 1 ],
			[ '1 = 0 & 2 * 3 * 4 <= 560 & "a" = "b"', 1 ],
			[ '1 = 1 & 2 * 3 * 4 <= 560 & "a" = "b"', 3 ],
			[ '1 = 1 | 2 * 3 * 4 <= 560 | "a" = "b"', 1 ],
			[ '1 = 0 | 2 * 3 * 4 <= 560 | "a" = "b"', 2 ],
		];
	}

	/**
	 * Test for T204841
	 */
	public function testArrayShortcircuit() {
		$code = 'a := [false, false]; b := [false, false]; c := 42; d := [0,1];' .
			'a[0] != false & b[1] != false & (b[5**2/(5*(4+1))] !== a[43-c] | a[d[0]] === b[d[c-41]])';
		$this->assertFalse( $this->getParser()->parse( $code ) );
	}

	/**
	 * Test the 'expectednotfound' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider expectedNotFound
	 */
	public function testExpectedNotFoundException( $expr, $caller ) {
		$this->exceptionTest( 'expectednotfound', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'expectednotfound', $expr, $caller );
	}

	/**
	 * Data provider for testExpectedNotFoundException.
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function expectedNotFound() {
		return [
			[ 'a:= [1,2,3]; a[1 = 4', 'doLevelSet' ],
			[ "if 1 = 1 'foo'", 'doLevelConditions' ],
			[ "if 1 = 1 then 'foo'", 'doLevelConditions' ],
			[ "if 1 = 1 then 'foo' else 'bar'", 'doLevelConditions' ],
			[ "a := 1 = 1 ? 'foo'", 'doLevelConditions' ],
			[ '(1 = 1', 'doLevelBraces' ],
			[ 'lcase( 3 = 1', 'doLevelFunction' ],
			[ 'a := [1,2', 'doLevelAtom' ],
			[ '1 = 1 | (1', 'skipOverBraces/doLevelParenthesis' ],
			[ 'a := [1,2,3]; 3 = a[5', 'doLevelArrayElements' ],
			[ 'if[3] := 1', 'doLevelConditions' ],
			[ "set( page_title + 'x' + ( page_namespace == 0 ? 'x' : 'y' )", '' ]
		];
	}

	/**
	 * Test the 'unexpectedatend' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider unexpectedAtEnd
	 */
	public function testUnexpectedAtEndException( $expr, $caller ) {
		$this->exceptionTest( 'unexpectedatend', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'unexpectedatend', $expr, $caller );
	}

	/**
	 * Data provider for testUnexpectedAtEndException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function unexpectedAtEnd() {
		return [
			[ "'a' = 1 )", 'doLevelEntry' ],
		];
	}

	/**
	 * Test the 'unrecognisedvar' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider unrecognisedVar
	 */
	public function testUnrecognisedVarException( $expr, $caller ) {
		$this->exceptionTest( 'unrecognisedvar', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'unrecognisedvar', $expr, $caller );
	}

	/**
	 * Data provider for testUnrecognisedVarException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function unrecognisedVar() {
		return [
			[ 'a[1] := 5', 'getVarValue' ],
			[ 'a[] := 5', 'getVarValue' ],
			[ 'a = 5', 'getVarValue' ],
			[ 'timestamp[a]', 'getVarValue' ],
			[ 'x := []; x[a] := 1', 'getVarValue' ],
			[ 'a := [1]; a[b] := (b := 0); true', 'getVarValue' ],
		];
	}

	/**
	 * Test the 'notarray' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider notArray
	 */
	public function testNotArrayException( $expr, $caller ) {
		$this->exceptionTest( 'notarray', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'notarray', $expr, $caller );
	}

	/**
	 * Data provider for testNotArrayException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function notArray() {
		return [
			[ 'a := 5; a[1] = 5', 'doLevelSet' ],
			[ 'a := 1; 3 = a[5]', 'doLevelArrayElements' ],
			[ 'a := 2; a[] := 2', '[different callers]' ],
			[ 'a := 3; a[3] := 5', '[different callers]' ]
		];
	}

	/**
	 * Test the 'outofbounds' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider outOfBounds
	 */
	public function testOutOfBoundsException( $expr, $caller ) {
		$this->exceptionTest( 'outofbounds', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'outofbounds', $expr, $caller );
	}

	/**
	 * Data provider for testOutOfBoundsException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function outOfBounds() {
		return [
			[ 'a := [2]; a[5] = 9', 'doLevelSet' ],
			[ 'a := [1,2,3]; 3 = a[5]', 'doLevelArrayElements' ],
			[ 'a := [1]; a[15] := 5', '[different callers]' ]
		];
	}

	/**
	 * Test the 'negativeindex' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider negativeIndex
	 */
	public function testNegativeIndexException( $expr, $caller ) {
		$this->exceptionTest( 'negativeindex', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'negativeindex', $expr, $caller );
	}

	/**
	 * Data provider for testNegativeIndexException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function negativeIndex() {
		return [
			[ '[0][-1]', '' ],
			[ "x := ['foo']; x[-1]", '' ],
			[ "x := ['foo']; x[-1] := 2; x[-1] == 2", '' ],
			[ "x := ['foo']; x[-5] := 2;", '' ]
		];
	}

	/**
	 * Test the 'unrecognisedkeyword' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider unrecognisedKeyword
	 */
	public function testUnrecognisedKeywordException( $expr, $caller ) {
		$this->exceptionTest( 'unrecognisedkeyword', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'unrecognisedkeyword', $expr, $caller );
	}

	/**
	 * Data provider for testUnrecognisedKeywordException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function unrecognisedKeyword() {
		return [
			[ '5 = rlike', 'doLevelAtom' ],
			[ 'then := 45', 'doLevelAtom' ],
		];
	}

	/**
	 * Test the 'unexpectedtoken' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider unexpectedToken
	 */
	public function testUnexpectedTokenException( $expr, $caller ) {
		$this->exceptionTest( 'unexpectedtoken', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'unexpectedtoken', $expr, $caller );
	}

	/**
	 * Data provider for testUnexpectedTokenException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function unexpectedToken() {
		return [
			[ '1 =? 1', 'doLevelAtom' ],
		];
	}

	/**
	 * Test the 'disabledvar' exception
	 *
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider disabledVar
	 */
	public function testDisabledVarException( $expr, $caller ) {
		$this->exceptionTest( 'disabledvar', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'disabledvar', $expr, $caller );
	}

	/**
	 * Data provider for testDisabledVarException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function disabledVar() {
		return [
			[ 'old_text = 1', 'getVarValue' ],
		];
	}

	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider variableVariable
	 */
	public function testVariableVariableException( $expr, $caller ) {
		$this->exceptionTest( 'variablevariable', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'variablevariable', $expr, $caller );
	}

	/**
	 * Data provider for testVariableVariableException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function variableVariable() {
		return [
			[ "set( 'x' + 'y', 1 )", 'doLevelFunction' ],
			[ "set( 'x' + page_title, 1 )", 'doLevelFunction' ],
			[ "set( page_title, 1 )", 'doLevelFunction' ],
			[ "set( page_title + 'x' + ( page_namespace == 0 ? 'x' : 'y' ), 1 )", 'doLevelFunction' ],
		];
	}

	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider overrideBuiltin
	 */
	public function testOverrideBuiltinException( $expr, $caller ) {
		$this->exceptionTest( 'overridebuiltin', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'overridebuiltin', $expr, $caller );
	}

	/**
	 * Data provider for testOverrideBuiltinException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function overrideBuiltin() {
		return [
			[ 'added_lines := 1', 'setUserVariable' ],
			[ 'added_lines[] := 1', 'doLevelSet' ],
			[ 'added_lines[3] := 1', 'doLevelSet' ],
			[ 'page_id[3] := 1', 'doLevelSet' ],
			[ 'true | (added_lines := 1);', 'setUserVariable' ],
			[ 'if(true) then 1 else (added_lines := 1) end;', 'setUserVariable' ],
			[ 'length := 45', 'setUserVariable' ],
			[ 'set("added_lines", 45)', 'setUserVariable' ],
			[ 'set("length", 45)', 'setUserVariable' ],
			[ 'set("true", true)', 'setUserVariable' ],
		];
	}

	/**
	 * Test the 'usebuiltin' exception
	 *
	 * @param string $expr The expression to test
	 * @dataProvider useBuiltin
	 */
	public function testUseBuiltinException( $expr ) {
		$this->exceptionTest( 'usebuiltin', $expr );
		$this->exceptionTestInSkippedBlock( 'usebuiltin', $expr );
	}

	/**
	 * Data provider for testUseBuiltinException
	 * @return array
	 */
	public static function useBuiltin() {
		return [
			[ 'contains_any[1] := "foo"' ],
			[ '1 + lcase + 2' ]
		];
	}

	/**
	 * Test a filter only containing a function application of
	 * an unknown function.
	 */
	public function testUnknownFunction() {
		$expr = "f(1)";
		$this->exceptionTest( 'unknownfunction', $expr );
		$this->exceptionTestInSkippedBlock( 'unknownfunction', $expr );
	}

	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider regexFailure
	 */
	public function testRegexFailureException( $expr, $caller ) {
		$this->exceptionTest( 'regexfailure', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'regexfailure', $expr, $caller );
	}

	/**
	 * Data provider for testRegexFailureException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function regexFailure() {
		return [
			[ "rcount('(','a')", 'funcRCount' ],
			[ "get_matches('this (should fail', 'any haystack')", 'funcGetMatches' ],
			[ "'a' rlike '('", 'keywordRegex' ],
			[ "str_replace_regexp('any string', 'a bad (regex', 'any replacement')", 'funcStrReplaceRegexp' ],
		];
	}

	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @dataProvider invalidIPRange
	 */
	public function testInvalidIPRangeException( $expr, $caller ) {
		$this->exceptionTest( 'invalidiprange', $expr, $caller );
		$this->exceptionTestInSkippedBlock( 'invalidiprange', $expr, $caller );
	}

	/**
	 * Data provider for testInvalidIPRangeException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public static function invalidIPRange() {
		return [
			[ "ip_in_range('0.0.0.0', 'lol')", 'funcIPInRange' ],
			[ "ip_in_ranges('0.0.0.0', ':', '0.0.0.256')", 'funcIPInRanges' ],
			[ "ip_in_ranges('1.1.1.1', '1.1.0.0/16', 'gibberish')", 'funcIPInRanges' ],
		];
	}

	/**
	 * Test functions which take exactly one parameters calling them
	 *   without 0 params. They should throw a 'noparams' exception.
	 *
	 * @param string $func The function to test
	 * @dataProvider oneParamFuncs
	 */
	public function testNoParamsException( $func ) {
		$this->exceptionTest( 'noparams', "$func()", 'checkArgCount' );
		$this->exceptionTestInSkippedBlock( 'noparams', "$func()", 'checkArgCount' );
	}

	/**
	 * Data provider for testNoParamsException, returns a list of
	 * functions taking a single parameter
	 *
	 * @return array
	 */
	public static function oneParamFuncs() {
		return [
			[ 'lcase' ],
			[ 'ucase' ],
			[ 'length' ],
			[ 'strlen' ],
			[ 'specialratio' ],
			[ 'count' ],
			[ 'rcount' ],
			[ 'ccnorm' ],
			[ 'sanitize' ],
			[ 'rmspecials' ],
			[ 'rmwhitespace' ],
			[ 'rmdoubles' ],
			[ 'norm' ],
			[ 'rescape' ],
			[ 'string' ],
			[ 'int' ],
			[ 'float' ],
			[ 'bool' ],
		];
	}

	/**
	 * Test functions taking two parameters by providing only one.
	 *   They should throw a 'notenoughargs' exception.
	 *
	 * @param string $func The function to test
	 * @dataProvider twoParamsFuncs
	 */
	public function testNotEnoughArgsExceptionTwo( $func ) {
		// Nevermind if the argument can't be string since we check the amount
		// of parameters before anything else.
		$code = "$func('foo')";
		$this->exceptionTest( 'notenoughargs', $code, 'checkArgCount' );
		$this->exceptionTestInSkippedBlock( 'notenoughargs', $code, 'checkArgCount' );
	}

	/**
	 * Data provider for testNotEnoughArgsExceptionTwo, returns the list of
	 * functions taking two parameters.
	 *
	 * @return array
	 */
	public static function twoParamsFuncs() {
		return [
			[ 'get_matches' ],
			[ 'ip_in_range' ],
			[ 'ip_in_ranges' ],
			[ 'contains_any' ],
			[ 'contains_all' ],
			[ 'ccnorm_contains_any' ],
			[ 'ccnorm_contains_all' ],
			[ 'equals_to_any' ],
			[ 'substr' ],
			[ 'strpos' ],
			[ 'set_var' ],
		];
	}

	/**
	 * Test functions taking three parameters by providing only two.
	 *   They should throw a 'notenoughargs' exception.
	 *
	 * @param string $func The function to test
	 * @dataProvider threeParamsFuncs
	 */
	public function testNotEnoughArgsExceptionThree( $func ) {
		// Nevermind if the argument can't be string since we check the amount
		// of parameters before anything else.
		$code = "$func('foo', 'bar')";
		$this->exceptionTest( 'notenoughargs', $code, 'checkArgCount' );
		$this->exceptionTestInSkippedBlock( 'notenoughargs', $code, 'checkArgCount' );
	}

	/**
	 * Data provider for testNotEnoughArgsExceptionThree, returns the list of
	 * functions taking three parameters.
	 *
	 * @return array
	 */
	public static function threeParamsFuncs() {
		return [
			[ 'str_replace' ],
			[ 'str_replace_regexp' ],
		];
	}

	/**
	 * @param string $code
	 * @dataProvider tooManyArgsFuncs
	 */
	public function testTooManyArgumentsException( $code ) {
		$this->exceptionTest( 'toomanyargs', $code, 'checkArgCount' );
		$this->exceptionTestInSkippedBlock( 'toomanyargs', $code, 'checkArgCount' );
	}

	/**
	 * @return array
	 */
	public static function tooManyArgsFuncs() {
		return [
			[ "lcase( 'a', 'b' )" ],
			[ "norm( 'a', 'b', 'c' )" ],
			[ "count( 'a', 'b', 'c' )" ],
			[ "ip_in_range( 'a', 'b', 'c' )" ],
			[ "substr( 'a', 'b', 'c', 'd' )" ],
			[ "str_replace( 'a', 'b', 'c', 'd', 'e' )" ],
			[ "str_replace_regexp( 'a', 'b', 'c', 'd', 'e' )" ],
		];
	}

	/**
	 * @param string $func
	 * @dataProvider variadicFuncs
	 */
	public function testVariadicFuncsArbitraryArgsAllowed( $func ) {
		$argsList = str_repeat( ', "arg"', 50 );
		$code = "$func( 'arg' $argsList )";
		// Expect no exception
		$this->getParser()->parse( $code );
		$this->assertTrue( true );
	}

	/**
	 * @return array
	 */
	public static function variadicFuncs() {
		return [
			[ 'contains_any' ],
			[ 'contains_all' ],
			[ 'equals_to_any' ],
			[ 'ccnorm_contains_any' ],
			[ 'ccnorm_contains_all' ],
		];
	}

	/**
	 * Check that calling a function with less arguments than required throws an exception
	 * when inside a skipped conditional branch.
	 *
	 * @param string $funcCode Code for a function call
	 * @param string $exceptionCode The ID of the expected exception
	 * @dataProvider provideFuncsForConditional
	 */
	public function testCheckArgCountInConditional( $funcCode, $exceptionCode ) {
		$code = "if ( 1==1 ) then ( 1 ) else ( $funcCode ) end;";
		// AbuseFilterParser skips the parentheses altogether, so this is not supposed to work
		$parser = $this->getParser();
		$parser->toggleConditionLimit( false );
		try {
			$parser->parse( $code );
			$this->fail( 'No exception was thrown.' );
		} catch ( UserVisibleException $e ) {
			$this->assertSame( $exceptionCode, $e->mExceptionID );
		}
	}

	/**
	 * Data provider for testCheckArgCountInConditional
	 * @return array
	 */
	public static function provideFuncsForConditional() {
		return [
			[ 'count()', 'noparams' ],
			[ 'bool()', 'noparams' ],
			[ 'ip_in_range(1)', 'notenoughargs' ],
			[ 'set_var("x")', 'notenoughargs' ],
			[ 'str_replace("x","y")', 'notenoughargs' ],
			[ 'str_replace_regexp("x","y")', 'notenoughargs' ],
		];
	}

	/**
	 * Check that deprecated variables are correctly translated to the new ones with a debug notice
	 *
	 * @param string $old The old name of the variable
	 * @param string $new The new name of the variable
	 * @dataProvider provideDeprecatedVars
	 *
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator::getVarValue
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPTreeParser::checkLogDeprecatedVar
	 */
	public function testDeprecatedVars( $old, $new ) {
		// Set it under the new name, and check that the old name points to it
		$vars = VariableHolder::newFromArray( [ $new => 'value' ] );

		$loggerMock = new TestLogger();
		$loggerMock->setCollect( true );
		$parser = $this->getParser( $loggerMock );

		$parser->setVariables( $vars );
		$actual = $parser->parse( "$old === $new" );

		$loggerBuffer = $loggerMock->getBuffer();
		// Check that the use has been logged
		$found = false;
		foreach ( $loggerBuffer as $entry ) {
			$check = preg_match( '/^Deprecated variable/', $entry[1] );
			if ( $check ) {
				$found = true;
				break;
			}
		}
		if ( !$found ) {
			$this->fail( "The use of the deprecated variable $old was not logged." );
		}

		$this->assertTrue( $actual );
	}

	public static function provideDeprecatedVars() {
		foreach ( TestingAccessWrapper::constant( KeywordsManager::class, 'DEPRECATED_VARS' ) as $old => $new ) {
			yield $old => [ $old, $new ];
		}
	}

	/**
	 * Ensure that things like `'a' === 'b' === 'c'` or `1 < 2 < 3` are rejected, while `1 < 2 == 3`
	 * and `1 == 2 < 3` are not. (T218906)
	 * @param string $code Code to parse
	 * @param bool $valid Whether $code is valid (or should throw an exception)
	 * @dataProvider provideConsecutiveComparisons
	 */
	public function testDisallowConsecutiveComparisons( $code, $valid ) {
		$parser = $this->getParser();
		$actuallyValid = true;
		try {
			$parser->parse( $code );
		} catch ( UserVisibleException $e ) {
			$actuallyValid = false;
		}

		$this->assertSame( $valid, $actuallyValid );
	}

	/**
	 * Data provider for testDisallowConsecutiveComparisons
	 *
	 * @return Generator
	 */
	public static function provideConsecutiveComparisons() {
		$eqOps = [ '==', '===', '!=', '!==', '=' ];
		$ordOps = [ '<', '>', '<=', '>=' ];
		$ops = array_merge( $eqOps, $ordOps );
		foreach ( $ops as $op1 ) {
			foreach ( $ops as $op2 ) {
				$testStr = "1 $op1 3.14 $op2 -1";
				$valid = ( in_array( $op1, $eqOps ) && in_array( $op2, $ordOps ) ) ||
					( in_array( $op1, $ordOps ) && in_array( $op2, $eqOps ) );
				yield $testStr => [ $testStr, $valid ];
			}
		}
		// Some more cases with more than 2 comparisons
		$extra = [
			'1 === 1 < 3 === 0',
			'1 === 1 < 3 === 0 < 555',
			'1 < 3 === 0 < 555',
			'1 < 3 === 0 < 555 !== 444',
			'1 != 0 < 3 == 1 > 0 != 0'
		];
		foreach ( $extra as $case ) {
			yield $case => [ $case, false ];
		}
	}

	/**
	 * Test for mutating arrays under a skipped branch.
	 *
	 * @param string $code Code to parse
	 * @dataProvider provideArrayAssignmentShortCircuit
	 */
	public function testArrayAssignmentShortCircuit( string $code ) {
		$this->assertTrue( $this->getParser()->parse( $code ) );
	}

	/**
	 * Data provider for testArrayAssignmentShortCircuit
	 *
	 * @return array
	 */
	public static function provideArrayAssignmentShortCircuit() {
		return [
			[ 'a := [true]; false & (a[] := 2); a[0]' ],
			[ 'a := [true]; false & (a[1] := 2); a[0]' ],
		];
	}

	/**
	 * Test that code declaring a variable in a skipped brace (because of shortcircuit)
	 * will be parsed without throwing an exception when later trying to use that var. T214674
	 *
	 * @param string $code Code to parse
	 * @dataProvider provideVarDeclarationInSkippedBlock
	 */
	public function testVarDeclarationInSkippedBlock( $code ) {
		$this->assertFalse( $this->getParser()->parse( $code ) );
	}

	/**
	 * Data provider for testVarDeclarationInSkippedBlock
	 * @return array
	 */
	public static function provideVarDeclarationInSkippedBlock() {
		return [
			[ "x := [5]; false & (1 == 1; y := 'b'; x[1] := 'x'; 3 < 4); y != 'b' & x[1] != 'x'" ],
			[ "false & (set('myvar', 1)); myvar contains 1" ],
			[ "false & ( ( false & ( var := [1] ) ) | ( var[] := 2 ) ); var" ],
			[ "false & ( ( false & ( var := [1] ); true ) | ( var[] := 2 ) ); var" ],
			// The following tests are to ensure that we don't get a match
			[ "false & ( var := 'foo'; x := get_matches( var, added_lines )[1] ); x != false" ],
			[ "false & ( var := 'foo'); var !== null" ],
			[ "false & ( var := 'foo'); var === null" ],
			[ "false & (set('myvar', 'foo')); myvar === 'foo' | myvar !== 'foo'" ],
			[ "false & ( var := 'foo'); var[0] !== 123456" ],
			[ "false & ( var := 'foo'); var[0][123] !== 123456" ],
			[ "false & (set('myvar', 'foo')); myvar[1][2] === 'foo' | myvar[1][2] !== 'foo'" ],
			// Identifier before closing skipped brace, T214674#5374757
			[ "false & ( var := 'foo'; 'x' in var )" ],
			[ "false & ( var := 'foo'; added_lines irlike var )" ],
			[ "false & ( if ( 1 == 1 ) then (var := 3) else (var := 4) end;); var !== 'foo'" ],
			[ "if ( 1 === 1 ) then ( 0 ) else ( var := 1 ) end; var !== 'foo'" ],
			[ "if ( 1=== 1 ) then (0) else ( false & ( var := 1 ) ) end; var !== 'foo'" ],
		];
	}

	/**
	 * Tests for the AFPData::DUNDEFINED type. No exceptions should be thrown.
	 *
	 * @param string $code To be parsed
	 * @dataProvider provideDUNDEFINED
	 */
	public function testDUNDEFINED( $code ) {
		// Note that some of the data sets will actually match at runtime, even if the variable
		// they refer to is not set, due to the parser using GET_BC rather than GET_STRICT.
		// TODO: Once T230256 is done, this can be changed to $this->assertFalse( $this->getParser()->parse( $code ) )
		$this->assertTrue( $this->getParser()->checkSyntax( $code )->isValid() );
	}

	/**
	 * Data provider for testDUNDEFINED. These bits of code must NOT match
	 *
	 * @return array
	 */
	public static function provideDUNDEFINED() {
		return [
			[ "5 / length( new_wikitext ) !== 3 ** edit_delta & " .
				"float( timestamp / (user_age + 0.000001) ) !== 0.0" ],
			[ "amount := float( timestamp / user_age); amount !== 0.0 & 64 / ( amount - 0.1 ) !== -640.0" ],
			[ "36 / ( length( user_rights ) + 0.00001 ) !== 0" ],
			[ "!('something' in added_lines)" ],
			[ "!(user_groups rlike 'foo')" ],
			[ "rcount('x', rescape(page_title) ) !== 0" ],
			[ "norm(user_name) !== rmspecials('')" ],
			[ "-user_editcount !== 1234567890" ],
			[ "added_lines" ],
			[ "removed_lines[0] !== 123456" ],
			[ "-new_size" ],
			[ "new_wikitext !== null" ],
			[ "true & user_editcount" ],
			[ "var:= 5; added_lines contains var" ],
			[ "false & (var := [ 1,2,3 ]); var === [ 1,2,3 ]" ],
			[ "page_age - user_editcount !== 1234567 - page_namespace" ],
			[ "added_lines contains 'foo' ? 'foo' : false" ],
			[ "timestamp / 12345 !== 'foobar'" ],
			// Refuse to modify a DUNDEFINED offset as if it were an array
			[ "false & (var := [ 1,2,3 ]); var[0] := true; var[0] === true" ],
			[ "false & (var := [ 1,2,3 ]); var[] := 'baz'; 'baz' in var" ],
			// But allow overwriting the whole variable
			[ "false & (var := [ 1,2,3 ]); var := [4,5,6]; var !== [4,5,6]" ],
			// Recursive DUNDEFINED replacement, T250570
			[ '"x" in [ user_name ]' ],
			[ 'string(user_name) in [ user_name ]' ],
			[ 'string(user_name) in [ "x" ]' ],
			[ '[ [ user_name ] ] in [ [ user_name ] ]' ],
			[ 'equals_to_any( [ user_name ], [ user_name ] )' ],
			// DUNDEFINED offsets delete the whole array
			[ "x := [true]; x[timestamp] := 1; x == x" ],
			[ "x := [true]; x[timestamp * 0] !== 'foo'" ],
		];
	}

	/**
	 * Test accessing builtin variables as arrays. This is always allowed when checking syntax, even
	 * if the variable is not an array (e.g. new_wikitext), but should fail when parsing.
	 *
	 * @param string $code
	 * @dataProvider provideBuiltinArrays
	 */
	public function testBuiltinArrays( string $code ) {
		$this->assertTrue( $this->getParser()->checkSyntax( $code )->isValid() );
		$this->exceptionTest( 'notarray', $code, '' );
	}

	/**
	 * Data provider for testBuiltinArrays
	 * @return array
	 */
	public static function provideBuiltinArrays() {
		return [
			[ "removed_lines[1] == 2" ],
			[ "added_lines[0] contains 'x'" ],
			[ "new_wikitext[1] !== 'xxx'" ]
		];
	}

	/**
	 * Test that empty operands raise an exception
	 *
	 * @param string $code
	 * @dataProvider provideEmptyOperands
	 */
	public function testEmptyOperands( $code ) {
		$parser = $this->getParser();
		$this->expectException( UserVisibleException::class );
		$this->expectExceptionMessage( 'unexpectedtoken' );
		$parser->parse( $code );
	}

	/**
	 * @return array
	 */
	public static function provideEmptyOperands() {
		return [
			[ '(0 |)', 'bool operand' ],
			[ '(1 |)', 'bool operand' ],
			[ '(0 &)', 'bool operand' ],
			[ '(1 &)', 'bool operand' ],
			[ '1==', 'compare operand' ],
			[ '0<=', 'compare operand' ],
			[ '1+', 'sum operand' ],
			[ '0-', 'sum operand' ],
			[ '1*', 'multiplication operand' ],
			[ '1**', 'power operand' ],
			[ '"string" contains', 'keyword operand' ],
			[ '1 in', 'keyword operand' ],
			[ "strpos('a','b',)", 'non-variadic function argument' ],
			[ "count('a',)", 'non-variadic function argument' ],
			[ "(!)", 'bool inversion' ],
			// `(false &!)` and `(true &!)`, originally reported in T156096,
			// should be used in the future to test that they throw. However,
			// using them now would log twice and thus make the test fail.
			[ "var :=", 'var assignment' ],
			[ "var :=[];var[] :=", 'array assignment' ],
			[ "var :=[1];var[0] :=", 'array assignment' ],
			[ "false ? false :", 'ternary else' ],
			[ "true ? false :", 'ternary else' ],
			[ "-", 'unary operand' ],
			[ "+", 'unary operand' ],
			[ 'if () then (1) end', 'parenthesized expression' ],
			[ 'if () then (1) else (1) end', 'parenthesized expression' ],
			[ 'if (true) then () end', 'parenthesized expression' ],
			[ 'if (false) then () end', 'parenthesized expression' ],
			[ 'if (true) then () else (3) end', 'parenthesized expression' ],
			[ 'if (false) then () else (3) end', 'parenthesized expression' ],
			[ 'if (true) then (1) else () end', 'parenthesized expression' ],
			[ 'if (false) then (1) else () end', 'parenthesized expression' ],
			[ '()', 'parenthesized expression' ]
		];
	}

	/**
	 * Ensure that the both parsers won't throw for dangling commas in variadic functions
	 * @param string $code
	 * @dataProvider provideDanglingCommasInVariargs
	 */
	public function testDanglingCommasInVariargsAreValid( $code ) {
		// Expect no exception
		$this->getParser()->parse( $code );
		$this->assertTrue( true );
	}

	/**
	 * @return array
	 */
	public static function provideDanglingCommasInVariargs() {
		return [
			[ "contains_any('a','b','c',)" ],
			[ "contains_all(1,1,1,1,1,1,1,)" ],
			[ "equals_to_any(1,'foo',)" ],
		];
	}

	/**
	 * Ensure that an exception is thrown where there are extra commas in function calls, which
	 * are not the kind of allowed dangling commas.
	 *
	 * @param string $code
	 * @dataProvider provideExtraCommas
	 */
	public function testExtraCommasNotAllowed( $code ) {
		$this->exceptionTest( 'unexpectedtoken', $code, 'doLevelAtom' );
		$this->exceptionTestInSkippedBlock( 'unexpectedtoken', $code, 'doLevelAtom' );
	}

	/**
	 * @return array
	 */
	public static function provideExtraCommas() {
		return [
			[ "norm(,,,)" ],
			[ "str_replace(,'x','y')" ],
			[ "str_replace_regexp(,'x','y')" ],
			[ "contains_any(,)" ],
			[ "contains_any(,,)" ],
			[ "contains_any(1,2,,)" ],
			[ "contains_any(1,2,,3,)" ],
		];
	}

	/**
	 * Ensure that any error in the arguments to a keyword or function is reported when
	 * checking syntax (T234339)
	 * @param string $code
	 * @param string $expID Expected exception ID
	 * @dataProvider provideArgsErrorsInSyntaxCheck
	 */
	public function testArgsErrorsInSyntaxCheck( $code, $expID ) {
		$caller = '[unavailable]';
		$this->exceptionTest( $expID, $code, $caller );
		$this->exceptionTestInSkippedBlock( $expID, $code, $caller );
	}

	/**
	 * @return array
	 */
	public static function provideArgsErrorsInSyntaxCheck() {
		return [
			[ 'accountname rlike "("', 'regexfailure' ],
			[ 'contains_any( new_wikitext, "foo", 3/0 )', 'dividebyzero' ],
			[ 'contains_any( added_lines, [ user_name, [ 3/0 ] ] )', 'dividebyzero' ],
			[ 'rcount( "(", added_lines )', 'regexfailure' ],
			[ 'get_matches( "(", new_wikitext )', 'regexfailure' ],
			[ 'added_lines contains string(3/0)', 'dividebyzero' ],
			[ 'norm(new_text) irlike ")"', 'regexfailure' ],
			[ 'ip_in_range( user_name, "foobar" )', 'invalidiprange' ],
			[ 'ip_in_ranges( user_name, "foo", "bar" )', 'invalidiprange' ],
		];
	}

	/**
	 * Ensure that every function in FUNCTIONS is also listed in FUNC_ARG_COUNT
	 */
	public function testAllFunctionsHaveArgCount() {
		$funcs = array_keys( FilterEvaluator::FUNCTIONS );
		sort( $funcs );
		$argsCount = array_keys( FilterEvaluator::FUNC_ARG_COUNT );
		sort( $argsCount );
		$this->assertSame( $funcs, $argsCount );
	}

	/**
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator::toggleConditionLimit
	 */
	public function testToggleConditionLimit() {
		/** @var FilterEvaluator $wrapper */
		$parser = $this->getParser();
		$wrapper = TestingAccessWrapper::newFromObject( $parser );

		$parser->toggleConditionLimit( false );
		$this->assertFalse( $wrapper->condLimitEnabled );

		$parser->toggleConditionLimit( true );
		$this->assertTrue( $wrapper->condLimitEnabled );
	}

	/**
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator::setVariables
	 */
	public function testSetVariables() {
		$parser = $this->getParser();
		$vars = new VariableHolder();
		$parser->setVariables( $vars );
		/** @var FilterEvaluator $wrapper */
		$wrapper = TestingAccessWrapper::newFromObject( $parser );
		$this->assertSame( $vars, $wrapper->mVariables );
	}
}
