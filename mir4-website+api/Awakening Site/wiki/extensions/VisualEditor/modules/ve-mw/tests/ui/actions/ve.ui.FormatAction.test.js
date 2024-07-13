/*!
 * VisualEditor UserInterface Actions FormatAction tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.ui.FormatAction (MW)', ve.test.utils.newMwEnvironment() );

/* Tests */

QUnit.test( 'convert (MW-specific types)', ( assert ) => {
	const cases = [
			{
				rangeOrSelection: new ve.Range( 14, 16 ),
				type: 'mwHeading',
				attributes: { level: 2 },
				expectedRangeOrSelection: new ve.Range( 14, 16 ),
				expectedData: ( data ) => {
					data.splice( 11, 2, { type: '/list' }, { type: 'mwHeading', attributes: { level: 2 } } );
					data.splice( 19, 2, { type: '/mwHeading' }, { type: 'list', attributes: { style: 'bullet' } } );
				},
				undo: true,
				msg: 'converting partial selection of list item "Item 2" to level 2 mwHeading'
			},
			{
				rangeOrSelection: new ve.Range( 15, 50 ),
				type: 'mwHeading',
				attributes: { level: 3 },
				expectedRangeOrSelection: new ve.Range( 15, 44 ),
				expectedData: ( data ) => {
					data.splice( 11, 2, { type: '/list' }, { type: 'mwHeading', attributes: { level: 3 } } );
					data.splice( 19, 4, { type: '/mwHeading' }, { type: 'mwHeading', attributes: { level: 3 } } );
					data.splice( 27, 4, { type: '/mwHeading' }, { type: 'mwHeading', attributes: { level: 3 } } );
					data.splice( 38, 4, { type: '/mwHeading' }, { type: 'mwHeading', attributes: { level: 3 } } );
					data.splice( 46, 2, { type: '/mwHeading' }, { type: 'list', attributes: { style: 'bullet' } } );
				},
				undo: true,
				msg: 'converting partial selection across two lists surrounding a paragraph'
			},
			{
				rangeOrSelection: new ve.Range( 4, 28 ),
				type: 'mwHeading',
				attributes: { level: 1 },
				expectedRangeOrSelection: new ve.Range( 2, 22 ),
				expectedData: ( data ) => {
					data.splice( 0, 3, { type: 'mwHeading', attributes: { level: 1 } } );
					data.splice( 7, 4, { type: '/mwHeading' }, { type: 'mwHeading', attributes: { level: 1 } } );
					data.splice( 15, 4, { type: '/mwHeading' }, { type: 'mwHeading', attributes: { level: 1 } } );
					data.splice( 23, 3, { type: '/mwHeading' } );
				},
				undo: true,
				msg: 'converting partial selection of all list items to level 1 MWheadings'
			},
			{
				rangeOrSelection: new ve.Range( 5, 26 ),
				type: 'mwPreformatted',
				attributes: undefined,
				expectedRangeOrSelection: new ve.Range( 3, 20 ),
				expectedData: ( data ) => {
					data.splice( 0, 3, { type: 'mwPreformatted' } );
					data.splice( 7, 4, { type: '/mwPreformatted' }, { type: 'mwPreformatted' } );
					data.splice( 15, 4, { type: '/mwPreformatted' }, { type: 'mwPreformatted' } );
					data.splice( 23, 3, { type: '/mwPreformatted' } );
				},
				undo: true,
				msg: 'converting partial selection of some list items to mwPreformatted text'
			}
		];

	for ( let i = 0; i < cases.length; i++ ) {
		ve.test.utils.runActionTest(
			'format', assert, cases[ i ].html || ve.dm.example.isolationHtml, false, 'convert',
			[ cases[ i ].type, cases[ i ].attributes ], cases[ i ].rangeOrSelection, cases[ i ].msg,
			{
				expectedData: cases[ i ].expectedData,
				expectedRangeOrSelection: cases[ i ].expectedRangeOrSelection,
				undo: cases[ i ].undo
			}
		);
	}
} );
