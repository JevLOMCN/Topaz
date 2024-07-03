/*!
 * VisualEditor DataModel Transaction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.TransactionBuilder' );

/* Helper methods */

ve.test.utils.runTransactionBuilderTests = function ( assert, cases ) {
	for ( var msg in cases ) {
		var txBuilder = new ve.dm.TransactionBuilder();
		for ( var i = 0; i < cases[ msg ].calls.length; i++ ) {
			txBuilder[ cases[ msg ].calls[ i ][ 0 ] ].apply( txBuilder, cases[ msg ].calls[ i ].slice( 1 ) );
		}
		assert.deepEqualWithDomElements( txBuilder.getTransaction().getOperations(), cases[ msg ].ops, msg + ': operations match' );
	}
};

ve.test.utils.runTransactionConstructorTests = function ( assert, constructor, cases, testRange ) {
	for ( var msg in cases ) {
		var doc = cases[ msg ].args[ 0 ];
		var args = cases[ msg ].args;
		if ( cases[ msg ].ops ) {
			var tx = constructor.apply( null, args );
			assert.equalLinearDataWithDom( doc.getStore(), tx.getOperations(), cases[ msg ].ops, msg + ': operations match' );
			if ( testRange ) {
				assert.equalRange(
					tx.getModifiedRange( doc ),
					cases[ msg ].range || new ve.Range( args[ 1 ], args[ 1 ] + args[ 2 ].length ),
					msg + ': range matches'
				);
			}
		} else if ( cases[ msg ].exception ) {
			// eslint-disable-next-line no-loop-func
			assert.throws( function () {
				constructor.apply( ve.dm.Transaction, args );
			}, cases[ msg ].exception, msg + ': throw exception' );
		}
	}
};

/* Tests */

