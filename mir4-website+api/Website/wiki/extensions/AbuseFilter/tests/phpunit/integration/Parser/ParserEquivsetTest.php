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

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration\Parser;

use EmptyBagOStuff;
use LanguageEn;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWikiIntegrationTestCase;
use NullStatsdDataFactory;
use Wikimedia\Equivset\Equivset;

/**
 * Tests that require Equivset, separated from the parser unit tests.
 *
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
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AFPData
 * @covers \MediaWiki\Extension\AbuseFilter\Parser\SyntaxChecker
 */
class ParserEquivsetTest extends MediaWikiIntegrationTestCase {

	private function getParser(): FilterEvaluator {
		// We're not interested in caching or logging; tests should call respectively setCache
		// and setLogger if they want to test any of those.
		$contLang = new LanguageEn();
		$cache = new EmptyBagOStuff();
		$logger = new \Psr\Log\NullLogger();
		$keywordsManager = AbuseFilterServices::getKeywordsManager();
		$varManager = new VariablesManager(
			$keywordsManager,
			$this->createMock( LazyVariableComputer::class )
		);

		$evaluator = new FilterEvaluator(
			$contLang,
			$cache,
			$logger,
			$keywordsManager,
			$varManager,
			new NullStatsdDataFactory(),
			new Equivset(),
			1000
		);
		$evaluator->toggleConditionLimit( false );
		return $evaluator;
	}

	/**
	 * @param string $rule The rule to parse
	 * @dataProvider provideGenericTests
	 */
	public function testGeneric( $rule ) {
		$this->assertTrue( $this->getParser()->parse( $rule ) );
	}

	public static function provideGenericTests() {
		$testPath = __DIR__ . "/../../../parserTestsEquivset";
		$testFiles = glob( $testPath . "/*.t" );

		foreach ( $testFiles as $testFile ) {
			$testName = basename( substr( $testFile, 0, -2 ) );
			$rule = trim( file_get_contents( $testFile ) );

			yield $testName => [ $rule ];
		}
	}
}
