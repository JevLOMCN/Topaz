<?php

use MediaWiki\Extension\Notifications\Iterator\CallbackIterator;
use MediaWiki\Extension\Notifications\Iterator\FilteredSequentialIterator;

/**
 * @covers \MediaWiki\Extension\Notifications\Iterator\CallbackIterator
 * @covers \MediaWiki\Extension\Notifications\Iterator\FilteredSequentialIterator
 */
class FilteredSequentialIteratorTest extends MediaWikiUnitTestCase {

	public function testCallbackIteratorDoesntBlowUp() {
		$it = new CallbackIterator(
			new ArrayIterator( [ 1, 2, 3 ] ),
			static function ( $num ) {
				return "There were $num items";
			}
		);

		foreach ( $it as $val ) {
			$res[] = $val;
		}

		$expected = [ "There were 1 items", "There were 2 items", "There were 3 items" ];
		$this->assertEquals( $expected, $res, 'Basic iteration with callback applied' );
	}

	public static function filteredSequentialIteratorProvider() {
		$odd = static function ( $v ) {
			return $v & 1;
		};
		$greaterThanFour = static function ( $v ) {
			return $v > 4;
		};

		return [
			[
				'Empty object still works',
				// expected result
				[],
				// list of iterators/arrays/etc each containing users
				[],
				// list of filters to apply on output
				[],
			],
			[
				'Basic iteration with one array and no filters',
				// expected result
				[ 1, 2, 3 ],
				// list of iterators/arrays/etc each containing users
				[ [ 1, 2, 3 ] ],
				// list of filters to apply on output
				[]
			],
			[
				'Basic iteration with one array and one filters',
				// expected result
				[ 1, 3 ],
				// list of tierators/arrays/etc each containing users
				[ [ 1, 2, 3 ] ],
				// list of filters to apply on output
				[ $odd ],
			],
			[
				'Iteration with multiple input arrays and no filters',
				// expected result (iterators are run in parallel)
				[ 1, 4, 2, 5, 3 ],
				// list of tierators/arrays/etc each containing users
				[ [ 1, 2, 3 ], [ 4, 5 ] ],
				// list of filters to apply on output
				[],
			],
			[
				'Iteration with multiple input arrays and multiple filters',
				// expected result
				[ 5 ],
				// list of tierators/arrays/etc each containing users
				[ [ 1, 2 ], [ 3, 4 ], [ 5, 6 ] ],
				// list of filters to apply on output
				[ $odd, $greaterThanFour ],
			],
			[
				'Iteration with interspersed empty arrays',
				// expected result
				[ 1, 3, 2 ],
				// list of tierators/arrays/etc each containing users
				[ [], [ 1, 2 ], [ 3 ], [] ],
				// list of filters to apply on output
				[],
			],
		];
	}

	/**
	 * @dataProvider filteredSequentialIteratorProvider
	 */
	public function testFilteredSequentialIterator( $message, $expect, $userLists, $filters ) {
		$notify = new FilteredSequentialIterator;

		foreach ( $userLists as $userList ) {
			$notify->add( $userList );
		}

		foreach ( $filters as $filter ) {
			$notify->addFilter( $filter );
		}

		$result = [];
		foreach ( $notify as $value ) {
			$result[] = $value;
		}

		$this->assertEquals( $expect, $result, $message );
	}
}