QUnit.test( 'newFromInsertion', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		isolationDoc = ve.dm.example.createExampleDocument( 'isolationData' ),
		complexTableDoc = ve.dm.example.createExampleDocument( 'complexTable' ),
		doc2 = new ve.dm.Document(
			ve.dm.example.preprocessAnnotations( [ { type: 'paragraph' }, { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ] )
		),
		doc3 = new ve.dm.Document(
			ve.dm.example.preprocessAnnotations( [ { type: 'paragraph' }, 'F', 'o', 'o', { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ] )
		),
		cases = {
			'paragraph before first element': {
				args: [ doc, 0, [ { type: 'paragraph' }, '1', { type: '/paragraph' } ] ],
				ops: [
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, '1', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 63 }
				]
			},
			'paragraph after last element': {
				args: [ doc, 61, [ { type: 'paragraph' }, '1', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 61 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, '1', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 2 }
				]
			},
			'split paragraph': {
				args: [ doc, 10, [ '1', { type: '/paragraph' }, { type: 'paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 10 },
					{
						type: 'replace',
						remove: [],
						insert: [ '1', { type: '/paragraph' }, { type: 'paragraph' } ]
					},
					{ type: 'retain', length: 53 }
				]
			},
			'paragraph inside a heading closes and reopens heading': {
				args: [ doc, 2, [ { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: '/heading' }, { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' }, { type: 'heading', attributes: { level: 1 } } ],
						insertedDataOffset: 1,
						insertedDataLength: 5
					},
					{ type: 'retain', length: 61 }
				],
				range: new ve.Range( 3, 8 )
			},
			'paragraph inside a list moves in front of list': {
				args: [ doc, 13, [ { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 12 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 51 }
				],
				range: new ve.Range( 12, 17 )
			},
			'tableCell inside the document is wrapped in a table, tableSection and tableRow': {
				args: [ doc, 43, [ { type: 'tableCell', attributes: { style: 'data' } }, { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' }, { type: '/tableCell' } ] ],
				ops: [
					{ type: 'retain', length: 43 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'table' }, { type: 'tableSection', attributes: { style: 'body' } }, { type: 'tableRow' }, { type: 'tableCell', attributes: { style: 'data' } }, { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' }, { type: '/tableCell' }, { type: '/tableRow' }, { type: '/tableSection' }, { type: '/table' } ],
						insertedDataOffset: 3,
						insertedDataLength: 7
					},
					{ type: 'retain', length: 20 }
				],
				range: new ve.Range( 46, 53 )
			},
			'tableCell inside a paragraph is wrapped in a table, tableSection and tableRow and moves outside of paragraph': {
				args: [ doc, 52, [ { type: 'tableCell', attributes: { style: 'data' } }, { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' }, { type: '/tableCell' } ] ],
				ops: [
					{ type: 'retain', length: 53 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'table' }, { type: 'tableSection', attributes: { style: 'body' } }, { type: 'tableRow' }, { type: 'tableCell', attributes: { style: 'data' } }, { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' }, { type: '/tableCell' }, { type: '/tableRow' }, { type: '/tableSection' }, { type: '/table' } ],
						insertedDataOffset: 3,
						insertedDataLength: 7
					},
					{ type: 'retain', length: 10 }
				],
				range: new ve.Range( 56, 63 )
			},
			'text at a structural location in the document is wrapped in a paragraph': {
				args: [ doc, 0, [ 'F', 'O', 'O' ] ],
				ops: [
					{
						type: 'replace',
						remove: [],
						insert: [
							{ type: 'paragraph', internal: { generated: 'wrapper' } },
							'F', 'O', 'O',
							{ type: '/paragraph' }
						],
						insertedDataOffset: 1,
						insertedDataLength: 3
					},
					{ type: 'retain', length: 63 }
				],
				range: new ve.Range( 1, 4 )
			},
			'text inside a paragraph is not wrapped in a paragraph': {
				args: [ doc, 16, [ 'F', 'O', 'O' ] ],
				ops: [
					{ type: 'retain', length: 16 },
					{
						type: 'replace',
						remove: [],
						insert: [ 'F', 'O', 'O' ]
					},
					{ type: 'retain', length: 47 }
				]
			},
			'text inside a heading is not wrapped in a paragraph': {
				args: [ doc, 2, [ 'F', 'O', 'O' ] ],
				ops: [
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [],
						insert: [ 'F', 'O', 'O' ]
					},
					{ type: 'retain', length: 61 }
				]
			},
			'paragraph in the middle of an insertion is balanced': {
				args: [ doc, 16, [ 'F', { type: 'paragraph' }, 'O', { type: '/paragraph' }, 'O' ] ],
				ops: [
					{ type: 'retain', length: 16 },
					{
						type: 'replace',
						remove: [],
						insert: [
							'F', { type: '/paragraph' },
							{ type: 'paragraph' }, 'O', { type: '/paragraph' },
							{ type: 'paragraph', internal: { generated: 'wrapper' } }, 'O'
						]
					},
					{ type: 'retain', length: 47 }
				],
				range: new ve.Range( 16, 23 )
			},
			'text inside a tableSection moves all the way to the end of the table and is wrapped in a paragraph': {
				args: [ doc, 34, [ 'F', 'O', 'O' ] ],
				ops: [
					{ type: 'retain', length: 37 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph', internal: { generated: 'wrapper' } }, 'F', 'O', 'O', { type: '/paragraph' } ],
						insertedDataOffset: 1,
						insertedDataLength: 3
					},
					{ type: 'retain', length: 26 }
				],
				range: new ve.Range( 38, 41 )
			},
			'insert two complete paragraphs into start of paragraph moves insertion point left': {
				args: [ doc, 10, [ { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 9 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 54 }
				],
				range: new ve.Range( 9, 19 )
			},
			'insert text, close paragraph and open heading into end of paragraph moves insertion point right': {
				args: [ doc, 57, [ 'F', 'O', 'O', { type: '/paragraph' }, { type: 'heading', attributes: { level: 1 } }, 'B', 'A', 'R' ] ],
				ops: [
					{ type: 'retain', length: 58 },
					{
						type: 'replace',
						remove: [],
						insert: [
							{ type: 'paragraph', internal: { generated: 'wrapper' } }, 'F', 'O', 'O', { type: '/paragraph' },
							{ type: 'heading', attributes: { level: 1 } }, 'B', 'A', 'R', { type: '/heading' }
						],
						insertedDataOffset: 1,
						insertedDataLength: 8
					},
					{ type: 'retain', length: 5 }
				],
				range: new ve.Range( 59, 67 )
			},
			'insert heading and incomplete paragraph into heading': {
				args: [ doc, 2, [ { type: 'heading', attributes: { level: 1 } }, 'F', 'O', 'O', { type: '/heading' }, { type: 'paragraph' }, 'B', 'A', 'R' ] ],
				ops: [
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [],
						insert: [
							{ type: '/heading' }, { type: 'heading', attributes: { level: 1 } }, 'F', 'O', 'O', { type: '/heading' },
							{ type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' },
							{ type: 'heading', attributes: { level: 1 } }
						],
						insertedDataOffset: 1,
						insertedDataLength: 9
					},
					{ type: 'retain', length: 61 }
				],
				range: new ve.Range( 3, 12 )
			},
			'inserting two paragraphs into a document with just an empty paragraph': {
				args: [ doc2, 1, [ 'F', 'O', 'O', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'R' ] ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [],
						insert: [ 'F', 'O', 'O', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'R' ]
					},
					{ type: 'retain', length: 3 }
				]
			},
			'inserting three paragraphs into a document with just an empty paragraph': {
				args: [ doc2, 1, [ 'F', 'O', 'O', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'Z' ] ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [],
						insert: [ 'F', 'O', 'O', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'Z' ]
					},
					{ type: 'retain', length: 3 }
				]
			},
			'inserting one paragraph into empty paragraph replaces it': {
				args: [ doc2, 1, [ { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' } ] ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' }, { type: '/paragraph' } ],
						insert: [ { type: 'paragraph' }, 'F', 'O', 'O', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 2 }
				],
				range: new ve.Range( 0, 5 )
			},
			'inserting paragraph at end of paragraph moves insertion point forward': {
				args: [ doc3, 4, [ { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 5 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 2 }
				],
				range: new ve.Range( 5, 10 )
			},
			'inserting paragraph into middle of paragraph splits paragraph': {
				args: [ doc3, 2, [ { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: '/paragraph' }, { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' }, { type: 'paragraph' } ],
						insertedDataOffset: 1,
						insertedDataLength: 5
					},
					{ type: 'retain', length: 5 }
				],
				range: new ve.Range( 3, 8 )
			},
			'inserting paragraph into middle of list splits list': {
				args: [ isolationDoc, 11, [ { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 11 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: '/list' }, { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' }, { type: 'list', attributes: { style: 'bullet' } } ],
						insertedDataOffset: 1,
						insertedDataLength: 5
					},
					{ type: 'retain', length: 235 }
				],
				range: new ve.Range( 12, 17 )
			},
			'inserting paragraph between table cells splits table, tableSection and tableRow': {
				args: [ complexTableDoc, 40, [ { type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' } ] ],
				ops: [
					{ type: 'retain', length: 40 },
					{
						type: 'replace',
						remove: [],
						insert: [
							{ type: '/tableRow' }, { type: '/tableSection' }, { type: '/table' },
							{ type: 'paragraph' }, 'B', 'A', 'R', { type: '/paragraph' },
							{ type: 'table' }, { type: 'tableSection', attributes: { style: 'body' } }, { type: 'tableRow' }
						],
						insertedDataOffset: 3,
						insertedDataLength: 5
					},
					{ type: 'retain', length: 13 }
				],
				range: new ve.Range( 43, 48 )
			}
			// TODO test cases for unclosed openings
			// TODO test cases for (currently failing) unopened closings use case
			// TODO analyze other possible cases (substrings of linmod data)
		};

	for ( var key in cases ) {
		for ( var i = 0; i < cases[ key ].ops.length; i++ ) {
			if ( cases[ key ].ops[ i ].remove ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].remove, doc.getStore() );
			}
			if ( cases[ key ].ops[ i ].insert ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].insert, doc.getStore() );
			}
		}
	}
	ve.test.utils.runTransactionConstructorTests( assert, ve.dm.TransactionBuilder.static.newFromInsertion, cases, true );
} );

QUnit.test( 'newFromRemoval', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument( 'data' ),
		alienDoc = ve.dm.example.createExampleDocument( 'alienData' ),
		alienWithEmptyDoc = ve.dm.example.createExampleDocument( 'alienWithEmptyData' ),
		internalDoc = ve.dm.example.createExampleDocument( 'internalData' ),
		cases = {
			'content in first element': {
				args: [ doc, new ve.Range( 1, 3 ) ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [
							'a',
							[ 'b', [ ve.dm.example.bold ] ]
						],
						insert: []
					},
					{ type: 'retain', length: 60 }
				]
			},
			'content in last element': {
				args: [ doc, new ve.Range( 59, 60 ) ],
				ops: [
					{ type: 'retain', length: 59 },
					{
						type: 'replace',
						remove: [ 'm' ],
						insert: []
					},
					{ type: 'retain', length: 3 }
				]
			},
			'first element': {
				args: [ doc, new ve.Range( 0, 5 ) ],
				ops: [
					{
						type: 'replace',
						remove: [
							{ type: 'heading', attributes: { level: 1 } },
							'a',
							[ 'b', [ ve.dm.example.bold ] ],
							[ 'c', [ ve.dm.example.italic ] ],
							{ type: '/heading' }
						],
						insert: []
					},
					{ type: 'retain', length: 58 }
				]
			},
			'middle element with image': {
				args: [ doc, new ve.Range( 38, 42 ) ],
				ops: [
					{ type: 'retain', length: 38 },
					{
						type: 'replace',
						remove: [
							'h',
							ve.dm.example.image.data,
							{ type: '/inlineImage' },
							'i'
						],
						insert: []
					},
					{ type: 'retain', length: 21 }
				]
			},
			'extra openings': {
				args: [ doc, new ve.Range( 0, 7 ) ],
				ops: [
					{
						type: 'replace',
						remove: [
							{ type: 'heading', attributes: { level: 1 } },
							'a',
							[ 'b', [ ve.dm.example.bold ] ],
							[ 'c', [ ve.dm.example.italic ] ],
							{ type: '/heading' }
						],
						insert: []
					},
					{ type: 'retain', length: 58 }
				]
			},
			'last element': {
				args: [ doc, new ve.Range( 58, 61 ) ],
				ops: [
					{ type: 'retain', length: 58 },
					{
						type: 'replace',
						remove: [ { type: 'paragraph' }, 'm', { type: '/paragraph' } ],
						insert: []
					},
					{ type: 'retain', length: 2 }
				]
			},
			'extra closings': {
				args: [ doc, new ve.Range( 31, 39 ) ],
				ops: [
					{ type: 'retain', length: 38 },
					{
						type: 'replace',
						remove: [ 'h' ],
						insert: []
					},
					{ type: 'retain', length: 24 }
				]
			},
			'merge last two elements': {
				args: [ doc, new ve.Range( 57, 59 ) ],
				ops: [
					{ type: 'retain', length: 57 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' }, { type: 'paragraph' } ],
						insert: []
					},
					{ type: 'retain', length: 4 }
				]
			},
			'strip out of paragraph in tableCell and paragraph in listItem': {
				args: [ doc, new ve.Range( 10, 16 ) ],
				ops: [
					{ type: 'retain', length: 10 },
					{
						type: 'replace',
						remove: [ 'd' ],
						insert: []
					},
					{ type: 'retain', length: 4 },
					{
						type: 'replace',
						remove: [ 'e' ],
						insert: []
					},
					{ type: 'retain', length: 47 }
				]
			},
			'over first alien into paragraph': {
				args: [ alienDoc, new ve.Range( 0, 4 ) ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'alienBlock', originalDomElements: $.parseHTML( '<foobar />' ) }, { type: '/alienBlock' } ],
						insert: []
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'a' ],
						insert: []
					},
					{ type: 'retain', length: alienDoc.data.getLength() - 4 }
				]
			},
			'out of paragraph over last alien': {
				args: [ alienDoc, new ve.Range( 6, 10 ) ],
				ops: [
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: [ 'b' ],
						insert: []
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ { type: 'alienBlock', originalDomElements: $.parseHTML( '<foobar />' ) }, { type: '/alienBlock' } ],
						insert: []
					},
					{ type: 'retain', length: alienDoc.data.getLength() - 10 }
				]
			},
			'out of empty paragraph over last alien': {
				args: [ alienWithEmptyDoc, new ve.Range( 1, 6 ) ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' }, { type: 'paragraph' }, 'a', { type: 'alienInline', originalDomElements: $.parseHTML( '<foobar />' ) }, { type: '/alienInline' } ],
						insert: []
					},
					{ type: 'retain', length: alienWithEmptyDoc.data.getLength() - 6 }
				]
			},
			'merging two paragraphs inside definitionListItems': {
				args: [ doc, new ve.Range( 47, 51 ) ],
				ops: [
					{ type: 'retain', length: 47 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' }, { type: '/definitionListItem' }, { type: 'definitionListItem', attributes: { style: 'definition' } }, { type: 'paragraph' } ],
						insert: []
					},
					{ type: 'retain', length: 12 }
				]
			},
			'merging two paragraphs while also deleting some content': {
				args: [ doc, new ve.Range( 56, 59 ) ],
				ops: [
					{ type: 'retain', length: 56 },
					{
						type: 'replace',
						remove: [ 'l', { type: '/paragraph' }, { type: 'paragraph' } ],
						insert: []
					},
					{ type: 'retain', length: 4 }
				]
			},
			'removing from a heading into a paragraph': {
				args: [ doc, new ve.Range( 2, 57 ) ],
				ops: [
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: doc.getData().slice( 2, 4 ),
						insert: []
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: doc.getData().slice( 5, 55 ),
						insert: []
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'l' ],
						insert: []
					},
					{ type: 'retain', length: 6 }
				]
			},
			'removing content from a paragraph in the middle': {
				args: [ doc, new ve.Range( 56, 57 ) ],
				ops: [
					{ type: 'retain', length: 56 },
					{
						type: 'replace',
						remove: [ 'l' ],
						insert: []
					},
					{ type: 'retain', length: 6 }
				]
			},
			'selection including internal nodes doesn\'t remove them': {
				args: [ internalDoc, new ve.Range( 2, 24 ) ],
				ops: [
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [
							'o', 'o',
							{ type: '/paragraph' }
						],
						insert: []
					},
					{ type: 'retain', length: 16 },
					{
						type: 'replace',
						remove: [
							{ type: 'paragraph' },
							'Q', 'u'
						],
						insert: []
					},
					{ type: 'retain', length: 3 }
				]
			},
			'selection ending with internal nodes': {
				args: [ internalDoc, new ve.Range( 2, 21 ) ],
				ops: [
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [
							'o', 'o'
						],
						insert: []
					},
					{ type: 'retain', length: 23 }
				]
			},
			'selection starting with internal nodes': {
				args: [ internalDoc, new ve.Range( 5, 24 ) ],
				ops: [
					{ type: 'retain', length: 22 },
					{
						type: 'replace',
						remove: [
							'Q', 'u'
						],
						insert: []
					},
					{ type: 'retain', length: 3 }
				]
			},
			'selection of just internal nodes returns a no-op transaction': {
				args: [ internalDoc, new ve.Range( 5, 21 ) ],
				ops: [
					{ type: 'retain', length: 27 }
				]
			}
		};

	for ( var key in cases ) {
		for ( var i = 0; i < cases[ key ].ops.length; i++ ) {
			var store = cases[ key ].args[ 0 ].getStore();
			if ( cases[ key ].ops[ i ].remove ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].remove, store );
			}
			if ( cases[ key ].ops[ i ].insert ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].insert, store );
			}
		}
	}
	ve.test.utils.runTransactionConstructorTests( assert, ve.dm.TransactionBuilder.static.newFromRemoval, cases );
} );

