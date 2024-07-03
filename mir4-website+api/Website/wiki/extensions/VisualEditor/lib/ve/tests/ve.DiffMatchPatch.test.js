/*!
 * VisualEditor BranchNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.DiffMatchPatch' );

/* Tests */

QUnit.test( 'indexOf/lastIndexOf', function ( assert ) {
	var dmp = new ve.DiffMatchPatch( new ve.dm.HashValueStore(), new ve.dm.HashValueStore() ),
		textString = 'hello world',
		textArray = textString.split( '' ),
		cases = [
			{
				msg: 'search value present',
				searchValue: 'o'
			},
			{
				msg: 'search value present, from index negative',
				searchValue: 'o',
				fromIndex: -2
			},
			{
				msg: 'search value present, from index 0',
				searchValue: 'o',
				fromIndex: 0
			},
			{
				msg: 'search value present, from index before first occurrence',
				searchValue: 'o',
				fromIndex: 2
			},
			{
				msg: 'search value present, from index first occurrence',
				searchValue: 'o',
				fromIndex: 4
			},
			{
				msg: 'search value present, from index between first and last occurrence',
				searchValue: 'o',
				fromIndex: 6
			},
			{
				msg: 'search value present, from index last occurrence',
				searchValue: 'o',
				fromIndex: 7
			},
			{
				msg: 'search value present, from index array length',
				searchValue: 'o',
				fromIndex: 11
			},
			{
				msg: 'search value present, from greater than array length',
				searchValue: 'o',
				fromIndex: 13
			},
			{
				msg: 'empty string',
				searchValue: ''
			},
			{
				msg: 'empty string, negative index',
				searchValue: '',
				fromIndex: -2
			},
			{
				msg: 'empty string, index within array',
				searchValue: '',
				fromIndex: 6
			},
			{
				msg: 'empty string, index greater than array length',
				searchValue: '',
				fromIndex: 99
			},
			{
				msg: 'search value not present',
				searchValue: 'heya'
			},
			{
				msg: 'search value multiple characters',
				searchValue: 'world'
			}
		];

	cases.forEach( function ( caseItem ) {
		var expected = textString.indexOf( caseItem.searchValue, caseItem.fromIndex );
		assert.strictEqual(
			dmp.indexOf( textArray, caseItem.searchValue, caseItem.fromIndex ),
			expected,
			caseItem.msg + ': indexOf is ' + expected
		);
		expected = textString.lastIndexOf( caseItem.searchValue, caseItem.fromIndex );
		assert.strictEqual(
			dmp.lastIndexOf( textArray, caseItem.searchValue, caseItem.fromIndex ),
			expected,
			caseItem.msg + ': lastIndexOf is ' + expected
		);
	} );
} );
