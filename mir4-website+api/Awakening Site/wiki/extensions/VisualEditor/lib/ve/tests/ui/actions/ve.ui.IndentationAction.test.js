/*!
 * VisualEditor UserInterface Actions IndentationAction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.IndentationAction' );

/* Tests */

QUnit.test( 'increase/decrease', function ( assert ) {
	var cases = [
		{
			rangeOrSelection: new ve.Range( 13, 14 ),
			method: 'increase',
			expectedRangeOrSelection: new ve.Range( 13, 14 ),
			expectedData: function ( data ) {
				data.splice( 20, 0, { type: '/listItem' }, { type: '/list' } );
				data.splice( 10, 1, { type: 'list', attributes: { style: 'bullet' } } );
			},
			undo: true,
			msg: 'increase indentation on Item 2'
		},
		{
			rangeOrSelection: new ve.Range( 14, 16 ),
			method: 'decrease',
			expectedRangeOrSelection: new ve.Range( 14, 16 ),
			expectedData: function ( data ) {
				data.splice( 11, 2, { type: '/list' }, { type: 'paragraph' } );
				data.splice( 19, 2, { type: '/paragraph' }, { type: 'list', attributes: { style: 'bullet' } } );
			},
			undo: true,
			msg: 'decrease indentation on partial selection of list item "Item 2"'
		},
		{
			rangeOrSelection: new ve.Range( 3, 19 ),
			method: 'decrease',
			expectedRangeOrSelection: new ve.Range( 1, 15 ),
			expectedData: function ( data ) {
				data.splice( 0, 2 );
				data.splice( 8, 2 );
				data.splice( 16, 1, { type: 'list', attributes: { style: 'bullet' } } );
				delete data[ 0 ].internal;
				delete data[ 8 ].internal;
			},
			undo: true,
			msg: 'decrease indentation on Items 1 & 2'
		},
		{
			rangeOrSelection: new ve.Range( 3, 19 ),
			method: 'increase',
			expectedRangeOrSelection: new ve.Range( 5, 21 ),
			expectedData: function ( data ) {
				data.splice( 0, 0, { type: 'list', attributes: { style: 'bullet' } }, { type: 'listItem' } );
				data.splice( 23, 0, { type: '/list' }, { type: '/listItem' } );
			},
			undo: true,
			msg: 'increase indentation on Items 1 & 2'
		},
		{
			html: '<ul><li><table><tr><td>A</td></tr></table></li></ul>',
			rangeOrSelection: new ve.Range( 2 ),
			method: 'decrease',
			expectedRangeOrSelection: new ve.Range( 0 ),
			expectedData: function ( data ) {
				data.splice( 13, 2 );
				data.splice( 0, 2 );
			},
			undo: true,
			msg: 'decrease indentation in slug'
		},
		{
			html: '<ul><li><table><tr><td>A</td></tr></table></li></ul>',
			rangeOrSelection: new ve.Range( 2 ),
			method: 'increase',
			expectedRangeOrSelection: new ve.Range( 4 ),
			expectedData: function ( data ) {
				data.splice( 15, 0, { type: '/listItem' }, { type: '/list' } );
				data.splice( 0, 0, { type: 'list', attributes: { style: 'bullet' } }, { type: 'listItem' } );
			},
			undo: true,
			msg: 'increase indentation in slug'
		},
		{
			// * a
			// ** b
			// * c
			html: '<ul><li><p>a</p><ul><li><p>b</p></li></ul></li><li><p>c</p></li></ul>',
			rangeOrSelection: new ve.Range( 9 ),
			method: 'decrease',
			expectedRangeOrSelection: new ve.Range( 9 ),
			expectedData: function ( data ) {
				data.splice( 11, 2 );
				data.splice( 5, 1, { type: '/listItem' } );
			},
			undo: true,
			msg: 'decrease indentation of double-indented item'
		},
		{
			html: '<table><tr><td>A</td></tr></table>',
			rangeOrSelection: {
				type: 'table',
				tableRange: new ve.Range( 0, 13 ),
				fromCol: 0,
				fromRow: 0,
				toCol: 0,
				toRow: 0
			},
			method: 'increase',
			expectedRangeOrSelection: {
				type: 'table',
				tableRange: new ve.Range( 0, 13 ),
				fromCol: 0,
				fromRow: 0,
				toCol: 0,
				toRow: 0
			},
			expectedData: function () {},
			msg: 'no-op on a table selection'
		}
	];

	cases.forEach( function ( caseItem ) {
		ve.test.utils.runActionTest(
			'indentation', assert, caseItem.html || ve.dm.example.isolationHtml, false, caseItem.method, [], caseItem.rangeOrSelection, caseItem.msg,
			{
				expectedData: caseItem.expectedData,
				expectedOriginalData: caseItem.expectedOriginalData,
				expectedRangeOrSelection: caseItem.expectedRangeOrSelection,
				undo: caseItem.undo
			}
		);
	} );
} );