QUnit.test( 'newFromReplacement', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		metaDoc = ve.dm.example.createExampleDocument( 'withMeta' ),

		cases = {
			'replace, preserving metadata': {
				args: [ metaDoc, new ve.Range( 6, 21 ), [ 'X', 'Y' ] ],
				ops: [
					{ type: 'retain', length: 4 },
					{
						type: 'replace',
						insert: metaDoc.getData( new ve.Range( 6, 21 ) )
							.filter( function ( item ) {
								return item.type === 'alienMeta' ||
									item.type === '/alienMeta';
							} ),
						remove: []
					},
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						insert: [ 'X', 'Y' ],
						remove: metaDoc.getData( new ve.Range( 6, 21 ) )
					},
					{ type: 'retain', length: 14 }
				]
			},
			'replace, removing metadata': {
				args: [ metaDoc, new ve.Range( 6, 21 ), [ 'X', 'Y' ], true ],
				ops: [
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						insert: [ 'X', 'Y' ],
						remove: metaDoc.getData( new ve.Range( 6, 21 ) )
					},
					{ type: 'retain', length: 14 }
				]
			}
		};

	for ( var key in cases ) {
		for ( var i = 0; i < cases[ key ].ops.length; i++ ) {
			if ( cases[ key ].ops[ i ].remove ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].remove, doc.getStore() );
			}
			if ( cases[ key ].ops[ i ].insert ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].insert, doc.getStore() );
			}
		}
	}
	ve.test.utils.runTransactionConstructorTests( assert, ve.dm.TransactionBuilder.static.newFromReplacement, cases, false );
} );

