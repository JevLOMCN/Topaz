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
 */

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Parser;

use EmptyBagOStuff;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer;
use MediaWiki\Extension\AbuseFilter\Parser\AFPTreeParser;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;
use MediaWiki\Extension\AbuseFilter\Parser\SyntaxChecker;
use NullStatsdDataFactory;
use Psr\Log\NullLogger;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 *
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\SyntaxChecker
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPTreeParser
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPTreeNode
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPSyntaxTree
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPParserState
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPToken
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData
 */
class SyntaxCheckerTest extends \MediaWikiUnitTestCase {
	/**
	 * @param string $excep The expected exception or an empty string
	 * @param string $expr The expression to test
	 * @param string $mode The checking mode
	 * @param bool $checkUnusedVars Whether unused variables should error
	 */
	private function exceptionTest( string $excep, string $expr, string $mode, bool $checkUnusedVars ): void {
		$expectException = $excep !== '';
		$info = " mode=$mode checkUnused=$checkUnusedVars";

		$cache = new EmptyBagOStuff();
		$logger = new NullLogger();
		$statsd = new NullStatsdDataFactory();
		$keywordsManager = new KeywordsManager( $this->createMock( AbuseFilterHookRunner::class ) );

		$tokenizer = new AbuseFilterTokenizer( $cache );
		$tokens = $tokenizer->getTokens( $expr );
		$parser = new AFPTreeParser( $logger, $statsd, $keywordsManager );
		$tree = $parser->parse( $tokens );
		$checker = new SyntaxChecker( $tree, $keywordsManager, $mode, $checkUnusedVars );
		try {
			$checker->start();
		} catch ( UserVisibleException $e ) {
			if ( $expectException ) {
				$this->assertEquals( $excep, $e->mExceptionID, "Got wrong exception type: $info" );
				return;
			}
			$this->fail( "Unexpected exception $e thrown: $info" );
		}
		$this->assertFalse( $expectException, "Exception $excep not thrown: $info" );
	}

	/**
	 * Test the arity-related exception
	 *
	 * @param string $excep The expected exception or an empty string
	 * @param string $expr The expression to test
	 * @dataProvider provideArity
	 */
	public function testArity( string $excep, string $expr ): void {
		$this->exceptionTest( $excep, $expr, SyntaxChecker::MCONSERVATIVE, false );
	}

	/**
	 * Data provider for testArity
	 *
	 * @return array
	 */
	public static function provideArity(): array {
		return [
			[ 'toomanyargs', 'length(1, 2)' ],
			[ 'noparams', 'length()' ],
			[ 'notenoughargs', 'contains_any(1)' ],
			[ '', 'length(1)' ],
			[ '', 'contains_any(1, 2)' ],
			[ '', 'contains_any(1, 2, 3)' ],
		];
	}

	/**
	 * Test the function name related exception
	 *
	 * @param string $excep The expected exception or an empty string
	 * @param string $expr The expression to test
	 * @dataProvider provideFunctionName
	 */
	public function testFunctionName( string $excep, string $expr ): void {
		$this->exceptionTest( $excep, $expr, SyntaxChecker::MCONSERVATIVE, false );
	}

	/**
	 * Data provider for testFunctionName
	 *
	 * @return array
	 */
	public static function provideFunctionName(): array {
		return [
			[ 'unknownfunction', 'f(1)' ],
			[ 'unknownfunction', 'timestamp(1)' ],
			[ '', 'length(1)' ],
		];
	}

	/**
	 * Test the assignment related exception
	 *
	 * @param string $excep The expected exception or an empty string
	 * @param string $expr The expression to test
	 * @dataProvider provideAssign
	 */
	public function testAssign( string $excep, string $expr ): void {
		$this->exceptionTest( $excep, $expr, SyntaxChecker::MCONSERVATIVE, false );
	}

	/**
	 * Data provider for testAssign
	 *
	 * @return array
	 */
	public static function provideAssign(): array {
		// array assignments need to lookup first, so error will differ.
		return [
			[ 'overridebuiltin', 'timestamp := 1' ],
			[ 'overridebuiltin', 'length := 1' ],
			[ '', 'f := 1' ],

			// lookup is fine. assignment is not.
			[ 'overridebuiltin', 'timestamp[] := 1' ],

			// lookup is not fine.
			[ 'usebuiltin', 'length[] := 1' ],

			// lookup is not fine.
			[ 'unrecognisedvar', 'f[] := 1' ],

			// lookup is fine. assignment is not.
			[ 'overridebuiltin', 'timestamp[0] := 1' ],

			// lookup is not fine.
			[ 'usebuiltin', 'length[0] := 1' ],

			// lookup is not fine.
			[ 'unrecognisedvar', 'f[0] := 1' ],

			// static checker currently does not check index error
			[ '', 'f := 1; f[] := 1' ],
			[ '', 'f := 1; f[0] := 1' ],
		];
	}

