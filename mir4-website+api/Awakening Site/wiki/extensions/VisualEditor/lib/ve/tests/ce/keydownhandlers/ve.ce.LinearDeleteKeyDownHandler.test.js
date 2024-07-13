/*!
 * VisualEditor ContentEditable linear delete key down handler tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.LinearDeleteKeyDownHandler', {
	// See https://github.com/platinumazure/eslint-plugin-qunit/issues/68
	// eslint-disable-next-line qunit/resolve-async
	beforeEach: function ( assert ) {
		var done = assert.async();
		return ve.init.platform.getInitializedPromise().then( done );
	}
} );

QUnit.test( 'special key down: linear backspace/delete', function ( assert ) {
	var done = assert.async(),
		promise = Promise.resolve(),
		noChange = function () {},
		emptyList = '<ul><li><p></p></li></ul>',
		blockAlien = '<div rel="ve:Alien"></div>',
		blockAliens = blockAlien + '<p>a</p>' + blockAlien,
		blockAliensEmptyParagraph = blockAlien + '<p></p>' + blockAlien,
		table = '<table><tr><td>a</td></tr></table>',
		tablesEmptyParagraph = table + '<p></p>' + table,
		link = '<p>foo <a href="#">bar</a> baz</p>',
		cases = [
			{
				rangeOrSelection: new ve.Range( 4 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 3, 1 );
				},
				expectedRangeOrSelection: new ve.Range( 3 ),
				expectedDefaultPrevented: [ false ],
				msg: 'Character deleted by backspace'
			},
			{
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 3, 1 );
				},
				expectedRangeOrSelection: new ve.Range( 3 ),
				expectedDefaultPrevented: [ false ],
				msg: 'Character deleted by delete'
			},
			{
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'SHIFT+DELETE' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 3 ),
				expectedDefaultPrevented: [ false ],
				msg: 'Shift delete does nothing (expected to cut)'
			},
			{
				rangeOrSelection: new ve.Range( 1, 4 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 1, 3 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Selection deleted by backspace'
			},
			{
				rangeOrSelection: new ve.Range( 1, 4 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 1, 3 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Selection deleted by delete'
			},
			{
				rangeOrSelection: new ve.Range( 4 ),
				keys: [ 'CTRL+BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 1, 3 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Whole word deleted by modified backspace'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				keys: [ 'CTRL+DELETE' ],
				expectedData: function ( data ) {
					data.splice( 1, 3 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Whole word deleted by modified delete'
			},
			{
				rangeOrSelection: new ve.Range( 56, 57 ),
				keys: [ 'DELETE', 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 55, 3 );
				},
				expectedRangeOrSelection: new ve.Range( 56 ),
				msg: 'Empty node deleted by delete; selection goes to nearest content offset'
			},
			{
				rangeOrSelection: new ve.Range( 41 ),
				keys: [ 'BACKSPACE' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 39, 41 ),
				msg: 'Inline focusable node selected but not deleted by backspace'
			},
			{
				rangeOrSelection: new ve.Range( 39 ),
				keys: [ 'DELETE' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 39, 41 ),
				msg: 'Inline focusable node selected but not deleted by delete'
			},
			{
				rangeOrSelection: new ve.Range( 39, 41 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 39, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 39 ),
				msg: 'Inline focusable node deleted if selected first'
			},
			{
				htmlOrDoc: blockAliens,
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'BACKSPACE' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 0, 2 ),
				msg: 'Block focusable node selected but not deleted by backspace'
			},
			{
				htmlOrDoc: blockAliens,
				rangeOrSelection: new ve.Range( 4 ),
				keys: [ 'DELETE' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 5, 7 ),
				msg: 'Block focusable node selected but not deleted by delete'
			},
			{
				htmlOrDoc: blockAliens,
				rangeOrSelection: new ve.Range( 0, 2 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 0, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Block focusable node deleted if selected first'
			},
			{
				htmlOrDoc: blockAliensEmptyParagraph,
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 0, 2 ),
				msg: 'Block focusable node selected and empty paragraph deleted'
			},
			{
				htmlOrDoc: blockAliensEmptyParagraph,
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 2, 4 ),
				msg: 'Block focusable node selected but not deleted by delete from empty paragraph'
			},
			{
				rangeOrSelection: new ve.Range( 38 ),
				keys: [ 'BACKSPACE' ],
				expectedData: noChange,
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 5, 37 ),
					fromCol: 0,
					fromRow: 0
				},
				msg: 'Table cell selected but not deleted by backspace'
			},
			{
				rangeOrSelection: new ve.Range( 4 ),
				keys: [ 'DELETE' ],
				expectedData: noChange,
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 5, 37 ),
					fromCol: 0,
					fromRow: 0
				},
				msg: 'Table cell selected but not deleted by delete'
			},
			{
				htmlOrDoc: tablesEmptyParagraph,
				rangeOrSelection: new ve.Range( 12 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 11, 2 );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 11 ),
					fromCol: 0,
					fromRow: 0
				},
				msg: 'Table selected but not deleted by backspace from empty paragraph'
			},
			{
				htmlOrDoc: tablesEmptyParagraph,
				rangeOrSelection: new ve.Range( 12 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 11, 2 );
				},
				expectedRangeOrSelection: {
					type: 'table',
					tableRange: new ve.Range( 11, 22 ),
					fromCol: 0,
					fromRow: 0
				},
				// TODO: Delete the empty paragraph instead
				msg: 'Table selected but not deleted by delete from empty paragraph'
			},
			{
				htmlOrDoc: '<p>a</p>' + emptyList + '<p>b</p>',
				rangeOrSelection: new ve.Range( 6 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 3, 6 );
				},
				expectedRangeOrSelection: new ve.Range( 4 ),
				msg: 'Empty list node deleted by delete from inside'
			},
			{
				htmlOrDoc: '<p>a</p>' + emptyList + '<p>b</p>',
				rangeOrSelection: new ve.Range( 6 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 3, 6 );
				},
				expectedRangeOrSelection: new ve.Range( 2 ),
				msg: 'Empty list node deleted by backspace from inside'
			},
			{
				htmlOrDoc: '<p>a</p>' + emptyList + '<p>b</p>',
				rangeOrSelection: new ve.Range( 2 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 3, 6 );
				},
				expectedRangeOrSelection: new ve.Range( 2 ),
				msg: 'Empty list node deleted by delete from before'
			},
			{
				htmlOrDoc: '<p>a</p>' + emptyList + '<p>b</p>',
				rangeOrSelection: new ve.Range( 10 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 3, 6 );
				},
				expectedRangeOrSelection: new ve.Range( 4 ),
				msg: 'Empty list node deleted by backspace from after'
			},
			{
				htmlOrDoc: '<ul><li><p></p>' + emptyList + '</li></ul>',
				rangeOrSelection: new ve.Range( 7 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 5 ),
				msg: 'Selection is not lost inside block slug after backspace'
			},
			{
				rangeOrSelection: new ve.Range( 0, 61 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice(
						0,
						61,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Backspace after select all spanning entire document creates empty paragraph'
			},
			{
				htmlOrDoc: emptyList + '<p>foo</p>',
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 0, 2 );
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'List at start of document unwrapped by backspace'
			},
			{
				htmlOrDoc: '<p>foo</p>' + emptyList,
				rangeOrSelection: new ve.Range( 8 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 5, 2 );
					data.splice( 7, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 6 ),
				msg: 'Empty list at end of document unwrapped by delete'
			},
			{
				htmlOrDoc: '<p>foo</p><ul><li><p>bar</p></li></ul>',
				rangeOrSelection: new ve.Range( 11 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 5, 2 );
					data.splice( 10, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 9 ),
				msg: 'Non-empty list at end of document unwrapped by delete'
			},
			{
				htmlOrDoc: '<p>foo</p><ul><li><p>bar</p></li><li><p>baz</p></li></ul>',
				rangeOrSelection: new ve.Range( 18 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					var paragraph = data.splice( 14, 5 );
					data.splice( 13, 2 ); // Remove the empty listItem
					data.splice.apply( data, [ 14, 0 ].concat( paragraph ) );
				},
				expectedRangeOrSelection: new ve.Range( 18 ),
				msg: 'Non-empty multi-item list at end of document unwrapped by delete'
			},
			{
				htmlOrDoc: '<ul><li><p>foo</p></li><li><p>bar</p></li></ul>',
				rangeOrSelection: new ve.Range( 6 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 6, 4 );
				},
				expectedRangeOrSelection: new ve.Range( 6 ),
				msg: 'List items merged by delete'
			},
			{
				htmlOrDoc: '<ul><li><p>foo</p></li><li><p>bar</p></li></ul>',
				rangeOrSelection: new ve.Range( 10 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 6, 4 );
				},
				expectedRangeOrSelection: new ve.Range( 6 ),
				msg: 'List items merged by backspace'
			},
			{
				htmlOrDoc: '<p>foo</p>',
				rangeOrSelection: new ve.Range( 4 ),
				keys: [ 'DELETE' ],
				expectedData: function () {
				},
				expectedRangeOrSelection: new ve.Range( 4 ),
				msg: 'Delete at end of last paragraph does nothing'
			},
			{
				htmlOrDoc: '<p>foo</p><p>bar</p><p></p>',
				rangeOrSelection: new ve.Range( 11 ),
				keys: [ 'DELETE' ],
				expectedData: function () {
				},
				expectedRangeOrSelection: new ve.Range( 11 ),
				msg: 'Delete at end of last empty paragraph does nothing'
			},
			{
				htmlOrDoc: '<div rel="ve:Alien">foo</div><p>bar</p>',
				rangeOrSelection: new ve.Range( 2 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function () {
				},
				expectedRangeOrSelection: new ve.Range( 0, 2 ),
				msg: 'Backspace after an alien just selects it'
			},
			{
				htmlOrDoc: '<p>bar</p><div rel="ve:Alien">foo</div>',
				rangeOrSelection: new ve.Range( 4 ),
				keys: [ 'DELETE' ],
				expectedData: function () {
				},
				expectedRangeOrSelection: new ve.Range( 5, 7 ),
				msg: 'Delete before an alien just selects it'
			},
			{
				htmlOrDoc: '<div rel="ve:Alien">foo</div><ul><li><p>bar</p></li></ul>',
				rangeOrSelection: new ve.Range( 5 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
					data.splice( 7, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 3 ),
				msg: 'List after an alien unwrapped by backspace'
			},
			{
				htmlOrDoc: '<p>baz</p><div rel="ve:Alien">foo</div><ul><li><p>bar</p></li></ul>',
				rangeOrSelection: new ve.Range( 10 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function () {
				},
				expectedRangeOrSelection: new ve.Range( 5, 7 ),
				// TODO: Make this more consistent with "List after an alien unwrapped by backspace"
				msg: 'List after an alien after content selects the alien'
			},
			{
				htmlOrDoc: '<p></p><div rel="ve:Alien">foo</div>',
				rangeOrSelection: new ve.Range( 2, 4 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Backspace with an alien selected deletes it'
			},
			{
				htmlOrDoc: '<p></p><div rel="ve:Alien">foo</div>',
				rangeOrSelection: new ve.Range( 2, 4 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Delete with an alien selected deletes it'
			},
			{
				htmlOrDoc: '<div rel="ve:Alien">foo</div><div rel="ve:Alien">foo</div>',
				rangeOrSelection: new ve.Range( 2, 4 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 0 ),
				msg: 'Backspace with an alien selected deletes it, with only aliens in the document'
			},
			{
				htmlOrDoc: '<div rel="ve:Alien">foo</div><div rel="ve:Alien">foo</div>',
				rangeOrSelection: new ve.Range( 2, 4 ),
				keys: [ 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 2, 2 );
				},
				expectedRangeOrSelection: new ve.Range( 0 ),
				msg: 'Delete with an alien selected deletes it, with only aliens in the document'
			},
			{
				htmlOrDoc: '<div rel="ve:Alien">foo</div>',
				rangeOrSelection: new ve.Range( 0, 2 ),
				keys: [ 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 0, 2,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Backspace with an alien selected deletes it and replaces it with a paragraph, when the alien is the entire document'
			},
			{
				htmlOrDoc: link,
				rangeOrSelection: new ve.Range( 8 ),
				keys: [ 'BACKSPACE' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 8 ),
				msg: 'Backspace from outside a link just activates the link'
			},
			{
				htmlOrDoc: link,
				rangeOrSelection: new ve.Range( 5 ),
				keys: [ 'DELETE' ],
				expectedData: function () {
				},
				expectedRangeOrSelection: new ve.Range( 5 ),
				msg: 'Delete from outside a link just activates the link'
			},
			{
				htmlOrDoc: link,
				rangeOrSelection: new ve.Range( 6 ),
				keys: [ 'BACKSPACE', 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 5, 1 );
				},
				expectedRangeOrSelection: new ve.Range( 5 ),
				expectedDefaultPrevented: [ false, true ],
				msg: 'Backspace from inside a link just de-activates the link'
			},
			{
				htmlOrDoc: link,
				rangeOrSelection: new ve.Range( 7 ),
				keys: [ 'DELETE', 'DELETE' ],
				expectedData: function ( data ) {
					data.splice( 7, 1 );
				},
				expectedRangeOrSelection: new ve.Range( 7 ),
				expectedDefaultPrevented: [ false, true ],
				msg: 'Delete from inside a link just de-activates the link'
			},
			{
				htmlOrDoc: link,
				rangeOrSelection: new ve.Range( 8 ),
				keys: [ 'BACKSPACE', 'BACKSPACE', 'BACKSPACE', 'BACKSPACE', 'BACKSPACE' ],
				expectedData: function ( data ) {
					data.splice( 5, 3 );
				},
				expectedRangeOrSelection: new ve.Range( 5 ),
				expectedDefaultPrevented: [ true, false, false, false, true ],
				msg: 'Deleting an empty link prevents default and just removes the insertionAnnotation'
			}
		];

	cases.forEach( function ( caseItem ) {
		promise = promise.then( function () {
			return ve.test.utils.runSurfaceHandleSpecialKeyTest( assert, caseItem );
		} );
	} );

	promise.finally( () => done() );
} );