QUnit.test( 'newFromDocumentInsertion', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument( 'internalData' ),
		bold = ve.dm.example.createAnnotation( ve.dm.example.bold ),
		whee = [ { type: 'paragraph' }, 'W', 'h', 'e', 'e', { type: '/paragraph' } ],
		wheeItem = [ { type: 'internalItem' } ].concat( whee ).concat( [ { type: '/internalItem' } ] ),
		cases = [
			{
				msg: 'simple insertion',
				doc: 'internalData',
				offset: 7,
				range: new ve.Range( 7, 12 ),
				modify: function ( newDoc ) {
					// Change "Bar" to "Bazaar"
					newDoc.commit( ve.dm.TransactionBuilder.static.newFromInsertion(
						newDoc, 3, [ 'z', 'a', 'a' ]
					) );
				},
				removalOps: [
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 7, 12 ) ),
						insert: []
					},
					{ type: 'retain', length: 15 }
				],
				expectedOps: [
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 6, 7 ) )
							.concat( doc.getData( new ve.Range( 12, 20 ) ) ),
						insert: doc.getData( new ve.Range( 6, 10 ) )
							.concat( [ 'z', 'a', 'a' ] )
							.concat( doc.getData( new ve.Range( 10, 20 ) ) )
					},
					{ type: 'retain', length: 7 }
				]
			},
			{
				msg: 'simple annotation',
				doc: 'internalData',
				offset: 14,
				range: new ve.Range( 14, 19 ),
				modify: function ( newDoc ) {
					// Bold the first two characters
					newDoc.commit( ve.dm.TransactionBuilder.static.newFromAnnotation(
						newDoc, new ve.Range( 1, 3 ), 'set', bold
					) );
				},
				removalOps: [
					{ type: 'retain', length: 14 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 14, 19 ) ),
						insert: []
					},
					{ type: 'retain', length: 8 }
				],
				expectedOps: [
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 6, 14 ) )
							.concat( doc.getData( new ve.Range( 19, 20 ) ) ),
						insert: doc.getData( new ve.Range( 6, 15 ) )
							.concat( [ [ doc.data.getData( 15 ), [ ve.dm.example.boldHash ] ] ] )
							.concat( [ [ doc.data.getData( 16 ), [ ve.dm.example.boldHash ] ] ] )
							.concat( doc.getData( new ve.Range( 17, 20 ) ) )
					},
					{ type: 'retain', length: 7 }
				],
				expectedStoreItems: {
					h49981eab0f8056ff: bold
				}
			},
			{
				msg: 'insertion into internal list',
				doc: 'internalData',
				offset: 21,
				range: new ve.Range( 21, 27 ),
				modify: function ( newDoc ) {
					var insertion = newDoc.internalList.getItemInsertion( 'test', 'whee', whee );
					newDoc.commit( insertion.transaction );
				},
				removalOps: [
					{ type: 'retain', length: 21 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 21, 27 ) ),
						insert: []
					}
				],
				expectedOps: [
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 6, 20 ) ),
						insert: doc.getData( new ve.Range( 6, 20 ) ).concat( wheeItem )
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [],
						insert: doc.getData( new ve.Range( 21, 27 ) )
					}
				]
			},
			{
				msg: 'change in internal list',
				doc: 'internalData',
				offset: 21,
				range: new ve.Range( 21, 27 ),
				modify: function ( newDoc ) {
					newDoc.commit( ve.dm.TransactionBuilder.static.newFromInsertion(
						newDoc, 12, [ '!', '!', '!' ]
					) );
				},
				removalOps: [
					{ type: 'retain', length: 21 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 21, 27 ) ),
						insert: []
					}
				],
				expectedOps: [
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 6, 20 ) ),
						insert: doc.getData( new ve.Range( 6, 11 ) )
							.concat( [ '!', '!', '!' ] )
							.concat( doc.getData( new ve.Range( 11, 20 ) ) )
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [],
						insert: doc.getData( new ve.Range( 21, 27 ) )
					}
				]
			},
			{
				msg: 'insertion into internal list from slice within internal list',
				doc: 'internalData',
				offset: 7,
				range: new ve.Range( 7, 12 ),
				modify: function ( newDoc ) {
					var insertion = newDoc.internalList.getItemInsertion( 'test', 'whee', whee );
					newDoc.commit( insertion.transaction );
				},
				removalOps: [
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 7, 12 ) ),
						insert: []
					},
					{ type: 'retain', length: 15 }
				],
				expectedOps: [
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 6, 7 ) )
							.concat( doc.getData( new ve.Range( 12, 20 ) ) ),
						insert: doc.getData( new ve.Range( 6, 20 ) ).concat( wheeItem )
					},
					{ type: 'retain', length: 7 }
				]
			},
			{
				msg: 'insertion from unrelated document',
				doc: 'internalData',
				offset: 0,
				newDocData: [
					{ type: 'paragraph' }, 'F', 'o', 'o', { type: '/paragraph' },
					{ type: 'internalList' }, { type: '/internalList' }
				],
				removalOps: [],
				expectedOps: [
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, 'F', 'o', 'o', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 6, 20 ) ),
						insert: doc.getData( new ve.Range( 6, 20 ) )
					},
					{ type: 'retain', length: 7 }
				]
			},
			{
				msg: 'insertion from unrelated document with annotation',
				doc: 'internalData',
				offset: 0,
				newDocData: [
					{ type: 'paragraph' }, 'F',
					[ 'o', [ ve.dm.example.bold ] ],
					'o', { type: '/paragraph' },
					{ type: 'internalList' }, { type: '/internalList' }
				],
				removalOps: [],
				expectedOps: [
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, 'F', [ 'o', [ ve.dm.example.boldHash ] ], 'o', { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: doc.getData( new ve.Range( 6, 20 ) ),
						insert: doc.getData( new ve.Range( 6, 20 ) )
					},
					{ type: 'retain', length: 7 }
				],
				expectedStoreItems: {
					h49981eab0f8056ff: bold
				}
			}
		];

	cases.forEach( function ( caseItem ) {
		doc = ve.dm.example.createExampleDocument( caseItem.doc );
		var doc2, removalOps, store2;
		if ( caseItem.newDocData ) {
			store2 = new ve.dm.HashValueStore();
			doc2 = new ve.dm.Document( ve.dm.example.preprocessAnnotations( caseItem.newDocData, store2 ) );
			removalOps = [];
		} else if ( caseItem.range ) {
			doc2 = doc.cloneFromRange( caseItem.range );
			caseItem.modify( doc2 );
			var removalTx = ve.dm.TransactionBuilder.static.newFromRemoval( doc, caseItem.range );
			doc.commit( removalTx );
			removalOps = removalTx.getOperations();
		}

		assert.deepEqualWithDomElements( removalOps, caseItem.removalOps, caseItem.msg + ': removal' );

		var tx = ve.dm.TransactionBuilder.static.newFromDocumentInsertion( doc, caseItem.offset, doc2 );
		assert.deepEqualWithDomElements( tx.getOperations(), caseItem.expectedOps, caseItem.msg + ': transaction' );

		var actualStoreItems = {};
		var expectedStoreItems = caseItem.expectedStoreItems || {};
		for ( var hash in expectedStoreItems ) {
			actualStoreItems[ hash ] = doc.store.value( hash );
			expectedStoreItems[ hash ].store = store2;
		}
		assert.deepEqual( actualStoreItems, expectedStoreItems, caseItem.msg + ': store items' );
	} );
} );

QUnit.test( 'newFromAttributeChanges', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		cases = {
			'first element': {
				args: [ doc, 0, { level: 2 } ],
				ops: [
					{
						type: 'attribute',
						key: 'level',
						from: 1,
						to: 2
					},
					{ type: 'retain', length: 63 }
				]
			},
			'middle element': {
				args: [ doc, 17, { style: 'number' } ],
				ops: [
					{ type: 'retain', length: 17 },
					{
						type: 'attribute',
						key: 'style',
						from: 'bullet',
						to: 'number'
					},
					{ type: 'retain', length: 46 }
				]
			},
			'multiple attributes': {
				args: [ doc, 17, { style: 'number', level: 1 } ],
				ops: [
					{ type: 'retain', length: 17 },
					{
						type: 'attribute',
						key: 'style',
						from: 'bullet',
						to: 'number'
					},
					{
						type: 'attribute',
						key: 'level',
						from: undefined,
						to: 1
					},
					{ type: 'retain', length: 46 }
				]
			},
			'non-element': {
				args: [ doc, 1, { level: 2 } ],
				exception: Error
			},
			'closing element': {
				args: [ doc, 4, { level: 2 } ],
				exception: Error
			}
		};

	ve.test.utils.runTransactionConstructorTests( assert, ve.dm.TransactionBuilder.static.newFromAttributeChanges, cases );

	var val = { foo: { bar: 'baz' } };
	var tx = ve.dm.TransactionBuilder.static.newFromAttributeChanges( doc, 0, val );
	val.foo.bar = 'qux';
	assert.deepEqual(
		tx.getOperations(),
		[
			{ type: 'attribute', key: 'foo', from: undefined, to: { bar: 'baz' } },
			{ type: 'retain', length: 63 }
		],
		'Attribute value mutated after transaction built (T193062)'
	);
} );

