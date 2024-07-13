/*!
 * VisualEditor ContentEditable TextNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.TextNode' );

/* Tests */

QUnit.test( 'getAnnotatedHtml', function ( assert ) {
	var cases = [
		{
			data: [
				{ type: 'paragraph' },
				'a',
				'b',
				'c',
				{ type: '/paragraph' }
			],
			html: [ 'a', 'b', 'c' ]
		},
		{
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/bold' } ] ],
				[ 'b', [ { type: 'textStyle/bold' } ] ],
				[ 'c', [ { type: 'textStyle/bold' } ] ],
				{ type: '/paragraph' }
			],
			html: [
				[ 'a', [ { type: 'textStyle/bold' } ] ],
				[ 'b', [ { type: 'textStyle/bold' } ] ],
				[ 'c', [ { type: 'textStyle/bold' } ] ]
			]
		},
		{
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/bold' } ] ],
				'b',
				[ 'c', [ { type: 'textStyle/italic' } ] ],
				{ type: '/paragraph' }
			],
			html: [
				[ 'a', [ { type: 'textStyle/bold' } ] ],
				'b',
				[ 'c', [ { type: 'textStyle/italic' } ] ]
			]
		},
		{
			// [ ]
			data: [ { type: 'paragraph' }, ' ', { type: '/paragraph' } ],
			html: [ ' ' ]
		},
		{
			// [ ][ ][ ]
			data: [ { type: 'paragraph' }, ' ', ' ', ' ', { type: '/paragraph' } ],
			html: [ ' ', ' ', ' ' ]
		},
		{
			// [ ][ ][ ][A][ ][ ]
			data: [ { type: 'paragraph' }, ' ', ' ', ' ', 'A', ' ', ' ', { type: '/paragraph' } ],
			html: [ ' ', ' ', ' ', 'A', ' ', ' ' ]
		},
		{
			// [A][ ][A] with non-breaking space
			data: [ { type: 'paragraph' }, 'A', '\u00a0', 'A', { type: '/paragraph' } ],
			html: [ 'A', '\u00a0', 'A' ]
		},
		{
			data: [ { type: 'paragraph' }, '\n', 'A', '\n', 'B', '\n', { type: '/paragraph' } ],
			html: [ '\u21b5', 'A', '\u21b5', 'B', '\u21b5' ]
		},
		{
			data: [ { type: 'paragraph' }, '\t', 'A', '\t', 'B', '\t', { type: '/paragraph' } ],
			html: [ '\u279e', 'A', '\u279e', 'B', '\u279e' ]
		},
		{
			data: [ { type: 'preformatted' }, '\n', 'A', '\n', 'B', '\n', { type: '/preformatted' } ],
			html: [ '\n', 'A', '\n', 'B', '\n' ]
		},
		{
			data: [ { type: 'preformatted' }, '\t', 'A', '\t', 'B', '\t', { type: '/preformatted' } ],
			html: [ '\t', 'A', '\t', 'B', '\t' ]
		},
		{
			// [ ][ ][ ][A][ ][ ]
			data: [ { type: 'preformatted' }, ' ', ' ', ' ', 'A', ' ', ' ', { type: '/preformatted' } ],
			html: [ ' ', ' ', ' ', 'A', ' ', ' ' ]
		},
		{
			data: [ { type: 'paragraph' }, '&', '<', '>', '\'', '"', { type: '/paragraph' } ],
			html: [ '&', '<', '>', '\'', '"' ]
		}
	];

	var store = new ve.dm.HashValueStore();
	cases.forEach( function ( caseItem ) {
		var doc = new ve.dm.Document( ve.dm.example.preprocessAnnotations( caseItem.data, store ) );
		ve.dm.example.preprocessAnnotations( caseItem.html, store );
		assert.deepEqual(
			( new ve.ce.TextNode( doc.getDocumentNode().getChildren()[ 0 ].getChildren()[ 0 ] ) ).getAnnotatedHtml(),
			caseItem.html
		);
	} );
} );
