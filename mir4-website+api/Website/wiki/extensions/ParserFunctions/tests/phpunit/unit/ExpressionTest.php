<?php

use MediaWiki\Extension\ParserFunctions\ExprError;
use MediaWiki\Extension\ParserFunctions\ExprParser;

/**
 * @group ParserFunctions
 * @covers \MediaWiki\Extension\ParserFunctions\ExprParser
 */
class ExpressionTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideExpressions
	 */
	public function testExpression( $input, $expected ) {
		$parser = new ExprParser();
		$this->assertEquals(
			$expected,
			$parser->doExpression( $input )
		);
	}

	public static function provideExpressions() {
		return [
			[ '1 or 0', '1' ],
			[ 'not (1 and 0)', '1' ],
			[ 'not 0', '1' ],
			[ '4 < 5', '1' ],
			[ '-5 < 2', '1' ],
			[ '-2 <= -2', '1' ],
			[ '4 > 3', '1' ],
			[ '4 > -3', '1' ],
			[ '5 >= 2', '1' ],
			[ '2 >= 2', '1' ],
			[ '1 != 2', '1' ],
			[ '-4 * -4 = 4 * 4', '1' ],
			[ 'not (1 != 1)', '1' ],
			[ '1 + 1', '2' ],
			[ '-1 + 1', '0' ],
			[ '+1 + 1', '2' ],
			[ '4 * 4', '16' ],
			[ '(1/3) * 3', '1' ],
			[ '3 / 1.5', '2' ],
			[ '3 / 0.2', '15' ],
			[ '3 / ( 2.0 * 0.1 )', '15' ],
			[ '3 / ( 2.0 / 10 )', '15' ],
			[ '3 / (- 0.2 )', '-15' ],
			[ '3 / abs( 0.2 )', '15' ],
			[ '3 mod 2', '1' ],
			[ '1e4', '10000' ],
			[ '1e-2', '0.01' ],
			[ '4.0 round 0', '4' ],
			[ 'ceil 4', '4' ],
			[ 'floor 4', '4' ],
			[ '4.5 round 0', '5' ],
			[ '4.2 round 0', '4' ],
			[ '-4.2 round 0', '-4' ],
			[ '-4.5 round 0', '-5' ],
			[ '-2.0 round 0', '-2' ],
			[ 'ceil -3', '-3' ],
			[ 'floor -6.0', '-6' ],
			[ 'ceil 4.2', '5' ],
			[ 'ceil -4.5', '-4' ],
			[ 'floor -4.5', '-5' ],
			[ 'abs(-2)', '2' ],
			[ 'ln(exp(1))', '1' ],
			[ 'trunc(4.5)', '4' ],
			[ 'trunc(-4.5)', '-4' ],
			[ '123 fmod (2^64-1)', '123' ],
			[ '5.7 mod 1.3', '0' ],
			[ '5.7 fmod 1.3', '0.5' ],
			[ 'pi + 1', '4.1415926535898' ],
			[ 'sin(0)', '0' ],
			[ 'cos(0)', '1' ],
			[ 'tan(0)', '0' ],
			[ 'asin(0)', '0' ],
			[ 'acos(1)', '0' ],
			[ 'atan(0)', '0' ],
			[ 'sqrt(4)', '2' ],
		];
	}

	/**
	 * @dataProvider provideExpressionThrows
	 * @param string $input
	 */
	public function testExpressionThrows( $input ) {
		$parser = new ExprParser();
		$this->expectException( ExprError::class );
		$parser->doExpression( $input );
	}

	public static function provideExpressionThrows() {
		$longExpression = str_repeat( 'ln(', 1001 ) . '1' . str_repeat( ')', 1001 );
		return [
			'Expression too long' => [ $longExpression ],
			'Unexpected number' => [ '1 2' ],
			'Unrecognised word' => [ 'foo' ],
			'Unexpected number: pi' => [ '1 pi' ],
			'Unexpected operator' => [ '1 sin' ],
			'Unexpected operator: (' => [ '1 (' ],
			'Unexpected closing bracket' => [ '1 + 1)' ],
			'Unexpected punctuation' => [ '1, 2' ],
			'Unexpected binary operator' => [ '<1' ],
			'Unclosed bracket' => [ '(1' ],
			'Missing operand: unary -' => [ '-' ],
			'Missing operand: unary +' => [ '+' ],
			'Missing operand: *' => [ '1*' ],
			'Missing operand: /' => [ '1/' ],
			'Division by zero: /' => [ '1/0' ],
			'Missing operand: mod' => [ '1 mod' ],
			'Division by zero: mod' => [ '1 mod 0' ],
			'Missing operand: fmod' => [ '1 fmod' ],
			'Division by zero: fmod' => [ '1 fmod 0' ],
			'Missing operand: +' => [ '1+' ],
			'Missing operand: -' => [ '1-' ],
			'Missing operand: and' => [ '1 and' ],
			'Missing operand: or' => [ '1 or' ],
			'Missing operand: =' => [ '1 =' ],
			'Missing operand: not' => [ '1 not' ],
			'Missing operand: round' => [ '1 round' ],
			'Missing operand: <' => [ '1<' ],
			'Missing operand: >' => [ '1>' ],
			'Missing operand: <=' => [ '1<=' ],
			'Missing operand: >=' => [ '1>=' ],
			'Missing operand: <>' => [ '1<>' ],
			'Missing operand: sin' => [ 'sin()' ],
			'Missing operand: cos' => [ 'cos()' ],
			'Missing operand: tan' => [ 'tan()' ],
			'Missing operand: asin' => [ 'asin()' ],
			'Invalid operand: asin' => [ 'asin(3) ' ],
			'Missing operand: acos' => [ 'acos()' ],
			'Invalid operand: acos' => [ 'acos(-1.1)' ],
			'Missing operand: atan' => [ 'atan()' ],
			'Missing operand: exp' => [ 'exp()' ],
			'Missing operand: ln' => [ 'ln()' ],
			'Invalid operand: ln' => [ 'ln(-1)' ],
			'Missing operand: abs' => [ 'abs()' ],
			'Missing operand: floor' => [ 'floor' ],
			'Missing operand: trunc' => [ 'trunc' ],
			'Missing operand: ceil' => [ 'ceil' ],
			'Missing operand: ^' => [ '1 ^' ],
			// 'Invalid operand: ^' => [ '-1 ^ 5.5.5' ], // Not testable?
			'Missing operand: sqrt' => [ 'sqrt' ],
			'Result is not a number: sqrt' => [ 'sqrt(-1)' ],
		];
	}
}