QUnit.test( 'newFromAnnotation', function ( assert ) {
	var boldAnnotation = ve.dm.example.createAnnotation( ve.dm.example.bold ),
		strong = { type: 'textStyle/bold', attributes: { nodeName: 'strong' } },
		strongAnnotation = ve.dm.example.createAnnotation( strong ),
		doc = ve.dm.example.createExampleDocument(),
		doc2 = ve.dm.example.createExampleDocumentFromData( [
			{ type: 'paragraph' },
			[ 'F', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'B', [ strong ] ],
			[ 'a', [ strong ] ],
			[ 'r', [ strong ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		] ),
		annotationDoc = ve.dm.example.createExampleDocument( 'annotationData' ),
		cases = {
			'over plain text': {
				args: [ doc, new ve.Range( 1, 2 ), 'set', boldAnnotation ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'a' ],
						insert: [ [ 'a', [ ve.dm.example.boldHash ] ] ]
					},
					{ type: 'retain', length: 61 }
				]
			},
			'over partially annotated text': {
				args: [ doc, new ve.Range( 1, 4 ), 'set', boldAnnotation ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'a' ],
						insert: [ [ 'a', [ ve.dm.example.boldHash ] ] ]
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ [ 'c', [ ve.dm.example.italicHash ] ] ],
						insert: [ [ 'c', [ ve.dm.example.italicHash, ve.dm.example.boldHash ] ] ]
					},
					{ type: 'retain', length: 59 }
				]
			},
			'comparable annotation over partially annotated text': {
				args: [ doc, new ve.Range( 1, 4 ), 'set', strongAnnotation ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'a' ],
						insert: [ [ 'a', [ ve.dm.example.strongHash ] ] ]
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ [ 'c', [ ve.dm.example.italicHash ] ] ],
						insert: [ [ 'c', [ ve.dm.example.italicHash, ve.dm.example.strongHash ] ] ]
					},
					{ type: 'retain', length: 59 }
				]
			},
			'adjacent comparable annotations not cleared together': {
				args: [ doc2, new ve.Range( 1, 7 ), 'clear', strongAnnotation ],
				ops: [
					{ type: 'retain', length: 4 },
					{
						type: 'replace',
						remove: [
							[ 'B', [ ve.dm.example.strongHash ] ],
							[ 'a', [ ve.dm.example.strongHash ] ],
							[ 'r', [ ve.dm.example.strongHash ] ]
						],
						insert: [ 'B', 'a', 'r' ]
					},
					{ type: 'retain', length: 3 }
				]
			},
			'over elements': {
				args: [ doc, new ve.Range( 4, 9 ), 'set', boldAnnotation ],
				ops: [
					{ type: 'retain', length: 63 }
				]
			},
			'over elements and content': {
				args: [ doc, new ve.Range( 3, 11 ), 'set', boldAnnotation ],
				ops: [
					{ type: 'retain', length: 3 },
					{
						type: 'replace',
						remove: [ [ 'c', [ ve.dm.example.italicHash ] ] ],
						insert: [ [ 'c', [ ve.dm.example.italicHash, ve.dm.example.boldHash ] ] ]
					},
					{ type: 'retain', length: 6 },
					{
						type: 'replace',
						remove: [ 'd' ],
						insert: [ [ 'd', [ ve.dm.example.boldHash ] ] ]
					},
					{ type: 'retain', length: 52 }
				]
			},
			'over content and content element (image)': {
				args: [ doc, new ve.Range( 38, 42 ), 'set', boldAnnotation ],
				ops: [
					{ type: 'retain', length: 38 },
					{
						type: 'replace',
						remove: [
							'h',
							{ type: 'inlineImage', attributes: {
								src: ve.dm.example.imgSrc,
								alt: 'Example',
								width: 100,
								height: 50
							} },
							{ type: '/inlineImage' },
							'i'
						],
						insert: [
							[ 'h', [ ve.dm.example.boldHash ] ],
							{ type: 'inlineImage', attributes: {
								src: ve.dm.example.imgSrc,
								alt: 'Example',
								width: 100,
								height: 50
							}, annotations: [ ve.dm.example.boldHash ] },
							{ type: '/inlineImage', annotations: [ ve.dm.example.boldHash ] },
							[ 'i', [ ve.dm.example.boldHash ] ]
						]
					},
					{ type: 'retain', length: 21 }
				]
			},
			'over content and unannotatable content element (unboldable node)': {
				args: [ annotationDoc, new ve.Range( 1, 9 ), 'set', boldAnnotation ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'F', 'o', 'o' ],
						insert: [
							[ 'F', [ ve.dm.example.boldHash ] ],
							[ 'o', [ ve.dm.example.boldHash ] ],
							[ 'o', [ ve.dm.example.boldHash ] ]
						]
					},
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [ 'B', 'a', 'r' ],
						insert: [
							[ 'B', [ ve.dm.example.boldHash ] ],
							[ 'a', [ ve.dm.example.boldHash ] ],
							[ 'r', [ ve.dm.example.boldHash ] ]
						]
					},
					{ type: 'retain', length: 21 }
				]
			},
			'over handles own children nodes': {
				args: [ annotationDoc, new ve.Range( 1, 27 ), 'set', boldAnnotation ],
				ops: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'F', 'o', 'o' ],
						insert: [
							[ 'F', [ ve.dm.example.boldHash ] ],
							[ 'o', [ ve.dm.example.boldHash ] ],
							[ 'o', [ ve.dm.example.boldHash ] ]
						]
					},
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [ 'B', 'a', 'r' ],
						insert: [
							[ 'B', [ ve.dm.example.boldHash ] ],
							[ 'a', [ ve.dm.example.boldHash ] ],
							[ 'r', [ ve.dm.example.boldHash ] ]
						]
					},
					{ type: 'retain', length: 15 },
					{
						type: 'replace',
						remove: [ 'B', 'a', 'z' ],
						insert: [
							[ 'B', [ ve.dm.example.boldHash ] ],
							[ 'a', [ ve.dm.example.boldHash ] ],
							[ 'z', [ ve.dm.example.boldHash ] ]
						]
					},
					{ type: 'retain', length: 3 }
				]
			}
		};

	ve.test.utils.runTransactionConstructorTests( assert, ve.dm.TransactionBuilder.static.newFromAnnotation, cases );
} );

QUnit.test( 'newFromContentBranchConversion', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		doc2 = ve.dm.example.createExampleDocument( 'inlineAtEdges' ),
		cases = {
			'range inside a heading, convert to paragraph': {
				args: [ doc, new ve.Range( 1, 2 ), 'paragraph' ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'heading', attributes: { level: 1 } } ],
						insert: [ { type: 'paragraph' } ]
					},
					{ type: 'retain', length: 3 },
					{
						type: 'replace',
						remove: [ { type: '/heading' } ],
						insert: [ { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 58 }
				]
			},
			'range inside a heading 1, convert to heading 2': {
				args: [ doc, new ve.Range( 1, 2 ), 'heading', { level: 2 } ],
				ops: [
					{
						type: 'attribute',
						key: 'level',
						from: 1,
						to: 2
					},
					{ type: 'retain', length: 63 }
				]
			},
			'range around 2 paragraphs, convert to preformatted': {
				args: [ doc, new ve.Range( 50, 58 ), 'preformatted' ],
				ops: [
					{ type: 'retain', length: 50 },
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'preformatted' } ]
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/preformatted' } ]
					},
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'preformatted' } ]
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/preformatted' } ]
					},
					{ type: 'retain', length: 5 }
				]
			},
			'zero-length range before inline node at the start': {
				args: [ doc2, new ve.Range( 1 ), 'heading', { level: 2 } ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'heading', attributes: { level: 2 } } ]
					},
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/heading' } ]
					},
					{ type: 'retain', length: 2 }
				]
			},
			'zero-length range inside inline node at the start': {
				args: [ doc2, new ve.Range( 2 ), 'heading', { level: 2 } ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'heading', attributes: { level: 2 } } ]
					},
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/heading' } ]
					},
					{ type: 'retain', length: 2 }
				]
			},
			'zero-length range after inline node at the start': {
				args: [ doc2, new ve.Range( 3 ), 'heading', { level: 2 } ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'heading', attributes: { level: 2 } } ]
					},
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/heading' } ]
					},
					{ type: 'retain', length: 2 }
				]
			},
			'zero-length range before inline node at the end': {
				args: [ doc2, new ve.Range( 6 ), 'heading', { level: 2 } ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'heading', attributes: { level: 2 } } ]
					},
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/heading' } ]
					},
					{ type: 'retain', length: 2 }
				]
			},
			'zero-length range inside inline node at the end': {
				args: [ doc2, new ve.Range( 7 ), 'heading', { level: 2 } ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'heading', attributes: { level: 2 } } ]
					},
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/heading' } ]
					},
					{ type: 'retain', length: 2 }
				]
			},
			'zero-length range after inline node at the end': {
				args: [ doc2, new ve.Range( 8 ), 'heading', { level: 2 } ],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [ { type: 'heading', attributes: { level: 2 } } ]
					},
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [ { type: '/heading' } ]
					},
					{ type: 'retain', length: 2 }
				]
			}
		};

	for ( var key in cases ) {
		for ( var i = 0; i < cases[ key ].ops.length; i++ ) {
			var store = cases[ key ].args[ 0 ].getStore();
			if ( cases[ key ].ops[ i ].remove ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].remove, store );
			}
			if ( cases[ key ].ops[ i ].insert ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].insert, store );
			}
		}
	}
	ve.test.utils.runTransactionConstructorTests(
		assert,
		ve.dm.TransactionBuilder.static.newFromContentBranchConversion,
		cases
	);
} );

