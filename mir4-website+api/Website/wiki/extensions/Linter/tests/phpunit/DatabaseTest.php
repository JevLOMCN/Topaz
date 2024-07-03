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
 */

namespace MediaWiki\Linter\Test;

use MediaWiki\Linter\Database;
use MediaWiki\Linter\LintError;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Linter\Database
 */
class DatabaseTest extends MediaWikiIntegrationTestCase {
	public function testConstructor() {
		$this->assertInstanceOf( Database::class, new Database( 5 ) );
	}

	private function getDummyLintErrors() {
		return [
			new LintError(
				'fostered', [ 0, 10 ], []
			),
			new LintError(
				'obsolete-tag', [ 15, 20 ], [ 'name' => 'big' ]
			),
		];
	}

	private function assertSetForPageResult( $result, $deleted, $added ) {
		$this->assertArrayHasKey( 'deleted', $result );
		$this->assertEquals( $deleted, $result['deleted'] );
		$this->assertArrayHasKey( 'added', $result );
		$this->assertEquals( $added, $result['added'] );
	}

	private function assertLintErrorsEqual( $expected, $actual ) {
		$expectedIds = array_map( static function ( LintError $error ) {
			return $error->id();
		}, $expected );
		$actualIds = array_map( static function ( LintError $error ) {
			return $error->id();
		}, $actual );
		$this->assertArrayEquals( $expectedIds, $actualIds );
	}

	private function createManyLintErrors( $lintDb, $errorCount ) {
		$manyLintErrors = [];
		for ( $i = 0; $i < $errorCount; $i++ ) {
			$templateName = "Template:Echo";
			$manyLintErrors[] = new LintError(
				'obsolete-tag', [ 15, 20 + $i ], [ 'name' => 'big',
					"templateInfo" => [ "name" => $templateName ] ]
			);
		}
		$lintDb->setForPage( $manyLintErrors );
	}

	public function testSetForPage() {
		$lintDb = new Database( 5 );
		$dummyErrors = $this->getDummyLintErrors();
		$result = $lintDb->setForPage( $dummyErrors );
		$this->assertSetForPageResult( $result, [], [ 'fostered' => 1, 'obsolete-tag' => 1 ] );
		$this->assertLintErrorsEqual( $dummyErrors, $lintDb->getForPage() );

		// Accurate low error count values should match for both methods
		$resultTotals = $lintDb->getTotalsForPage();
		$resultEstimatedTotals = $lintDb->getTotals();
		$this->assertEquals( $resultTotals, $resultEstimatedTotals );

		// Should delete the second error
		$result2 = $lintDb->setForPage( [ $dummyErrors[0] ] );
		$this->assertSetForPageResult( $result2, [ 'obsolete-tag' => 1 ], [] );
		$this->assertLintErrorsEqual( [ $dummyErrors[0] ], $lintDb->getForPage() );

		// Accurate low error count values should match for both methods
		$resultTotals = $lintDb->getTotalsForPage();
		$resultEstimatedTotals = $lintDb->getTotals();
		$this->assertEquals( $resultTotals, $resultEstimatedTotals );

		// Insert the second error, delete the first
		$result3 = $lintDb->setForPage( [ $dummyErrors[1] ] );
		$this->assertSetForPageResult( $result3, [ 'fostered' => 1 ], [ 'obsolete-tag' => 1 ] );
		$this->assertLintErrorsEqual( [ $dummyErrors[1] ], $lintDb->getForPage() );

		// Delete the second (only) error
		$result4 = $lintDb->setForPage( [] );
		$this->assertSetForPageResult( $result4, [ 'obsolete-tag' => 1 ], [] );
		$this->assertLintErrorsEqual( [], $lintDb->getForPage() );

		// Accurate low error count values should match for both methods
		$resultTotals = $lintDb->getTotalsForPage();
		$resultEstimatedTotals = $lintDb->getTotals();
		$this->assertEquals( $resultTotals, $resultEstimatedTotals );

		// For error counts <= MAX_ACCURATE_COUNT, both error
		// count methods should return the same count.
		$this->createManyLintErrors( $lintDb, Database::MAX_ACCURATE_COUNT );
		$resultTotals = $lintDb->getTotalsForPage();
		$resultEstimatedTotals = $lintDb->getTotals();
		$this->assertEquals( $resultTotals, $resultEstimatedTotals );

		// FIXME: These tests seem to be making false assumptions about
		// `estimateRowCount()`.  "EXPLAIN" is just going to give an estimate of
		// the row count, it doesn't seem like there's any guarantee that it'll
		// be higher or lower.
		//
		// // For error counts greater than MAX_ACCURATE_COUNT, both error
		// // count methods should NOT return the same count in this test scenario
		// // because previously added and deleted records will be included
		// // in the estimated count which is normal.
		// $this->createManyLintErrors( $lintDb, Database::MAX_ACCURATE_COUNT + 1 );
		// $resultTotals = $lintDb->getTotalsForPage();
		// $resultEstimatedTotals = $lintDb->getTotals();
		// $this->assertNotEquals( $resultTotals, $resultEstimatedTotals );
		//
		// // For error counts greatly above the MAX_ACCURATE_COUNT, the estimated
		// // count method should return a greater count in this test scenario
		// // because previously added and deleted records will be included
		// // in the estimated count which is normal.
		// $this->createManyLintErrors( $lintDb, Database::MAX_ACCURATE_COUNT * 10 );
		// $resultTotals = $lintDb->getTotalsForPage();
		// $resultEstimatedTotals = $lintDb->getTotals();
		// $this->assertGreaterThan( $resultTotals, $resultEstimatedTotals );
	}

}
