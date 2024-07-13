/*!
 * VisualEditor FlatLinearData tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.FlatLinearData' );

/* Tests */

QUnit.test( 'getType/isOpenElementData/isCloseElementData', function ( assert ) {
	var data = new ve.dm.FlatLinearData( new ve.dm.HashValueStore(), [
			{ type: 'paragraph' },
			'a', [ 'b', [ 0 ] ],
			{ type: '/paragraph' }
		] ),
		types = [ 'paragraph', undefined, undefined, 'paragraph' ],
		isOpen = [ 0 ],
		isClose = [ 3 ];

	for ( var i = 0; i < data.getLength(); i++ ) {
		assert.strictEqual( data.getType( i ), types[ i ], 'Type at offset ' + i );
		assert.strictEqual( data.isOpenElementData( i ), isOpen.indexOf( i ) !== -1, 'isOpen ' + i );
		assert.strictEqual( data.isCloseElementData( i ), isClose.indexOf( i ) !== -1, 'isClose ' + i );
	}
} );

QUnit.test( 'isElementData', function ( assert ) {
	var data = new ve.dm.FlatLinearData( new ve.dm.HashValueStore(), [
			{ type: 'heading' },
			'a',
			{ type: 'inlineImage' },
			{ type: '/inlineImage' },
			'b',
			'c',
			{ type: '/heading' },
			{ type: 'paragraph' },
			{ type: '/paragraph' },
			{ type: 'preformatted' },
			{ type: 'inlineImage' },
			{ type: '/inlineImage' },
			{ type: '/preformatted' },
			{ type: 'list' },
			{ type: 'listItem' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'alienBlock' },
			{ type: '/alienBlock' }
		] ),
		cases = [
			{ msg: 'left of document', expected: true },
			{ msg: 'beginning of content branch', expected: false },
			{ msg: 'left of non-text inline leaf', expected: true },
			{ msg: 'inside non-text inline leaf', expected: true },
			{ msg: 'right of non-text inline leaf', expected: false },
			{ msg: 'between characters', expected: false },
			{ msg: 'end of content branch', expected: true },
			{ msg: 'between content branches', expected: true },
			{ msg: 'inside empty content branch', expected: true },
			{ msg: 'between content branches', expected: true },
			{ msg: 'beginning of content branch, left of inline leaf', expected: true },
			{ msg: 'inside content branch with non-text leaf', expected: true },
			{ msg: 'end of content branch, right of inline leaf', expected: true },
			{ msg: 'between content, non-content branches', expected: true },
			{ msg: 'between parent, child branches, descending', expected: true },
			{ msg: 'inside empty non-content branch', expected: true },
			{ msg: 'between parent, child branches, ascending', expected: true },
			{ msg: 'between non-content branch, non-content leaf', expected: true },
			{ msg: 'inside non-content leaf', expected: true },
			{ msg: 'right of document', expected: false }
		];

	cases.forEach( function ( caseItem, i ) {
		assert.strictEqual( data.isElementData( i ), caseItem.expected, caseItem.msg );
	} );
} );

QUnit.test( 'containsElementData', function ( assert ) {
	var cases = [
		{
			msg: 'simple paragraph',
			data: [ { type: 'paragraph' }, 'a', { type: '/paragraph' } ],
			expected: true
		},
		{
			msg: 'plain text',
			data: [ 'a', 'b', 'c' ],
			expected: false
		},
		{
			msg: 'annotated text',
			data: [ [ 'a', { '{"type:"bold"}': { type: 'bold' } } ] ],
			expected: false
		},
		{
			msg: 'non-text leaf',
			data: [ 'a', { type: 'inlineImage' }, { type: '/inlineImage' }, 'c' ],
			expected: true
		}
	];

	cases.forEach( function ( caseItem ) {
		var data = new ve.dm.FlatLinearData( new ve.dm.HashValueStore(), caseItem.data );
		assert.strictEqual(
			data.containsElementData(), caseItem.expected, caseItem.msg
		);
	} );
} );