QUnit.test( 'newFromWrap', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		metaDoc = ve.dm.example.createExampleDocument( 'withMeta' ),
		listMetaDoc = ve.dm.example.createExampleDocument( 'listWithMeta' ),
		listDoc = ve.dm.example.createExampleDocumentFromData(
			listMetaDoc.getData().filter( function ( item ) {
				return item.type !== 'alienMeta' &&
					item.type !== '/alienMeta';
			} )
		),
		cases = {
			'changes a heading to a paragraph': {
				args: [ doc, new ve.Range( 1, 4 ), [ { type: 'heading', attributes: { level: 1 } } ], [ { type: 'paragraph' } ], [], [] ],
				ops: [
					{ type: 'replace', remove: [ { type: 'heading', attributes: { level: 1 } } ], insert: [ { type: 'paragraph' } ] },
					{ type: 'retain', length: 3 },
					{ type: 'replace', remove: [ { type: '/heading' } ], insert: [ { type: '/paragraph' } ] },
					{ type: 'retain', length: 58 }
				]
			},
			'unwraps a list': {
				args: [ doc, new ve.Range( 13, 25 ), [ { type: 'list' } ], [], [ { type: 'listItem' } ], [] ],
				ops: [
					{ type: 'retain', length: 12 },
					{
						type: 'replace',
						remove: [ { type: 'list', attributes: { style: 'bullet' } }, { type: 'listItem' } ],
						insert: []
					},
					{ type: 'retain', length: 10 },
					{ type: 'replace', remove: [ { type: '/listItem' }, { type: '/list' } ], insert: [] },
					{ type: 'retain', length: 37 }
				]
			},
			'unwraps a multiple-item list': {
				args: [ listDoc, new ve.Range( 1, 11 ), [ { type: 'list' } ], [], [ { type: 'listItem', attributes: { styles: [ 'bullet' ] } } ], [] ],
				ops: [
					{ type: 'replace',
						remove: [ { type: 'list' }, { type: 'listItem', attributes: { styles: [ 'bullet' ] } } ],
						insert: []
					},
					{ type: 'retain', length: 3 },
					{ type: 'replace',
						remove: [ { type: '/listItem' }, { type: 'listItem', attributes: { styles: [ 'bullet' ] } } ],
						insert: []
					},
					{ type: 'retain', length: 3 },
					{ type: 'replace',
						remove: [ { type: '/listItem' }, { type: '/list' } ],
						insert: []
					},
					{ type: 'retain', length: 2 }
				]
			},
			'replaces a table with a list': {
				args: [ doc, new ve.Range( 9, 33 ), [ { type: 'table' }, { type: 'tableSection', attributes: { style: 'body' } }, { type: 'tableRow' }, { type: 'tableCell', attributes: { style: 'data' } } ], [ { type: 'list' }, { type: 'listItem' } ], [], [] ],
				ops: [
					{ type: 'retain', length: 5 },
					{ type: 'replace', remove: [ { type: 'table' }, { type: 'tableSection', attributes: { style: 'body' } }, { type: 'tableRow' }, { type: 'tableCell', attributes: { style: 'data' } } ], insert: [ { type: 'list' }, { type: 'listItem' } ] },
					{ type: 'retain', length: 24 },
					{ type: 'replace', remove: [ { type: '/tableCell' }, { type: '/tableRow' }, { type: '/tableSection' }, { type: '/table' } ], insert: [ { type: '/listItem' }, { type: '/list' } ] },
					{ type: 'retain', length: 26 }
				]
			},
			'wraps two adjacent paragraphs in a list': {
				args: [ doc, new ve.Range( 55, 61 ), [], [ { type: 'list', attributes: { style: 'number' } } ], [], [ { type: 'listItem' } ] ],
				ops: [
					{ type: 'retain', length: 55 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'list', attributes: { style: 'number' } }, { type: 'listItem' } ]
					},
					{ type: 'retain', length: 3 },
					{ type: 'replace', remove: [], insert: [ { type: '/listItem' }, { type: 'listItem' } ] },
					{ type: 'retain', length: 3 },
					{ type: 'replace', remove: [], insert: [ { type: '/listItem' }, { type: '/list' } ] },
					{ type: 'retain', length: 2 }
				]
			},
			'wraps two adjacent paragraphs in a definitionList': {
				args: [ doc, new ve.Range( 55, 61 ), [], [ { type: 'definitionList' } ], [], [ { type: 'definitionListItem', attributes: { style: 'term' } } ] ],
				ops: [
					{ type: 'retain', length: 55 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'definitionList' }, { type: 'definitionListItem', attributes: { style: 'term' } } ]
					},
					{ type: 'retain', length: 3 },
					{
						type: 'replace',
						remove: [],
						insert: [ { type: '/definitionListItem' }, { type: 'definitionListItem', attributes: { style: 'term' } } ]
					},
					{ type: 'retain', length: 3 },
					{ type: 'replace', remove: [], insert: [ { type: '/definitionListItem' }, { type: '/definitionList' } ] },
					{ type: 'retain', length: 2 }
				]
			},
			'metadata is preserved on wrap': {
				args: [
					metaDoc,
					new ve.Range( 4, 23 ),
					[],
					[ { type: 'list' } ],
					[ { type: 'paragraph' } ],
					[ { type: 'listItem' }, { type: 'heading', level: 1 } ]
				],
				ops: [
					{ type: 'retain', length: 4 },
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [
							{ type: 'list' },
							{ type: 'listItem' },
							{ type: 'heading', level: 1 }
						]
					},
					{ type: 'retain', length: 3 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [
							{ type: '/heading' },
							{ type: '/listItem' }
						]
					},
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [
							{ type: 'listItem' },
							{ type: 'heading', level: 1 }
						]
					},
					{ type: 'retain', length: 3 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [
							{ type: '/heading' },
							{ type: '/listItem' }
						]
					},
					{ type: 'retain', length: 2 },
					{
						type: 'replace',
						remove: [ { type: 'paragraph' } ],
						insert: [
							{ type: 'listItem' },
							{ type: 'heading', level: 1 }
						]
					},
					{ type: 'retain', length: 3 },
					{
						type: 'replace',
						remove: [ { type: '/paragraph' } ],
						insert: [
							{ type: '/heading' },
							{ type: '/listItem' },
							{ type: '/list' }
						]
					},
					{ type: 'retain', length: 12 }
				]
			},
			'metadata is preserved on unwrap': {
				args: [ listMetaDoc, new ve.Range( 3, 35 ), [ { type: 'list' } ], [], [ { type: 'listItem', attributes: { styles: [ 'bullet' ] } } ], [] ],
				ops: [
					{ type: 'retain', length: 2 },
					{ type: 'replace', remove: [ { type: 'list' } ], insert: [] },
					{ type: 'retain', length: 2 },
					{ type: 'replace', remove: [ { type: 'listItem', attributes: { styles: [ 'bullet' ] } } ], insert: [] },
					{ type: 'retain', length: 11 },
					{ type: 'replace', remove: [ { type: '/listItem' } ], insert: [] },
					{ type: 'retain', length: 2 },
					{ type: 'replace', remove: [ { type: 'listItem', attributes: { styles: [ 'bullet' ] } } ], insert: [] },
					{ type: 'retain', length: 11 },
					{ type: 'replace', remove: [ { type: '/listItem' } ], insert: [] },
					{ type: 'retain', length: 2 },
					{ type: 'replace', remove: [ { type: '/list' } ], insert: [] },
					{ type: 'retain', length: 4 }
				]
			},
			'checks integrity of unwrapOuter parameter': {
				args: [ doc, new ve.Range( 13, 32 ), [ { type: 'table' } ], [], [], [] ],
				exception: Error
			},
			'checks integrity of unwrapEach parameter': {
				args: [ doc, new ve.Range( 13, 32 ), [ { type: 'list' } ], [], [ { type: 'paragraph' } ], [] ],
				exception: Error
			},
			'checks that unwrapOuter fits before the range': {
				args: [ doc, new ve.Range( 1, 4 ), [ { type: 'listItem' }, { type: 'paragraph' } ], [], [], [] ],
				exception: Error
			}
		};

	for ( var key in cases ) {
		for ( var i = 0; cases[ key ].ops && i < cases[ key ].ops.length; i++ ) {
			if ( cases[ key ].ops[ i ].remove ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].remove, doc.getStore() );
			}
			if ( cases[ key ].ops[ i ].insert ) {
				ve.dm.example.preprocessAnnotations( cases[ key ].ops[ i ].insert, doc.getStore() );
			}
		}
	}
	ve.test.utils.runTransactionConstructorTests(
		assert,
		ve.dm.TransactionBuilder.static.newFromWrap,
		cases
	);
} );

