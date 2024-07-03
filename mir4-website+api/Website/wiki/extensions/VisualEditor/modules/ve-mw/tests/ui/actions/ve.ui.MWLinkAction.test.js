/*!
 * VisualEditor UserInterface Actions MWLinkAction tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.ui.MWLinkAction' );

/* Tests */

QUnit.test( 'MW autolink', ( assert ) => {
	const cases = [
		{
			msg: 'Strip trailing punctuation (but not matched parens)',
			html: '<p><b>https://en.wikipedia.org/wiki/Red_(disambiguation) xyz</b></p>',
			rangeOrSelection: new ve.Range( 1, 51 ),
			method: 'autolinkUrl',
			expectedRangeOrSelection: new ve.Range( 51 ),
			expectedData: ( data, action ) => {
				const a = action.getLinkAnnotation( 'https://en.wikipedia.org/wiki/Red_(disambiguation)' );
				for ( let i = 1; i < 51; i++ ) {
					data[ i ][ 1 ].push( a.element );
				}
			}
		},
		{
			msg: 'Autolink valid RFC',
			html: '<p><b>RFC 1234 xyz</b></p>',
			rangeOrSelection: new ve.Range( 1, 9 ),
			method: 'autolinkMagicLink',
			expectedRangeOrSelection: new ve.Range( 3 ),
			expectedOriginalRangeOrSelection: new ve.Range( 9 ),
			expectedData: ( data ) => {
				data.splice( 1, 8, {
					type: 'link/mwMagic',
					attributes: {
						content: 'RFC 1234'
					},
					annotations: data[ 1 ][ 1 ]
				}, {
					type: '/link/mwMagic'
				} );
			},
			undo: true
		},
		{
			msg: 'Don\'t autolink invalid RFC',
			html: '<p><b>RFC 123x xyz</b></p>',
			rangeOrSelection: new ve.Range( 1, 9 ),
			method: 'autolinkMagicLink',
			expectedRangeOrSelection: new ve.Range( 1, 9 ),
			expectedData: () => {
				/* no change, no link */
			}
		},
		{
			msg: 'Autolink valid PMID',
			html: '<p><b>PMID 1234 xyz</b></p>',
			rangeOrSelection: new ve.Range( 1, 10 ),
			method: 'autolinkMagicLink',
			expectedRangeOrSelection: new ve.Range( 3 ),
			expectedOriginalRangeOrSelection: new ve.Range( 10 ),
			expectedData: ( data ) => {
				data.splice( 1, 9, {
					type: 'link/mwMagic',
					attributes: {
						content: 'PMID 1234'
					},
					annotations: data[ 1 ][ 1 ]
				}, {
					type: '/link/mwMagic'
				} );
			},
			undo: true
		},
		{
			msg: 'Don\'t autolink invalid PMID',
			html: '<p><b>PMID 123x xyz</b></p>',
			rangeOrSelection: new ve.Range( 1, 10 ),
			method: 'autolinkMagicLink',
			expectedRangeOrSelection: new ve.Range( 1, 10 ),
			expectedData: () => {
				/* no change, no link */
			}
		},
		{
			msg: 'Autolink valid ISBN',
			html: '<p><b>ISBN 978-0596517748 xyz</b></p>',
			rangeOrSelection: new ve.Range( 1, 20 ),
			method: 'autolinkMagicLink',
			expectedRangeOrSelection: new ve.Range( 3 ),
			expectedOriginalRangeOrSelection: new ve.Range( 20 ),
			expectedData: ( data ) => {
				data.splice( 1, 19, {
					type: 'link/mwMagic',
					attributes: {
						content: 'ISBN 978-0596517748'
					},
					annotations: data[ 1 ][ 1 ]
				}, {
					type: '/link/mwMagic'
				} );
			},
			undo: true
		},
		{
			msg: 'Don\'t autolink invalid ISBN',
			html: '<p><b>ISBN 978-059651774 xyz</b></p>',
			rangeOrSelection: new ve.Range( 1, 19 ),
			method: 'autolinkMagicLink',
			expectedRangeOrSelection: new ve.Range( 1, 19 ),
			expectedData: () => {
				/* no change, no link */
			}
		}
	];

	cases.forEach( ( caseItem ) => {
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
