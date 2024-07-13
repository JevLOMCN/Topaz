/*!
 * VisualEditor UserInterface Actions LinkAction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.LinkAction' );

/* Tests */

QUnit.test( 'autolink', function ( assert ) {
	var cases = [
		{
			html: '<p>http://example.com xyz</p>',
			rangeOrSelection: new ve.Range( 1, 19 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 19 ),
			expectedOriginalRangeOrSelection: new ve.Range( 19 ),
			expectedData: function ( data, action ) {
				var i,
					a = action.getLinkAnnotation( 'http://example.com' );
				for ( i = 1; i < 19; i++ ) {
					data[ i ] = [ data[ i ], [ a.element ] ];
				}
			},
			undo: true,
			msg: 'Autolink after space'
		},
		{
			html: '<p>http://example.com</p><p>xyz</p>',
			rangeOrSelection: new ve.Range( 1, 19 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 19 ),
			expectedOriginalRangeOrSelection: new ve.Range( 19 ),
			expectedData: function ( data, action ) {
				var i,
					a = action.getLinkAnnotation( 'http://example.com' );
				for ( i = 1; i < 19; i++ ) {
					data[ i ] = [ data[ i ], [ a.element ] ];
				}
			},
			undo: true,
			msg: 'Autolink after newline'
		},
		{
			html: '<p>Http://Example.COm xyz</p>',
			rangeOrSelection: new ve.Range( 1, 19 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 19 ),
			expectedOriginalRangeOrSelection: new ve.Range( 19 ),
			expectedData: function ( data, action ) {
				var i,
					a = action.getLinkAnnotation( 'Http://Example.COm' );
				for ( i = 1; i < 19; i++ ) {
					data[ i ] = [ data[ i ], [ a.element ] ];
				}
			},
			undo: true,
			msg: 'Autolink with mixed case'
		},
		{
			html: '<p>http://example.com.) xyz</p>',
			rangeOrSelection: new ve.Range( 1, 21 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 21 ),
			expectedOriginalRangeOrSelection: new ve.Range( 21 ),
			expectedData: function ( data, action ) {
				var i,
					a = action.getLinkAnnotation( 'http://example.com' );
				for ( i = 1; i < 19; i++ ) {
					data[ i ] = [ data[ i ], [ a.element ] ];
				}
			},
			undo: true,
			msg: 'Strip trailing punctuation'
		},
		{
			html: '<p>"http://example.com" xyz</p>',
			rangeOrSelection: new ve.Range( 2, 21 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 21 ),
			expectedOriginalRangeOrSelection: new ve.Range( 21 ),
			expectedData: function ( data, action ) {
				var i,
					a = action.getLinkAnnotation( 'http://example.com' );
				for ( i = 2; i < 20; i++ ) {
					data[ i ] = [ data[ i ], [ a.element ] ];
				}
			},
			undo: true,
			msg: 'Strip trailing quotes'
		},
		{
			html: '<p>http://example.comFoo bar baz</p>',
			rangeOrSelection: new ve.Range( 1, 19 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 1, 19 ),
			expectedData: function () {
				// No change, no link
			},
			msg: 'Don\'t link if followed by word characters'
		},
		{
			html: '<p>http://.) xyz</p>',
			rangeOrSelection: new ve.Range( 1, 10 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 1, 10 ),
			expectedData: function () {
				// No change, no link
			},
			msg: 'Don\'t link if stripping leaves bare protocol'
		}
	];

	cases.forEach( function ( caseItem ) {
		ve.test.utils.runActionTest(
			'link', assert, caseItem.html, false, caseItem.method, [], caseItem.rangeOrSelection, caseItem.msg,
			{
				expectedData: caseItem.expectedData,
				expectedRangeOrSelection: caseItem.expectedRangeOrSelection,
				expectedOriginalRangeOrSelection: caseItem.expectedOriginalRangeOrSelection,
				undo: caseItem.undo
			}
		);
	} );
} );