QUnit.test( 'pushRetain', function ( assert ) {
	var cases = {
		retain: {
			calls: [ [ 'pushRetain', 5 ] ],
			ops: [ { type: 'retain', length: 5 } ],
			diff: 0
		},
		'multiple retain': {
			calls: [ [ 'pushRetain', 5 ], [ 'pushRetain', 3 ] ],
			ops: [ { type: 'retain', length: 8 } ],
			diff: 0
		}
	};
	ve.test.utils.runTransactionBuilderTests( assert, cases );
} );

QUnit.test( 'pushReplacement', function ( assert ) {
	var doc = new ve.dm.Document( [ { type: 'paragraph' }, 'a', 'b', 'c', { type: '/paragraph' } ] ),
		doc2 = new ve.dm.Document( [ { type: 'paragraph' }, 'a', 'b', 'c', 'g', 'h', 'i', { type: '/paragraph' } ] ),
		cases = {
			insert: {
				calls: [
					[ 'pushReplacement', doc, 0, 0, [ { type: 'paragraph' }, 'a', 'b', 'c', { type: '/paragraph' } ] ]
				],
				ops: [
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, 'a', 'b', 'c', { type: '/paragraph' } ]
					}
				],
				diff: 5
			},
			'multiple insert': {
				calls: [
					[ 'pushReplacement', doc, 0, 0, [ { type: 'paragraph' }, 'a', 'b' ] ],
					[ 'pushReplacement', doc, 0, 0, [ 'c', { type: '/paragraph' } ] ]
				],
				ops: [
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, 'a', 'b', 'c', { type: '/paragraph' } ]
					}
				],
				diff: 5
			},
			'insert and retain': {
				calls: [
					[ 'pushRetain', 1 ],
					[ 'pushReplacement', doc, 0, 0, [ 'a', 'b', 'c' ] ]
				],
				ops: [
					{ type: 'retain', length: 1 },
					{ type: 'replace', remove: [], insert: [ 'a', 'b', 'c' ] }
				],
				diff: 3
			},
			remove: {
				calls: [
					[ 'pushReplacement', doc, 0, 5, [] ]
				],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' }, 'a', 'b', 'c', { type: '/paragraph' } ],
						insert: []
					}
				],
				diff: -5
			},
			'multiple remove': {
				calls: [
					[ 'pushReplacement', doc, 0, 3, [] ],
					[ 'pushReplacement', doc, 3, 2, [] ]
				],
				ops: [
					{
						type: 'replace',
						remove: [ { type: 'paragraph' }, 'a', 'b', 'c', { type: '/paragraph' } ],
						insert: []
					}
				],
				diff: -5
			},
			'retain and remove': {
				calls: [
					[ 'pushRetain', 1 ],
					[ 'pushReplacement', doc, 1, 3, [] ]
				],
				ops: [
					{ type: 'retain', length: 1 },
					{ type: 'replace', remove: [ 'a', 'b', 'c' ], insert: [] }
				],
				diff: -3
			},
			replace: {
				calls: [
					[ 'pushReplacement', doc, 1, 3, [ 'd', 'e', 'f' ] ]
				],
				ops: [
					{
						type: 'replace',
						remove: [ 'a', 'b', 'c' ],
						insert: [ 'd', 'e', 'f' ]
					}
				],
				diff: 0
			},
			'multiple replace': {
				calls: [
					[ 'pushReplacement', doc2, 1, 3, [ 'd', 'e', 'f' ] ],
					[ 'pushReplacement', doc2, 4, 3, [ 'j', 'k', 'l' ] ]
				],
				ops: [
					{
						type: 'replace',
						remove: [ 'a', 'b', 'c', 'g', 'h', 'i' ],
						insert: [ 'd', 'e', 'f', 'j', 'k', 'l' ]
					}
				],
				diff: 0
			}
		};

	ve.test.utils.runTransactionBuilderTests( assert, cases );
} );

QUnit.test( 'pushReplaceElementAttribute', function ( assert ) {
	var cases = {
		'replace element attribute': {
			calls: [
				[ 'pushReplaceElementAttribute', 'style', 'bullet', 'number' ]
			],
			ops: [
				{
					type: 'attribute',
					key: 'style',
					from: 'bullet',
					to: 'number'
				}
			],
			diff: 0
		},
		'replace multiple element attributes': {
			calls: [
				[ 'pushReplaceElementAttribute', 'style', 'bullet', 'number' ],
				[ 'pushReplaceElementAttribute', 'level', 1, 2 ]
			],
			ops: [
				{
					type: 'attribute',
					key: 'style',
					from: 'bullet',
					to: 'number'
				},
				{
					type: 'attribute',
					key: 'level',
					from: 1,
					to: 2
				}
			],
			diff: 0
		}
	};

	ve.test.utils.runTransactionBuilderTests( assert, cases );
} );

