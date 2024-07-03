/*!
 * VisualEditor UserInterface Actions FormatAction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.FormatAction' );

/* Tests */

QUnit.test( 'convert', function ( assert ) {
	var cases = [
		{
			rangeOrSelection: new ve.Range( 14, 16 ),
			type: 'heading',
			attributes: { level: 2 },
			expectedRangeOrSelection: new ve.Range( 14, 16 ),
			expectedData: function ( data ) {
				data.splice( 12, 1, { type: 'heading', attributes: { level: 2 } } );
				data.splice( 19, 1, { type: '/heading' } );
			},
			undo: true,
			msg: 'converting partial selection of list item "Item 2" to level 2 heading'
		},
		{
			rangeOrSelection: new ve.Range( 15, 50 ),
			type: 'heading',
			attributes: { level: 3 },
			expectedRangeOrSelection: new ve.Range( 15, 50 ),
			expectedData: function ( data ) {
				data.splice( 12, 1, { type: 'heading', attributes: { level: 3 } } );
				data.splice( 19, 1, { type: '/heading' } );
				data.splice( 22, 1, { type: 'heading', attributes: { level: 3 } } );
				data.splice( 29, 1, { type: '/heading' } );
				data.splice( 32, 1, { type: 'heading', attributes: { level: 3 } } );
				data.splice( 42, 1, { type: '/heading' } );
				data.splice( 45, 1, { type: 'heading', attributes: { level: 3 } } );
				data.splice( 52, 1, { type: '/heading' } );
			},
			undo: true,
			msg: 'converting partial selection across two lists surrounding a paragraph'
		},
		{
			rangeOrSelection: new ve.Range( 4, 28 ),
			type: 'heading',
			attributes: { level: 1 },
			expectedRangeOrSelection: new ve.Range( 4, 28 ),
			expectedData: function ( data ) {
				data.splice( 2, 1, { type: 'heading', attributes: { level: 1 } } );
				data.splice( 9, 1, { type: '/heading' } );
				data.splice( 12, 1, { type: 'heading', attributes: { level: 1 } } );
				data.splice( 19, 1, { type: '/heading' } );
				data.splice( 22, 1, { type: 'heading', attributes: { level: 1 } } );
				data.splice( 29, 1, { type: '/heading' } );
			},
			undo: true,
			msg: 'converting partial selection of all list items to level 1 headings'
		},
		{
			rangeOrSelection: new ve.Range( 5, 26 ),
			type: 'preformatted',
			attributes: undefined,
			expectedRangeOrSelection: new ve.Range( 5, 26 ),
			expectedData: function ( data ) {
				data.splice( 2, 1, { type: 'preformatted' } );
				data.splice( 9, 1, { type: '/preformatted' } );
				data.splice( 12, 1, { type: 'preformatted' } );
				data.splice( 19, 1, { type: '/preformatted' } );
				data.splice( 22, 1, { type: 'preformatted' } );
				data.splice( 29, 1, { type: '/preformatted' } );
			},
			undo: true,
			msg: 'converting partial selection of some list items to preformatted text'
		},
		{
			rangeOrSelection: new ve.Range( 146, 159 ),
			type: 'paragraph',
			attributes: undefined,
			expectedRangeOrSelection: new ve.Range( 146, 159 ),
			expectedData: function ( data ) {
				data.splice( 145, 1, { type: 'paragraph' } );
				data.splice( 159, 1, { type: '/paragraph' } );
			},
			undo: true,
			msg: 'converting heading in list item to paragraph'
		},
		{
			rangeOrSelection: new ve.Range( 165, 180 ),
			type: 'paragraph',
			attributes: undefined,
			expectedRangeOrSelection: new ve.Range( 165, 180 ),
			expectedData: function ( data ) {
				data.splice( 162, 1, { type: 'paragraph' } );
				data.splice( 183, 1, { type: '/paragraph' } );
			},
			undo: true,
			msg: 'converting preformatted in list item to paragraph'
		},
		{
			html: '<p>a</p><p></p>',
			rangeOrSelection: new ve.Range( 4 ),
			type: 'heading',
			attributes: { level: 2 },
			expectedRangeOrSelection: new ve.Range( 4 ),
			expectedData: function ( data ) {
				data.splice( 3, 1, { type: 'heading', attributes: { level: 2 } } );
				data.splice( 4, 1, { type: '/heading' } );
			},
			undo: true,
			msg: 'converting empty paragraph to heading'
		},
		{
			html: '<p>foo</p><p>bar</p>',
			rangeOrSelection: new ve.Range( 1, 6 ),
			type: 'heading',
			attributes: { level: 2 },
			expectedRangeOrSelection: new ve.Range( 1, 6 ),
			expectedData: function ( data ) {
				data.splice( 0, 1, { type: 'heading', attributes: { level: 2 } } );
				data.splice( 4, 1, { type: '/heading' } );
			},
			undo: true,
			msg: 'covering first paragraph but empty in the second paragraph: only converts first paragraph'
		},
		{
			html: '<p>foo</p><p>bar</p>',
			rangeOrSelection: new ve.Range( 2 ),
			type: 'heading',
			attributes: { level: 2 },
			expectedRangeOrSelection: new ve.Range( 2 ),
			expectedData: function ( data ) {
				data.splice( 0, 1, { type: 'heading', attributes: { level: 2 } } );
				data.splice( 4, 1, { type: '/heading' } );
			},
			undo: true,
			msg: 'collapsed in paragraph'
		}
	];

	cases.forEach( function ( caseItem ) {
		ve.test.utils.runActionTest(
			'format', assert, caseItem.html || ve.dm.example.isolationHtml, false, 'convert',
			[ caseItem.type, caseItem.attributes ], caseItem.rangeOrSelection, caseItem.msg,
			{
				expectedData: caseItem.expectedData,
				expectedRangeOrSelection: caseItem.expectedRangeOrSelection,
				undo: caseItem.undo
			}
		);
	} );
} );
