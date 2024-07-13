/*!
 * VisualEditor UserInterface Actions BlockquoteAction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.BlockquoteAction' );

/* Tests */

QUnit.test( 'wrap/unwrap/toggle', function ( assert ) {
	var cases = [
		{
			html: '<p>aa</p><p>bb</p><p>cc</p>',
			rangeOrSelection: new ve.Range( 6 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 7 ),
			expectedData: function ( data ) {
				data.splice( 4, 0, { type: 'blockquote' } );
				data.splice( 9, 0, { type: '/blockquote' } );
			},
			msg: 'wrapping a single paragraph in a blockquote (collapsed selection)'
		},
		{
			html: '<p>aa</p><p>bb</p><p>cc</p>',
			rangeOrSelection: new ve.Range( 2, 6 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 3, 7 ),
			expectedData: function ( data ) {
				data.splice( 0, 0, { type: 'blockquote' } );
				data.splice( 9, 0, { type: '/blockquote' } );
			},
			msg: 'wrapping multiple paragraphs in a blockquote'
		},

		{
			html: '<p>aa</p><p>bb</p><p>cc</p>',
			rangeOrSelection: new ve.Range( 3, 7 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 3, 8 ),
			expectedData: function ( data ) {
				data.splice( 4, 0, { type: 'blockquote' } );
				data.splice( 9, 0, { type: '/blockquote' } );
			},
			msg: 'wrapping a single paragraph in a blockquote (selection includes only opening tag)'
		},
		{
			html: '<p>aa</p><p>bb</p><p>cc</p>',
			rangeOrSelection: new ve.Range( 5, 9 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 6, 11 ),
			expectedData: function ( data ) {
				data.splice( 4, 0, { type: 'blockquote' } );
				data.splice( 9, 0, { type: '/blockquote' } );
			},
			msg: 'wrapping a single paragraph in a blockquote (selection includes only closing tag)'
		},

		{
			html: '<p>aa</p><blockquote><p>bb</p></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 7 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 6 ),
			expectedData: function ( data ) {
				data.splice( 4, 1 );
				data.splice( 8, 1 );
			},
			msg: 'unwrapping a blockquote (collapsed selection)'
		},
		{
			html: '<p>aa</p><blockquote><p>bb</p></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 6, 8 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 5, 7 ),
			expectedData: function ( data ) {
				data.splice( 4, 1 );
				data.splice( 8, 1 );
			},
			msg: 'unwrapping a blockquote (contents selected)'
		},
		{
			html: '<p>aa</p><blockquote><p>bb</p></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 6, 7 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 5, 6 ),
			expectedData: function ( data ) {
				data.splice( 4, 1 );
				data.splice( 8, 1 );
			},
			msg: 'unwrapping a blockquote (contents partially selected)'
		},
		{
			html: '<p>aa</p><blockquote><p>bb</p></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 3, 8 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 3, 7 ),
			expectedData: function ( data ) {
				data.splice( 4, 1 );
				data.splice( 8, 1 );
			},
			msg: 'unwrapping a blockquote (selection includes only opening tag)'
		},
		{
			html: '<p>aa</p><blockquote><p>bb</p></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 6, 11 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 5, 9 ),
			expectedData: function ( data ) {
				data.splice( 4, 1 );
				data.splice( 8, 1 );
			},
			msg: 'unwrapping a blockquote (selection includes only closing tag)'
		},

		{
			html: '<p>aa</p><blockquote><p>bb</p></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 2, 7 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 3, 8 ),
			expectedData: function ( data ) {
				data.splice( 0, 0, { type: 'blockquote' } );
				data.splice( 11, 0, { type: '/blockquote' } );
			},
			msg: 'double-wrapping a blockquote (selection partially covering)'
		},
		{
			html: '<p>aa</p><blockquote><p>bb</p></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 2, 12 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 3, 13 ),
			expectedData: function ( data ) {
				data.splice( 0, 0, { type: 'blockquote' } );
				data.splice( 15, 0, { type: '/blockquote' } );
			},
			msg: 'double-wrapping a blockquote (selection fully covering)'
		},

		{
			html: '<blockquote><p>aa</p><blockquote><p>bb</p></blockquote></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 8 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 7 ),
			expectedData: function ( data ) {
				data.splice( 5, 1 );
				data.splice( 9, 1 );
			},
			msg: 'unwrapping inner nested blockquote (collapsed selection)'
		},
		{
			html: '<blockquote><p>aa</p><blockquote><p>bb</p></blockquote></blockquote><p>cc</p>',
			rangeOrSelection: new ve.Range( 3 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 2 ),
			expectedData: function ( data ) {
				data.splice( 0, 1 );
				data.splice( 10, 1 );
			},
			msg: 'unwrapping outer nested blockquote (collapsed selection)'
		},

		{
			html: '<p>xx</p><ul><li><p>aa</p></li><li><p>bb</p></li><li><p>cc</p></li></ul>',
			rangeOrSelection: new ve.Range( 14 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 15 ),
			expectedData: function ( data ) {
				data.splice( 12, 0, { type: 'blockquote' } );
				data.splice( 17, 0, { type: '/blockquote' } );
			},
			msg: 'wrapping contents of a list item in a blockquote'
		},
		{
			html: '<p>xx</p><ul><li><p>aa</p></li><li><p>bb</p></li><li><p>cc</p></li></ul>',
			rangeOrSelection: new ve.Range( 8, 14 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 9, 15 ),
			expectedData: function ( data ) {
				data.splice( 4, 0, { type: 'blockquote' } );
				data.splice( 25, 0, { type: '/blockquote' } );
			},
			msg: 'wrapping multiple list items in a blockquote (wraps whole list)'
		},
		{
			html: '<p>xx</p><ul><li><p>aa</p></li><li><p>bb</p></li><li><p>cc</p></li></ul>',
			rangeOrSelection: new ve.Range( 2, 8 ),
			method: 'toggle',
			expectedRangeOrSelection: new ve.Range( 3, 9 ),
			expectedData: function ( data ) {
				data.splice( 0, 0, { type: 'blockquote' } );
				data.splice( 25, 0, { type: '/blockquote' } );
			},
			msg: 'wrapping partially covered list in a blockquote'
		}
	];

	cases.forEach( function ( caseItem ) {
		ve.test.utils.runActionTest(
			'blockquote', assert, caseItem.html, false, caseItem.method, [], caseItem.rangeOrSelection, caseItem.msg,
			{
				expectedData: caseItem.expectedData,
				expectedOriginalData: caseItem.expectedOriginalData,
				expectedRangeOrSelection: caseItem.expectedRangeOrSelection
			}
		);
	} );
} );