QUnit.test( 'isNoOp', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		metaDoc = ve.dm.example.createExampleDocument( 'withMeta' ),
		listMetaDoc = ve.dm.example.createExampleDocument( 'listWithMeta' );

	[ doc, metaDoc, listMetaDoc ].forEach( function ( d, i ) {
		var isListMetaDoc = ( i === 2 );

		var tx = ve.dm.TransactionBuilder.static.newFromReplacement(
			d, new ve.Range( 1 ), [], false
		);
		assert.strictEqual( tx.isNoOp(), true );

		tx = ve.dm.TransactionBuilder.static.newFromInsertion(
			d, 1, []
		);
		assert.strictEqual( tx.isNoOp(), true );

		tx = ve.dm.TransactionBuilder.static.newFromRemoval(
			d, new ve.Range( 1 ), false
		);
		assert.strictEqual( tx.isNoOp(), true );

		if ( !isListMetaDoc ) {
			tx = ve.dm.TransactionBuilder.static.newFromDocumentInsertion(
				d, 1,
				ve.dm.example.createExampleDocument(), new ve.Range( 0 )
			);
			assert.strictEqual( tx.isNoOp(), true );
		}

		tx = ve.dm.TransactionBuilder.static.newFromAttributeChanges(
			d, isListMetaDoc ? 5 : 0, {}
		);
		assert.strictEqual( tx.isNoOp(), true );

		tx = ve.dm.TransactionBuilder.static.newFromAnnotation(
			d, new ve.Range( 1 ), 'set', new ve.dm.ItalicAnnotation()
		);
		assert.strictEqual( tx.isNoOp(), true );
	} );
} );

QUnit.test( 'operations/build from operations', function ( assert ) {
	var tBSstatic = ve.dm.TransactionBuilder.static,
		doc = ve.dm.example.createExampleDocument(),
		underline = ve.dm.example.createAnnotation( ve.dm.example.underline ),
		cases = [
			{
				msg: 'Replace annotated text',
				method: 'newFromReplacement',
				args: [ doc, new ve.Range( 1, 4 ), [ 'F', 'o', 'o' ] ],
				expected: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [ 'a', [ 'b', [ ve.dm.example.boldHash ] ], [ 'c', [ ve.dm.example.italicHash ] ] ],
						insert: [ 'F', 'o', 'o' ]
					},
					{ type: 'retain', length: 59 }
				]
			},
			{
				msg: 'New paragraph',
				method: 'newFromInsertion',
				args: [ doc, 0, [ { type: 'paragraph' }, { type: '/paragraph' } ] ],
				roundTripArgs: [ doc, 0, [ { type: 'paragraph' }, { type: '/paragraph' } ] ],
				expected: [
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'paragraph' }, { type: '/paragraph' } ]
					},
					{ type: 'retain', length: 63 }
				]
			},
			{
				msg: 'New alien preserves originalDomElements',
				method: 'newFromInsertion',
				args: [ doc, 0, [ { type: 'alienBlock' }, { type: '/alienBlock' } ] ],
				expected: [
					{
						type: 'replace',
						remove: [],
						insert: [ { type: 'alienBlock' }, { type: '/alienBlock' } ]
					},
					{ type: 'retain', length: 63 }
				]
			},
			{
				msg: 'Add annotation',
				method: 'newFromAnnotation',
				args: [ doc, new ve.Range( 1, 4 ), 'set', underline ],
				expected: [
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: [
							'a',
							[ 'b', [ ve.dm.example.boldHash ] ],
							[ 'c', [ ve.dm.example.italicHash ] ]
						],
						insert: [
							[ 'a', [ ve.dm.example.underlineHash ] ],
							[ 'b', [ ve.dm.example.underlineHash, ve.dm.example.boldHash ] ],
							[ 'c', [ ve.dm.example.underlineHash, ve.dm.example.italicHash ] ]
						]
					},
					{ type: 'retain', length: 59 }
				]
			}
		];

	cases.forEach( function ( caseItem ) {
		var tx = tBSstatic[ caseItem.method ].apply( tBSstatic, caseItem.args );
		var ops = ve.copy( tx.operations );
		assert.deepEqual( ops, caseItem.expected, caseItem.msg + ': operations' );
		if ( caseItem.roundTripArgs ) {
			tx = tBSstatic[ caseItem.method ].apply( tBSstatic, caseItem.roundTripArgs );
		}
		assert.deepEqual( new ve.dm.Transaction( ops ), tx, caseItem.msg + ': build from operations' );
	} );
} );

QUnit.test( 'newFromRemoval preserving metadata', function ( assert ) {
	function removeBoringProperties( operations ) {
		return operations.map( function ( op ) {
			if ( !op.insert ) {
				return op;
			}
			op = ve.copy( op );
			[].concat( op.insert, op.remove ).forEach( function ( item ) {
				delete item.originalDomElementsHash;
				delete item.internal;
			} );
			return op;
		} );
	}
	var cases = [
		{
			msg: 'Removal ending at meta',
			html: '<p>foo</p><meta><p>bar</p>',
			range: new ve.Range( 0, 7 ),
			ops: [
				{ type: 'replace', insert: [
					{ type: 'alienMeta' },
					{ type: '/alienMeta' }
				], remove: [
					{ type: 'paragraph' },
					'f',
					'o',
					'o',
					{ type: '/paragraph' },
					{ type: 'alienMeta' },
					{ type: '/alienMeta' }
				] },
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Removal beginning at meta',
			html: '<p>foo</p><meta><p>bar</p>',
			range: new ve.Range( 5, 13 ),
			ops: [
				{ type: 'retain', length: 5 },
				{ type: 'replace', insert: [
					{ type: 'alienMeta' },
					{ type: '/alienMeta' }
				], remove: [
					{ type: 'alienMeta' },
					{ type: '/alienMeta' },
					{ type: 'paragraph' },
					'b',
					'a',
					'r',
					{ type: '/paragraph' }
				] },
				{ type: 'retain', length: 2 }
			]
		},
		{
			msg: 'Removal across meta',
			html: '<p>foo</p><meta><p>bar</p><p>baz</p>',
			range: new ve.Range( 0, 12 ),
			ops: [
				{ type: 'replace', insert: [
					{ type: 'alienMeta' },
					{ type: '/alienMeta' }
				], remove: [
					{ type: 'paragraph' },
					'f',
					'o',
					'o',
					{ type: '/paragraph' },
					{ type: 'alienMeta' },
					{ type: '/alienMeta' },
					{ type: 'paragraph' },
					'b',
					'a',
					'r',
					{ type: '/paragraph' }
				] },
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Merge /ul/li/p nodes across meta',
			html: '<ul><li><p>foo</p></li><li><p></p></li></ul><p></p><div><div><meta></div></div><ul><li><p>bar</p></li></ul>',
			range: new ve.Range( 4, 25 ),
			ops: [
				{ type: 'retain', length: 2 },
				{ type: 'replace', remove: [], insert: [
					{ type: 'alienMeta' },
					{ type: '/alienMeta' }
				] },
				{ type: 'retain', length: 2 },
				{ type: 'replace', insert: [], remove: [
					'o',
					'o',
					{ type: '/paragraph' },
					{ type: '/listItem' },
					{ type: 'listItem' },
					{ type: 'paragraph' },
					{ type: '/paragraph' },
					{ type: '/listItem' },
					{ type: '/list' },
					{ type: 'paragraph' },
					{ type: '/paragraph' },
					{ type: 'div' },
					{ type: 'div' },
					{ type: 'alienMeta' },
					{ type: '/alienMeta' },
					{ type: '/div' },
					{ type: '/div' },
					{ type: 'list', attributes: { style: 'bullet' } },
					{ type: 'listItem' },
					{ type: 'paragraph' },
					'b'
				] },
				{ type: 'retain', length: 7 }
			]
		}
	];

	cases.forEach( function ( caseItem ) {
		var doc = ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( caseItem.html ) );
		var tx = ve.dm.TransactionBuilder.static.newFromRemoval( doc, caseItem.range );
		assert.deepEqual(
			caseItem.ops,
			removeBoringProperties( tx.operations ),
			caseItem.msg
		);
	} );
} );
