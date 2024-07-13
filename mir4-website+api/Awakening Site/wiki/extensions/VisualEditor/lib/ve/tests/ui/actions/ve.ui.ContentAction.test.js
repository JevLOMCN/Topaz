/*!
 * VisualEditor UserInterface Actions ContentAction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.ContentAction' );

/* Tests */

QUnit.test( 'insert/remove/select/selectAll', function ( assert ) {
	var cases = [
		{
			rangeOrSelection: new ve.Range( 3, 4 ),
			method: 'insert',
			args: [ 'Foo' ],
			expectedData: function ( data ) {
				data.splice( 3, 1,
					'F', 'o', 'o'
				);
			},
			expectedRangeOrSelection: new ve.Range( 3, 6 ),
			undo: true,
			msg: 'insert text (annotate=false)'
		},
		{
			rangeOrSelection: new ve.Range( 3, 4 ),
			method: 'insert',
			args: [ 'Foo', true ],
			expectedData: function ( data ) {
				data.splice( 3, 1,
					[ 'F', [ ve.dm.example.italic ] ],
					[ 'o', [ ve.dm.example.italic ] ],
					[ 'o', [ ve.dm.example.italic ] ]
				);
			},
			expectedRangeOrSelection: new ve.Range( 3, 6 ),
			undo: true,
			msg: 'insert text (annotate=true)'
		},
		{
			rangeOrSelection: new ve.Range( 3, 4 ),
			method: 'insert',
			args: [ 'Foo', false, true ],
			expectedData: function ( data ) {
				data.splice( 3, 1,
					'F', 'o', 'o'
				);
			},
			expectedRangeOrSelection: new ve.Range( 6 ),
			undo: true,
			msg: 'insert text (collapseToEnd=true)'
		},
		{
			rangeOrSelection: new ve.Range( 1, 4 ),
			method: 'remove',
			expectedData: function ( data ) {
				data.splice( 1, 3 );
			},
			expectedRangeOrSelection: new ve.Range( 1 ),
			undo: true,
			msg: 'remove text'
		},
		{
			rangeOrSelection: new ve.Range( 0 ),
			method: 'select',
			args: [ new ve.dm.LinearSelection( new ve.Range( 1, 4 ) ) ],
			expectedRangeOrSelection: new ve.Range( 1, 4 ),
			msg: 'select'
		},
		{
			createView: true,
			rangeOrSelection: new ve.Range( 1 ),
			method: 'selectAll',
			expectedRangeOrSelection: new ve.Range( 1, 60 ),
			msg: 'select all in text selects the whole document'
		},
		{
			createView: true,
			html: ve.dm.example.mergedCellsHtml,
			rangeOrSelection: {
				type: 'table',
				tableRange: new ve.Range( 0, 171 ),
				fromCol: 0,
				fromRow: 0,
				toCol: 0,
				toRow: 0
			},
			method: 'selectAll',
			expectedRangeOrSelection: {
				type: 'table',
				tableRange: new ve.Range( 0, 171 ),
				fromCol: 0,
				fromRow: 0,
				toCol: 5,
				toRow: 6
			},
			msg: 'select all in a table selects the whole table'
		}
	];

	cases.forEach( function ( caseItem ) {
		ve.test.utils.runActionTest(
			'content', assert, caseItem.html, caseItem.createView, caseItem.method, caseItem.args, caseItem.rangeOrSelection, caseItem.msg,
			{
				expectedData: caseItem.expectedData,
				expectedRangeOrSelection: caseItem.expectedRangeOrSelection,
				undo: caseItem.undo
			}
		);
	} );
} );
