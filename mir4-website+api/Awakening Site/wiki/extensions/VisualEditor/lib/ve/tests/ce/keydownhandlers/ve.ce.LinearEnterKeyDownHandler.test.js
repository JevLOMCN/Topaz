/*!
 * VisualEditor ContentEditable linear enter down handler tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.LinearEnterKeyDownHandler', {
	// See https://github.com/platinumazure/eslint-plugin-qunit/issues/68
	// eslint-disable-next-line qunit/resolve-async
	beforeEach: function ( assert ) {
		var done = assert.async();
		return ve.init.platform.getInitializedPromise().then( done );
	}
} );

QUnit.test( 'special key down: linear enter', function ( assert ) {
	var done = assert.async(),
		noChange = function () {},
		promise = Promise.resolve(),
		emptyList = '<ul><li><p></p></li></ul>',
		alienDoc = ve.dm.example.createExampleDocument( 'alienData' ),
		cases = [
			{
				rangeOrSelection: new ve.Range( 57 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						57, 0,
						{ type: '/paragraph' },
						{ type: 'paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 59 ),
				msg: 'End of paragraph split by enter'
			},
			{
				rangeOrSelection: new ve.Range( 57 ),
				keys: [ 'CTRL+ENTER' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 57 ),
				msg: 'Ctrl + Enter does nothing (emits submit)'
			},
			{
				htmlOrDoc: alienDoc,
				rangeOrSelection: new ve.Range( 0, 2 ),
				keys: [ 'ENTER' ],
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 0, 2 ),
				msg: 'Enter does nothing on focusable (executes command)'
			},
			{
				rangeOrSelection: new ve.Range( 57 ),
				keys: [ 'ENTER' ],
				htmlOrDoc: ( function () {
					var view = ve.test.utils.createSurfaceViewFromDocument( ve.dm.example.createExampleDocument() );
					view.surface.isMultiline = function () {
						return false;
					};
					return view;
				}() ),
				expectedData: noChange,
				expectedRangeOrSelection: new ve.Range( 57 ),
				msg: 'Enter does nothing in single line mode'
			},
			{
				rangeOrSelection: new ve.Range( 57 ),
				keys: [ 'SHIFT+ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						57, 0,
						{ type: '/paragraph' },
						{ type: 'paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 59 ),
				msg: 'End of paragraph split by shift+enter'
			},
			{
				rangeOrSelection: new ve.Range( 56 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						56, 0,
						{ type: '/paragraph' },
						{ type: 'paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 58 ),
				msg: 'Start of paragraph split by enter'
			},
			{
				rangeOrSelection: new ve.Range( 39 ),
				keys: [ 'SHIFT+ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						39, 0, '\n'
					);
				},
				expectedRangeOrSelection: new ve.Range( 40 ),
				msg: 'Shift+enter in pre adds a linebreak character'
			},
			{
				rangeOrSelection: new ve.Range( 38 ),
				keys: [ 'SHIFT+ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						38, 0, '\n'
					);
				},
				expectedRangeOrSelection: new ve.Range( 39 ),
				msg: 'Shift+enter at start of pre adds a linebreak character'
			},
			{
				rangeOrSelection: new ve.Range( 42 ),
				keys: [ 'SHIFT+ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						42, 0, '\n'
					);
				},
				expectedRangeOrSelection: new ve.Range( 43 ),
				msg: 'Shift+enter at end of pre adds a linebreak character'
			},
			{
				rangeOrSelection: new ve.Range( 39 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						39, 0,
						{ type: '/preformatted' },
						{ type: 'preformatted' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 41 ),
				msg: 'enter in pre splits it'
			},
			{
				rangeOrSelection: new ve.Range( 38 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						37, 0,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 40 ),
				msg: 'enter at start of pre adds a paragraph'
			},
			{
				rangeOrSelection: new ve.Range( 42 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						43, 0,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 44 ),
				msg: 'enter at end of pre adds a paragraph'
			},
			{
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						3, 0,
						{ type: '/heading' },
						{ type: 'heading', attributes: { level: 1 } }
					);
				},
				expectedRangeOrSelection: new ve.Range( 5 ),
				msg: 'Heading split by enter'
			},
			{
				rangeOrSelection: new ve.Range( 2, 3 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						2, 1,
						{ type: '/heading' },
						{ type: 'heading', attributes: { level: 1 } }
					);
				},
				expectedRangeOrSelection: new ve.Range( 4 ),
				msg: 'Selection in heading removed, then split by enter'
			},
			{
				rangeOrSelection: new ve.Range( 1 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						0, 0,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 3 ),
				msg: 'Start of heading split into a plain paragraph'
			},
			{
				rangeOrSelection: new ve.Range( 4 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						5, 0,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 6 ),
				msg: 'End of heading split into a plain paragraph'
			},
			{
				rangeOrSelection: new ve.Range( 16 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						16, 0,
						{ type: '/paragraph' },
						{ type: '/listItem' },
						{ type: 'listItem' },
						{ type: 'paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 20 ),
				msg: 'List item split by enter'
			},
			{
				rangeOrSelection: new ve.Range( 16 ),
				keys: [ 'SHIFT+ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						16, 0,
						{ type: '/paragraph' },
						{ type: 'paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 18 ),
				msg: 'List item not split by shift+enter'
			},
			{
				rangeOrSelection: new ve.Range( 30 ),
				keys: [ 'ENTER', 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						33, 0,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 34 ),
				msg: 'Two enters breaks out of a list and starts a new paragraph'
			},
			{
				rangeOrSelection: new ve.Range( 21 ),
				keys: [ 'ENTER', 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						24, 0,
						{ type: '/listItem' },
						{ type: 'listItem' },
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 27 ),
				msg: 'Two enters in nested list breaks out of inner list and starts a new list item'
			},
			{
				htmlOrDoc: '<p>foo</p>' + emptyList + '<p>bar</p>',
				rangeOrSelection: new ve.Range( 8 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice( 5, 6 );
				},
				expectedRangeOrSelection: new ve.Range( 6 ),
				msg: 'Enter in an empty list destroys it and moves to next paragraph'
			},
			{
				htmlOrDoc: '<p>foo</p>' + emptyList,
				rangeOrSelection: new ve.Range( 8 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice( 5, 6 );
				},
				expectedRangeOrSelection: new ve.Range( 4 ),
				msg: 'Enter in an empty list at end of document destroys it and moves to previous paragraph'
			},
			{
				htmlOrDoc: emptyList + '<p>bar</p>',
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice( 0, 6 );
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Enter in an empty list at start of document destroys it and moves to next paragraph'
			},
			{
				htmlOrDoc: emptyList,
				rangeOrSelection: new ve.Range( 3 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						0, 6,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 1 ),
				msg: 'Enter in an empty list with no adjacent content destroys it and creates a paragraph'
			},
			{
				htmlOrDoc: '<p>foo</p><ul><li>bar' + emptyList + '</li></ul><p>baz</p>',
				rangeOrSelection: new ve.Range( 15 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						12, 6,
						{ type: '/listItem' }, { type: 'listItem' }, { type: 'paragraph' }, { type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 15 ),
				msg: 'Enter in a completely empty nested list destroys it and adds a new list item to the parent'
			},
			{
				htmlOrDoc: '<div rel="ve:Alien">Bar</div><div rel="ve:Alien">Baz</div>',
				rangeOrSelection: new ve.Range( 2 ),
				keys: [ 'ENTER' ],
				expectedData: function ( data ) {
					data.splice( 2, 0, { type: 'paragraph' }, { type: '/paragraph' } );
				},
				expectedRangeOrSelection: new ve.Range( 3 ),
				// This should really result in a slug being focused, but in some
				// browsers an observed selection can end up here.
				msg: 'Enter between two block nodes (and somehow not in the slug) creates a new paragraph'
			},
			{
				htmlOrDoc: '<blockquote><p>Foo</p></blockquote>',
				rangeOrSelection: new ve.Range( 5 ),
				keys: [ 'ENTER', 'ENTER' ],
				expectedData: function ( data ) {
					data.splice(
						7, 0,
						{ type: 'paragraph' },
						{ type: '/paragraph' }
					);
				},
				expectedRangeOrSelection: new ve.Range( 8 ),
				msg: 'Two enters at end of <blockquote> breaks out into paragraph below'
			}

		];

	cases.forEach( function ( caseItem ) {
		promise = promise.then( function () {
			return ve.test.utils.runSurfaceHandleSpecialKeyTest( assert, caseItem );
		} );
	} );

	promise.finally( () => done() );
} );