	/**
	 * Test the lookup related exception
	 *
	 * @param string $excep The expected exception or an empty string
	 * @param string $expr The expression to test
	 * @dataProvider provideLookup
	 */
	public function testLookup( string $excep, string $expr ): void {
		$this->exceptionTest( $excep, $expr, SyntaxChecker::MCONSERVATIVE, false );
	}

	/**
	 * Data provider for testLookup
	 *
	 * @return array
	 */
	public static function provideLookup(): array {
		return [
			[ '', 'timestamp' ],
			[ 'usebuiltin', 'length' ],
			[ 'unrecognisedvar', 'f' ],
			[ '', 'f := 1; f' ]
		];
	}

	/**
	 * Test mode-related exception where both modes differ
	 *
	 * @param string $expr The expression to test
	 * @param string $mode The mode to test
	 * @dataProvider provideModeDiffer
	 */
	public function testModeDiffer( string $expr, string $mode ): void {
		// conservative mode is supposed to pass.
		// liberal mode is supposed to fail.
		$this->exceptionTest(
			$mode === SyntaxChecker::MCONSERVATIVE ? '' : 'unrecognisedvar',
			$expr,
			$mode,
			false
		);
	}

	/**
	 * Data provider for testModeDiffer
	 *
	 * @return array
	 */
	public static function provideModeDiffer(): array {
		$testSketches = [
			// and
			'(false & (a := 1)); a',
			'(true & (a := 1)); a',
			// or
			'(true | (a := 1)); a',
			'(false | (a := 1)); a',
			// if
			'if 1 then (a := 1) else 1 end; a',
			'if 1 then 1 else (a := 1) end; a',
		];
		$tests = [];
		foreach ( $testSketches as $test ) {
			$tests[] = [ $test, SyntaxChecker::MCONSERVATIVE ];
			$tests[] = [ $test, SyntaxChecker::MLIBERAL ];
		}
		return $tests;
	}

	/**
	 * Test mode-related exception where both modes agree
	 *
	 * @param string $excep The expected exception or an empty string
	 * @param string $expr The expression to test
	 * @param string $mode The mode to test
	 * @dataProvider provideModeAgree
	 */
	public function testModeAgree( string $excep, string $expr, string $mode ): void {
		$this->exceptionTest( $excep, $expr, $mode, false );
	}

	/**
	 * Data provider for testModeAgree
	 *
	 * @return array
	 */
	public static function provideModeAgree(): array {
		$testSketches = [
			// pass tests
			// and
			[ true, '((a := 1) & 0); a' ],
			// or
			[ true, '((a := 1) | 0); a' ],
			// if
			[ true, 'if 1 then (a := 1) else (a := 1) end; a' ],
			[ true, 'if (a := 1) then 1 else 1 end; a' ],

			// fail tests
			// and
			[ false, 'false & a' ],
			// or
			[ false, 'true | a' ],
			// if
			[ false, 'if true then 1 else a end' ],
			[ false, 'if false then a else 1 end' ],
			[ false, 'if a then 1 else 1 end' ],
		];
		$tests = [];
		foreach ( $testSketches as $test ) {
			$excep = $test[0] ? '' : 'unrecognisedvar';
			$tests[] = [ $excep, $test[1], SyntaxChecker::MCONSERVATIVE ];
			$tests[] = [ $excep, $test[1], SyntaxChecker::MLIBERAL ];
		}
		return $tests;
	}

	/**
	 * Test unused variable
	 *
	 * @param string $excep The expected exception or an empty string
	 * @param string $expr The expression to test
	 * @dataProvider provideUnusedVars
	 */
	public function testUnusedVars( string $excep, string $expr ): void {
		$this->exceptionTest( $excep, $expr, SyntaxChecker::MCONSERVATIVE, true );
	}

	/**
	 * Data provider for testUnusedVars
	 *
	 * @return array
	 */
	public static function provideUnusedVars(): array {
		return [
			[ '', 'a := 1; a' ],

			// even though the first a is not used, we allow it to prevent
			// too many false-positives. Note that setting a variable to null
			// and mutate the variable to something else without ever reading
			// that variable is a pretty common idiom, which is another reason
			// we we don't want to error this.
			[ '', 'a := 1; a := 1; a' ],

			[ '', 'a := 1; b := a; b' ],

			// eventually a is not used.
			[ 'unusedvars', 'a := 1' ],

			// eventually a is not used.
			[ 'unusedvars', 'a := 1; a := 1' ],

			// eventually a is not used.
			[ 'unusedvars', 'a := 1; b := a; a := b' ],
		];
	}
}
