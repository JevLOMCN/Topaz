/*!
 * VisualEditor DataModel Document tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.Document' );

/* Tests */

QUnit.test( 'constructor', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument();

	assert.equalNodeTree( doc.getDocumentNode(), ve.dm.example.tree, 'node tree matches example data' );
	assert.throws(
		function () {
			doc = new ve.dm.Document( [
				{ type: '/paragraph' },
				{ type: 'paragraph' }
			] );
			doc.buildNodeTree();
		},
		Error,
		'unbalanced input causes exception'
	);
	assert.throws(
		function () {
			doc = new ve.dm.Document( [
				{ type: 'paragraph' },
				{ type: 'inlineImage' },
				{ type: '/paragraph' }
			] );
			doc.buildNodeTree();
		},
		Error,
		'unclosed inline node causes exception'
	);
	assert.throws(
		function () {
			doc = new ve.dm.Document( [
				{ type: 'div' },
				{ type: 'paragraph' },
				'a',
				{ type: '/div' },
				{ type: '/paragraph' }
			] );
			doc.buildNodeTree();
		},
		Error,
		'mismatching close tags cause exception'
	);
	assert.throws(
		function () {
			doc = new ve.dm.Document( [
				{ type: 'div' },
				{ type: 'paragraph' },
				'a',
				{ type: '/paragraph' }
			] );
			doc.buildNodeTree();
		},
		Error,
		'unclosed open tag causes exception'
	);

	doc = new ve.dm.Document( [ 'a', 'b', 'c', 'd' ] );
	assert.equalNodeTree(
		doc.getDocumentNode(),
		new ve.dm.DocumentNode( [ new ve.dm.TextNode( 4 ) ] ),
		'plain text input is handled correctly'
	);
	assert.strictEqual( doc.getMetadata().join( ',' ), ',,,', 'Empty metadata array' );
	assert.strictEqual( doc.getHtmlDocument().body.innerHTML, '', 'Empty HTML document is created' );

	var htmlDoc = ve.createDocumentFromHtml( 'abcd' );
	doc = new ve.dm.Document( [ 'a', 'b', 'c', 'd' ], htmlDoc );
	assert.strictEqual( doc.getHtmlDocument(), htmlDoc, 'Provided HTML document is used' );

	var data = new ve.dm.ElementLinearData(
		new ve.dm.HashValueStore(),
		[ { type: 'paragraph' }, { type: '/paragraph' } ]
	);
	doc = new ve.dm.Document( data );
	assert.equalNodeTree(
		doc.getDocumentNode(),
		new ve.dm.DocumentNode( [ new ve.dm.ParagraphNode( { type: 'paragraph' } ) ] ),
		'empty paragraph no longer has a text node'
	);
	assert.strictEqual( doc.data, data, 'ElementLinearData is stored by reference' );
} );

QUnit.test( 'newBlankDocument', function ( assert ) {
	var doc = ve.dm.Document.static.newBlankDocument();

	assert.true( doc instanceof ve.dm.Document, 'newBlankDocument creates a document' );
	assert.strictEqual( doc.data.data[ 0 ].internal.generated, 'empty', 'Creates an "empty" paragraph with no args' );

	doc = ve.dm.Document.static.newBlankDocument( 'wrapper' );
	assert.strictEqual( doc.data.data[ 0 ].internal.generated, 'wrapper', 'Creates an "wrapper" paragraph when requested' );

	doc = ve.dm.Document.static.newBlankDocument( null );
	assert.false( 'internal' in doc.data.data[ 0 ], 'Creates an regular paragraph with null' );
} );

QUnit.test( 'getData', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		expectedData = ve.dm.example.preprocessAnnotations( ve.copy( ve.dm.example.data ) );
	assert.equalLinearDataWithDom( doc.getStore(), doc.getData(), expectedData.getData() );
} );

QUnit.test( 'getFullData', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument( 'withMeta' );
	assert.equalLinearDataWithDom( doc.getStore(), doc.getFullData(), ve.dm.example.withMeta );
} );

QUnit.test( 'cloneFromRange', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument( 'internalData' ),
		cases = [
			{
				msg: 'range undefined',
				doc: 'internalData',
				range: undefined,
				expectedData: doc.data.slice()
			},
			{
				msg: 'first internal item',
				doc: 'internalData',
				range: new ve.Range( 7, 12 ),
				expectedData: doc.data.slice( 7, 12 ).concat( doc.data.slice( 5, 21 ) )
			},
			{
				msg: 'second internal item',
				doc: 'internalData',
				range: doc.getInternalList().getItemNode( 1 ).getRange(),
				expectedData: doc.data.slice( 14, 19 ).concat( doc.data.slice( 5, 21 ) )
			},
			{
				msg: 'paragraph at the start',
				doc: 'internalData',
				range: new ve.Range( 0, 5 ),
				expectedData: doc.data.slice( 0, 21 )
			},
			{
				msg: 'paragraph at the end',
				doc: 'internalData',
				range: new ve.Range( 21, 27 ),
				expectedData: doc.data.slice( 21, 27 ).concat( doc.data.slice( 5, 21 ) )
			}
		];

	cases.forEach( function ( caseItem ) {
		var doc1 = ve.dm.example.createExampleDocument( caseItem.doc ),
			doc2 = doc1.cloneFromRange( caseItem.range );
		assert.deepEqual( doc2.data.data, caseItem.expectedData,
			caseItem.msg + ': sliced data' );
		assert.notStrictEqual( doc2.data[ 0 ], caseItem.expectedData[ 0 ],
			caseItem.msg + ': data is cloned, not the same' );
		assert.deepEqual( doc2.store, doc1.store,
			caseItem.msg + ': store is copied' );
		assert.notStrictEqual( doc2.store, doc1.store,
			caseItem.msg + ': store is a clone, not the same' );
		assert.strictEqual( doc2.getStorage(), doc1.getStorage(),
			caseItem.msg + ': persistentStorage is copied' );
	} );
} );

QUnit.test( 'getRelativeOffset', function ( assert ) {
	var documentModel = ve.dm.example.createExampleDocument( 'alienData' ),
		tests = [
			{
				direction: 1,
				unit: 'character',
				cases: [
					{ input: 0, output: 1 },
					{ input: 2, output: 3 },
					{ input: 3, output: 4 },
					{ input: 4, output: 5 },
					{ input: 6, output: 7 },
					{ input: 7, output: 9 },
					{ input: 10, output: 10 }
				]
			},
			{
				direction: 1,
				unit: 'word',
				cases: [
					{ input: 0, output: 1 },
					{ input: 2, output: 3 },
					{ input: 3, output: 4 },
					{ input: 4, output: 5 },
					{ input: 6, output: 7 },
					{ input: 7, output: 9 },
					{ input: 10, output: 10 }
				]
			},
			{
				direction: -1,
				unit: 'character',
				cases: [
					{ input: 10, output: 9 },
					{ input: 8, output: 7 },
					{ input: 7, output: 6 },
					{ input: 6, output: 5 },
					{ input: 4, output: 3 },
					{ input: 3, output: 1 },
					{ input: 0, output: 0 }
				]
			},
			{
				direction: -1,
				unit: 'word',
				cases: [
					{ input: 10, output: 9 },
					{ input: 8, output: 7 },
					{ input: 7, output: 6 },
					{ input: 6, output: 5 },
					{ input: 4, output: 3 },
					{ input: 3, output: 1 },
					{ input: 0, output: 0 }
				]
			}
		];
	tests.forEach( function ( testItem ) {
		testItem.cases.forEach( function ( caseItem ) {
			assert.strictEqual(
				documentModel.getRelativeOffset(
					caseItem.input,
					testItem.direction,
					testItem.unit
				),
				caseItem.output,
				caseItem.input + ', ' + testItem.direction + ', ' + testItem.unit
			);
		} );
	} );
} );

QUnit.test( 'getRelativeRange', function ( assert ) {
	var tests = [
		{
			data: [
				{ type: 'paragraph' }, // 0
				'a', // 1
				{ type: 'alienInline' }, // 2
				{ type: '/alienInline' }, // 3
				'b', // 4
				{ type: '/paragraph' } // 5
			],
			cases: [
				{
					direction: 1,
					given: new ve.Range( 1 ),
					expected: new ve.Range( 2 )
				},
				{
					direction: 1,
					given: new ve.Range( 2 ),
					expected: new ve.Range( 2, 4 )
				},
				{
					direction: 1,
					given: new ve.Range( 2, 4 ),
					expected: new ve.Range( 4 )
				},

				{
					direction: 1,
					expand: true,
					given: new ve.Range( 1 ),
					expected: new ve.Range( 1, 2 )
				},
				{
					direction: 1,
					expand: true,
					given: new ve.Range( 1, 2 ),
					expected: new ve.Range( 1, 4 )
				},
				{
					direction: 1,
					expand: true,
					given: new ve.Range( 1, 4 ),
					expected: new ve.Range( 1, 5 )
				}
			]
		},
		{
			data: [
				{ type: 'paragraph' }, // 0
				{ type: 'alienInline' }, // 1
				{ type: '/alienInline' }, // 2
				{ type: 'alienInline' }, // 3
				{ type: '/alienInline' }, // 4
				{ type: '/paragraph' } // 5
			],
			cases: [
				{
					direction: 1,
					given: new ve.Range( 3 ),
					expected: new ve.Range( 3, 5 )
				},
				{
					direction: 1,
					expand: true,
					given: new ve.Range( 1, 3 ),
					expected: new ve.Range( 1, 5 )
				},
				{
					direction: -1,
					expand: true,
					given: new ve.Range( 1, 5 ),
					expected: new ve.Range( 1, 3 )
				},
				{
					direction: 1,
					expand: true,
					given: new ve.Range( 5, 1 ),
					expected: new ve.Range( 5, 3 )
				}
			]
		},
		{
			data: ve.copy( ve.dm.example.alienData ),
			cases: [
				{
					direction: 1,
					given: new ve.Range( 0 ),
					expected: new ve.Range( 0, 2 )
				},
				{
					direction: 1,
					given: new ve.Range( 0, 2 ),
					expected: new ve.Range( 3 )
				},
				{
					direction: 1,
					given: new ve.Range( 3 ),
					expected: new ve.Range( 4 )
				},
				{
					direction: 1,
					given: new ve.Range( 4 ),
					expected: new ve.Range( 4, 6 )
				},
				{
					direction: 1,
					given: new ve.Range( 4, 6 ),
					expected: new ve.Range( 6 )
				},
				{
					direction: 1,
					given: new ve.Range( 6 ),
					expected: new ve.Range( 7 )
				},
				{
					direction: 1,
					given: new ve.Range( 7 ),
					expected: new ve.Range( 8, 10 )
				},
				{
					direction: 1,
					given: new ve.Range( 10 ),
					expected: new ve.Range( 10 )
				},
				{
					direction: -1,
					given: new ve.Range( 10 ),
					expected: new ve.Range( 10, 8 )
				},
				{
					direction: -1,
					given: new ve.Range( 10, 8 ),
					expected: new ve.Range( 7 )
				},
				{
					direction: -1,
					given: new ve.Range( 7 ),
					expected: new ve.Range( 6 )
				},
				{
					direction: -1,
					given: new ve.Range( 6 ),
					expected: new ve.Range( 6, 4 )
				},
				{
					direction: -1,
					given: new ve.Range( 6, 4 ),
					expected: new ve.Range( 4 )
				},
				{
					direction: -1,
					given: new ve.Range( 4 ),
					expected: new ve.Range( 3 )
				},
				{
					direction: -1,
					given: new ve.Range( 3 ),
					expected: new ve.Range( 2, 0 )
				},
				{
					direction: -1,
					given: new ve.Range( 2, 0 ),
					expected: new ve.Range( 0 )
				}
			]
		}
	];
	tests.forEach( function ( testItem, i ) {
		var documentModel = new ve.dm.Document( testItem.data );
		testItem.cases.forEach( function ( caseItem ) {
			assert.equalRange(
				documentModel.getRelativeRange(
					caseItem.given,
					caseItem.direction,
					'character',
					!!caseItem.expand
				),
				caseItem.expected,
				'Test document ' + i +
				', range ' + caseItem.given.toJSON() +
				', direction ' + caseItem.direction
			);
		} );
	} );
} );

QUnit.test( 'getBranchNodeFromOffset', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		root = doc.getDocumentNode().getRoot(),
		expected = [
			[], // 0 - document
			[ 0 ], // 1 - heading
			[ 0 ], // 2 - heading
			[ 0 ], // 3 - heading
			[ 0 ], // 4 - heading
			[], // 5 - document
			[ 1 ], // 6 - table
			[ 1, 0 ], // 7 - tableSection
			[ 1, 0, 0 ], // 8 - tableRow
			[ 1, 0, 0, 0 ], // 9 - tableCell
			[ 1, 0, 0, 0, 0 ], // 10 - paragraph
			[ 1, 0, 0, 0, 0 ], // 11 - paragraph
			[ 1, 0, 0, 0 ], // 12 - tableCell
			[ 1, 0, 0, 0, 1 ], // 13 - list
			[ 1, 0, 0, 0, 1, 0 ], // 14 - listItem
			[ 1, 0, 0, 0, 1, 0, 0 ], // 15 - paragraph
			[ 1, 0, 0, 0, 1, 0, 0 ], // 16 - paragraph
			[ 1, 0, 0, 0, 1, 0 ], // 17 - listItem
			[ 1, 0, 0, 0, 1, 0, 1 ], // 18 - list
			[ 1, 0, 0, 0, 1, 0, 1, 0 ], // 19 - listItem
			[ 1, 0, 0, 0, 1, 0, 1, 0, 0 ], // 20 - paragraph
			[ 1, 0, 0, 0, 1, 0, 1, 0, 0 ], // 21 - paragraph
			[ 1, 0, 0, 0, 1, 0, 1, 0 ], // 22 - listItem
			[ 1, 0, 0, 0, 1, 0, 1 ], // 23 - list
			[ 1, 0, 0, 0, 1, 0 ], // 24 - listItem
			[ 1, 0, 0, 0, 1 ], // 25 - list
			[ 1, 0, 0, 0 ], // 26 - tableCell
			[ 1, 0, 0, 0, 2 ], // 27 - list
			[ 1, 0, 0, 0, 2, 0 ], // 28 - listItem
			[ 1, 0, 0, 0, 2, 0, 0 ], // 29 - paragraph
			[ 1, 0, 0, 0, 2, 0, 0 ], // 30 - paragraph
			[ 1, 0, 0, 0, 2, 0 ], // 31 - listItem
			[ 1, 0, 0, 0, 2 ], // 32 - list
			[ 1, 0, 0, 0 ], // 33 - tableCell
			[ 1, 0, 0 ], // 34 - tableRow
			[ 1, 0 ], // 35 - tableSection
			[ 1 ], // 36 - table
			[], // 37- document
			[ 2 ], // 38 - preformatted
			[ 2 ], // 39 - preformatted
			[ 2 ], // 40 - preformatted
			[ 2 ], // 41 - preformatted
			[ 2 ], // 42 - preformatted
			[], // 43 - document
			[ 3 ], // 44 - definitionList
			[ 3, 0 ], // 45 - definitionListItem
			[ 3, 0, 0 ], // 46 - paragraph
			[ 3, 0, 0 ], // 47 - paragraph
			[ 3, 0 ], // 48 - definitionListItem
			[ 3 ], // 49 - definitionList
			[ 3, 1 ], // 50 - definitionListItem
			[ 3, 1, 0 ], // 51 - paragraph
			[ 3, 1, 0 ], // 52 - paragraph
			[ 3, 1 ], // 53 - definitionListItem
			[ 3 ], // 54 - definitionList
			[], // 55 - document
			[ 4 ], // 56 - paragraph
			[ 4 ], // 57 - paragraph
			[], // 58 - document
			[ 5 ], // 59 - paragraph
			[ 5 ], // 60 - paragraph
			[] // 61 - document
		];

	expected.forEach( function ( expectedItem, i ) {
		var node = root;
		expectedItem.forEach( function ( offset ) {
			node = node.children[ offset ];
		} );
		assert.strictEqual( node, doc.getBranchNodeFromOffset( i ), 'reference at offset ' + i );
	} );
} );

QUnit.test( 'hasSlugAtOffset', function ( assert ) {
	var expected = {
			0: true,
			10: true
		},
		doc = ve.dm.example.createExampleDocument( 'alienData' );

	for ( var i = 0, l = doc.data.getLength(); i <= l; i++ ) {
		assert.strictEqual( doc.hasSlugAtOffset( i ), !!expected[ i ], 'hasSlugAtOffset ' + i + ' = ' + !!expected[ i ] );
	}

} );

QUnit.test( 'getDataFromNode', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		expectedData = ve.dm.example.preprocessAnnotations( ve.copy( ve.dm.example.data ) );
	assert.deepEqual(
		doc.getDataFromNode( doc.getDocumentNode().getChildren()[ 0 ] ),
		expectedData.slice( 1, 4 ),
		'branch with leaf children'
	);
	assert.deepEqual(
		doc.getDataFromNode( doc.getDocumentNode().getChildren()[ 1 ] ),
		expectedData.slice( 6, 36 ),
		'branch with branch children'
	);
	assert.deepEqual(
		doc.getDataFromNode( doc.getDocumentNode().getChildren()[ 2 ].getChildren()[ 1 ] ),
		[],
		'leaf without children'
	);
} );

QUnit.test( 'getOuterLength', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument();
	assert.strictEqual(
		doc.getDocumentNode().getOuterLength(),
		ve.dm.example.data.length,
		'document does not have elements around it'
	);
} );

QUnit.test( 'selectNodes', function ( assert ) {
	var doc,
		mainDoc = ve.dm.example.createExampleDocument(),
		cases = ve.dm.example.selectNodesCases;

	function resolveNode( item ) {
		var newItem = ve.extendObject( {}, item );
		newItem.node = ve.dm.example.lookupNode.apply(
			ve.dm.example, [ doc.getDocumentNode() ].concat( item.node )
		);
		return newItem;
	}

	cases.forEach( function ( caseItem ) {
		var expectedSelection;
		doc = caseItem.doc ? ve.dm.example.createExampleDocument( caseItem.doc ) : mainDoc;
		expectedSelection = caseItem.expected.map( resolveNode );
		assert.equalNodeSelection(
			doc.selectNodes( caseItem.range, caseItem.mode ), expectedSelection, caseItem.msg
		);
	} );
} );

QUnit.test( 'rangeInsideOneLeafNode', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		cases = [
			{
				range: new ve.Range( 1, 4 ),
				result: true
			},
			{
				range: new ve.Range( 4, 1 ),
				result: true
			},
			{
				range: new ve.Range( 0, 5 ),
				result: false
			},
			{
				range: new ve.Range( 0, 4 ),
				result: false
			},
			{
				range: new ve.Range( 0 ),
				result: false
			},
			{
				range: new ve.Range( 5 ),
				result: false
			},
			{
				range: new ve.Range( 1 ),
				result: true
			},
			{
				range: new ve.Range( 5, 1 ),
				result: false
			},
			{
				range: new ve.Range( 4, 6 ),
				result: false
			}
		];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual(
			doc.rangeInsideOneLeafNode( caseItem.range ),
			caseItem.result,
			'Range ' + caseItem.range.from + ', ' + caseItem.range.to + ' ' +
			( caseItem.result ? 'is' : 'isn\'t' ) + ' inside one leaf node'
		);
	} );
} );
QUnit.test( 'shallowCloneFromRange', function ( assert ) {
	var cases = [
		{
			msg: 'no range',
			range: undefined,
			expected: ve.copy( ve.dm.example.data.slice( 0, -2 ) )
		},
		{
			msg: 'collapsed range',
			range: new ve.Range( 2 ),
			expected: [
				{ type: 'paragraph', internal: { generated: 'empty' } },
				{ type: '/paragraph' }
			],
			originalRange: new ve.Range( 1 ),
			balancedRange: new ve.Range( 1 )
		},
		{
			doc: 'alienWithEmptyData',
			msg: 'collapsed range in empty paragraph',
			range: new ve.Range( 1 ),
			expected: [
				{ type: 'paragraph', internal: { generated: 'empty' } },
				{ type: '/paragraph' }
			],
			originalRange: new ve.Range( 1 ),
			balancedRange: new ve.Range( 1 )
		},
		{
			doc: 'alienData',
			msg: 'range ending inside alienBlock node',
			range: new ve.Range( 7, 9 ),
			expected: [
				{ type: 'paragraph' },
				{ type: '/paragraph' },
				{ type: 'alienBlock', originalDomElements: $.parseHTML( '<foobar />' ) },
				{ type: '/alienBlock' }
			],
			originalRange: new ve.Range( 1, 3 ),
			balancedRange: new ve.Range( 0, 4 )
		},
		{
			msg: 'range with one character',
			range: new ve.Range( 2, 3 ),
			expected: [
				{ type: 'heading', attributes: { level: 1 }, internal: { generated: 'wrapper' } },
				[ 'b', [ ve.dm.example.bold ] ],
				{ type: '/heading' }
			],
			originalRange: new ve.Range( 1, 2 ),
			balancedRange: new ve.Range( 1, 2 )
		},
		{
			msg: 'range with two characters',
			range: new ve.Range( 2, 4 ),
			expected: [
				{ type: 'heading', attributes: { level: 1 }, internal: { generated: 'wrapper' } },
				[ 'b', [ ve.dm.example.bold ] ],
				[ 'c', [ ve.dm.example.italic ] ],
				{ type: '/heading' }
			],
			originalRange: new ve.Range( 1, 3 ),
			balancedRange: new ve.Range( 1, 3 )
		},
		{
			msg: 'range with two characters and a header closing',
			range: new ve.Range( 2, 5 ),
			expected: [
				{ type: 'heading', attributes: { level: 1 } },
				[ 'b', [ ve.dm.example.bold ] ],
				[ 'c', [ ve.dm.example.italic ] ],
				{ type: '/heading' }
			],
			originalRange: new ve.Range( 1, 4 )
		},
		{
			msg: 'range with one character, a header closing and a table opening',
			range: new ve.Range( 3, 6 ),
			expected: [
				{ type: 'heading', attributes: { level: 1 } },
				[ 'c', [ ve.dm.example.italic ] ],
				{ type: '/heading' },
				{ type: 'table' },
				{ type: '/table' }
			],
			originalRange: new ve.Range( 1, 4 )
		},
		{
			msg: 'range from a paragraph into a list',
			range: new ve.Range( 15, 21 ),
			expected: [
				{ type: 'paragraph' },
				'e',
				{ type: '/paragraph' },
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'paragraph' },
				'f',
				{ type: '/paragraph' },
				{ type: '/listItem' },
				{ type: '/list' }
			],
			originalRange: new ve.Range( 1, 7 )
		},
		{
			msg: 'range from a paragraph inside a nested list into the next list',
			range: new ve.Range( 20, 27 ),
			expected: [
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'paragraph' },
				'f',
				{ type: '/paragraph' },
				{ type: '/listItem' },
				{ type: '/list' },
				{ type: '/listItem' },
				{ type: '/list' },
				{ type: 'list', attributes: { style: 'number' } },
				{ type: '/list' }
			],
			originalRange: new ve.Range( 5, 12 )
		},
		{
			msg: 'range from a paragraph inside a nested list out of both lists',
			range: new ve.Range( 20, 26 ),
			expected: [
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'paragraph' },
				'f',
				{ type: '/paragraph' },
				{ type: '/listItem' },
				{ type: '/list' },
				{ type: '/listItem' },
				{ type: '/list' }
			],
			originalRange: new ve.Range( 5, 11 )
		},
		{
			msg: 'range from a paragraph inside a nested list out of the outer listItem',
			range: new ve.Range( 20, 25 ),
			expected: [
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'paragraph' },
				'f',
				{ type: '/paragraph' },
				{ type: '/listItem' },
				{ type: '/list' },
				{ type: '/listItem' },
				{ type: '/list' }
			],
			originalRange: new ve.Range( 5, 10 ),
			balancedRange: new ve.Range( 1, 10 )
		},
		{
			msg: 'table cell',
			range: new ve.Range( 8, 34 ),
			expected: [
				{ type: 'table' },
				{ type: 'tableSection', attributes: { style: 'body' } },
				{ type: 'tableRow' },
				{ type: 'tableCell', attributes: { style: 'data' } },
				{ type: 'paragraph' },
				'd',
				{ type: '/paragraph' },
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'paragraph' },
				'e',
				{ type: '/paragraph' },
				{ type: 'list', attributes: { style: 'bullet' } },
				{ type: 'listItem' },
				{ type: 'paragraph' },
				'f',
				{ type: '/paragraph' },
				{ type: '/listItem' },
				{ type: '/list' },
				{ type: '/listItem' },
				{ type: '/list' },
				{ type: 'list', attributes: { style: 'number' } },
				{ type: 'listItem' },
				{ type: 'paragraph' },
				'g',
				{ type: '/paragraph' },
				{ type: '/listItem' },
				{ type: '/list' },
				{ type: '/tableCell' },
				{ type: '/tableRow' },
				{ type: '/tableSection' },
				{ type: '/table' }
			],
			originalRange: new ve.Range( 3, 29 ),
			balancedRange: new ve.Range( 3, 29 )
		},
		{
			doc: 'inlineAtEdges',
			msg: 'inline node at start',
			range: new ve.Range( 1, 3 ),
			expected: [
				{ type: 'paragraph', internal: { generated: 'wrapper' } },
				ve.dm.example.image.data,
				{ type: '/inlineImage' },
				{ type: '/paragraph' }
			],
			originalRange: new ve.Range( 1, 3 ),
			balancedRange: new ve.Range( 1, 3 )
		},
		{
			doc: 'inlineAtEdges',
			msg: 'inline node at end',
			range: new ve.Range( 6, 8 ),
			expected: [
				{ type: 'paragraph', internal: { generated: 'wrapper' } },
				{ type: 'alienInline', originalDomElements: $.parseHTML( '<foobar />' ) },
				{ type: '/alienInline' },
				{ type: '/paragraph' }
			],
			originalRange: new ve.Range( 1, 3 ),
			balancedRange: new ve.Range( 1, 3 )
		},
		{
			doc: 'inlineAtEdges',
			msg: 'inline node at start with text',
			range: new ve.Range( 1, 5 ),
			expected: [
				{ type: 'paragraph', internal: { generated: 'wrapper' } },
				ve.dm.example.image.data,
				{ type: '/inlineImage' },
				'F', 'o',
				{ type: '/paragraph' }
			],
			originalRange: new ve.Range( 1, 5 ),
			balancedRange: new ve.Range( 1, 5 )
		},
		{
			doc: 'inlineAtEdges',
			msg: 'inline node at end with text',
			range: new ve.Range( 4, 8 ),
			expected: [
				{ type: 'paragraph', internal: { generated: 'wrapper' } },
				'o', 'o',
				{ type: 'alienInline', originalDomElements: $.parseHTML( '<foobar />' ) },
				{ type: '/alienInline' },
				{ type: '/paragraph' }
			],
			originalRange: new ve.Range( 1, 5 ),
			balancedRange: new ve.Range( 1, 5 )
		}
	];

	cases.forEach( function ( caseItem ) {
		var doc = ve.dm.example.createExampleDocument( caseItem.doc ),
			expectedData = ve.dm.example.preprocessAnnotations( caseItem.expected.slice(), doc.getStore() ).getData(),
			range = new ve.Range( 0, caseItem.expected.length );

		expectedData = expectedData.concat( [
			{ type: 'internalList' },
			{ type: '/internalList' }
		] );
		var slice = doc.shallowCloneFromRange( caseItem.range );
		assert.equalLinearDataWithDom(
			doc.getStore(),
			slice.getData(),
			expectedData,
			caseItem.msg + ': data'
		);
		assert.equalRange(
			slice.originalRange,
			caseItem.originalRange || range,
			caseItem.msg + ': original range'
		);
		assert.equalRange(
			slice.balancedRange,
			caseItem.balancedRange || range,
			caseItem.msg + ': balanced range'
		);
	} );
} );

QUnit.test( 'protection against double application of transactions', function ( assert ) {
	var testDocument = ve.dm.example.createExampleDocument(),
		txBuilder = new ve.dm.TransactionBuilder();
	txBuilder.pushRetain( 1 );
	txBuilder.pushReplacement( testDocument, 1, 0, [ 'H', 'e', 'l', 'l', 'o' ] );
	testDocument.commit( txBuilder.getTransaction() );
	assert.throws(
		function () {
			testDocument.commit( txBuilder.getTransaction() );
		},
		Error,
		'exception thrown when trying to commit an already-committed transaction'
	);
} );

QUnit.test( 'getNearestCursorOffset', function ( assert ) {
	var cases = [
		{
			name: 'example',
			doc: ve.dm.example.createExampleDocument(),
			expected: {
				// 10 offsets per row
				'-1': [
					1, 1, 2, 3, 4, 4, 4, 4, 4, 4,
					10, 11, 11, 11, 11, 15, 16, 16, 16, 16,
					20, 21, 21, 21, 21, 21, 21, 21, 21, 29,
					30, 30, 30, 30, 30, 30, 30, 30, 38, 39,
					39, 41, 42, 42, 42, 42, 46, 47, 47, 47,
					47, 51, 52, 52, 52, 52, 56, 57, 57, 59,
					60, 60, 60
				],
				1: [
					1, 1, 2, 3, 4, 10, 10, 10, 10, 10,
					10, 11, 15, 15, 15, 15, 16, 20, 20, 20,
					20, 21, 29, 29, 29, 29, 29, 29, 29, 29,
					30, 38, 38, 38, 38, 38, 38, 38, 38, 39,
					41, 41, 42, 46, 46, 46, 46, 47, 51, 51,
					51, 51, 52, 56, 56, 56, 56, 57, 59, 59,
					60, 60, 60
				]
			}
		},
		{
			name: 'figcaption',
			doc: ve.dm.example.createExampleDocument( 'figcaption' ),
			expected: {
				// 10 offsets per row
				'-1': [
					1, 1, 2, 2, 2, 2, 6, 7, 7, 7,
					7, 11, 12, 12, 12
				],
				1: [
					1, 1, 2, 6, 6, 6, 6, 7, 11, 11,
					11, 11, 12, 12, 12
				]
			}
		},
		{
			name: 'internalData',
			doc: ve.dm.example.createExampleDocument( 'internalData' ),
			expected: {
				// TODO: Better define behaviour around ignoreChildren node
				// boundaries so the offsets are less erratic here.
				// 10 offsets per row
				'-1': [
					1, 1, 2, 3, 4, 4, 4, -1, 8, 9,
					10, 11, 11, 4, -1, 15, 16, 17, 18, 18,
					4, 4, 22, 23, 24, 25, 26
				],
				1: [
					1, 1, 2, 3, 4, 4, 4, 8, 8, 9,
					10, 11, -1, 4, 15, 15, 16, 17, 18, -1,
					4, 22, 22, 23, 24, 25, 26
				]
			}
		}
	];

	cases.forEach( function ( caseItem ) {
		for ( var dir = -1; dir <= 1; dir += 2 ) {
			for ( var i = 0; i < caseItem.doc.data.getLength(); i++ ) {
				assert.strictEqual(
					caseItem.doc.getNearestCursorOffset( i, dir ),
					caseItem.expected[ dir ][ i ],
					'Document "' + caseItem.name + '" - Direction: ' + dir + ' Offset: ' + i
				);
			}
		}
	} );
} );

QUnit.test( 'Selection equality', function ( assert ) {
	var selections = [
		new ve.dm.LinearSelection( new ve.Range( 1, 1 ) ),
		new ve.dm.LinearSelection( new ve.Range( 1, 3 ) ),
		new ve.dm.LinearSelection( new ve.Range( 3, 1 ) ),
		new ve.dm.TableSelection( new ve.Range( 4, 25 ), 0, 1, 2, 2 ),
		new ve.dm.NullSelection(),
		undefined,
		null,
		'foo'
	];

	selections.forEach( function ( iSel, i ) {
		if ( !( iSel instanceof ve.dm.Selection ) ) {
			return;
		}
		selections.forEach( function ( jSel, j ) {
			assert.strictEqual( iSel.equals( jSel ), i === j, 'Selections ' + i + ' and ' + j );
		} );
	} );
} );

QUnit.test( 'findText (plain text)', function ( assert ) {
	var doc = ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml(
			// 0
			'<p>Foo bar fooq.</p>' +
			// 15
			'<p>baz\nfoob</p>' +
			// 25
			'<p>Liberté, Égalité, Fraternité.</p>' +
			// 56
			'<p>ééé</p>' +
			// 61
			'<p>Erdős, Malmö</p>' +
			// 75
			'<p>İzlanda</p>'
		) ),
		cases = [
			{
				msg: 'Simple case insensitive',
				query: 'Foo',
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 1, 4 ),
					new ve.Range( 9, 12 ),
					new ve.Range( 20, 23 )
				]
			},
			{
				msg: 'Simple case sensitive',
				query: 'Foo',
				options: {
					noOverlaps: true,
					caseSensitiveString: true
				},
				matchCase: true,
				ranges: [
					new ve.Range( 1, 4 )
				]
			},
			{
				msg: 'Case insensitive regex',
				query: /fo[^ ]+/gi,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 1, 4 ),
					new ve.Range( 9, 14 ),
					new ve.Range( 20, 24 )
				]
			},
			{
				msg: 'Case sensitive regex',
				query: /fo[^ ]+/g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 9, 14 ),
					new ve.Range( 20, 24 )
				]
			},
			{
				msg: 'Regex to end of line',
				query: /q.*/g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 12, 14 )
				]
			},
			{
				msg: 'Overlapping regex',
				query: /[\s\S]*/g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 1, 14 ),
					new ve.Range( 16, 24 ),
					new ve.Range( 26, 55 ),
					new ve.Range( 57, 60 ),
					new ve.Range( 62, 74 ),
					new ve.Range( 76, 83 )
				]
			},
			{
				msg: 'Literal newline',
				query: /\n/g,
				options: {},
				ranges: [
					new ve.Range( 19, 20 )
				]
			},
			{
				msg: 'Anchor "^" matches at start of line, but does not match after literal newlines',
				query: /^./g,
				options: {},
				ranges: [
					new ve.Range( 1, 2 ),
					new ve.Range( 16, 17 ),
					new ve.Range( 26, 27 ),
					new ve.Range( 57, 58 ),
					new ve.Range( 62, 63 ),
					new ve.Range( 76, 77 )
				]
			},
			{
				msg: 'Anchor "$" matches at end of line, but does not match before literal newlines',
				query: /.$/g,
				options: {},
				ranges: [
					new ve.Range( 13, 14 ),
					new ve.Range( 23, 24 ),
					new ve.Range( 54, 55 ),
					new ve.Range( 59, 60 ),
					new ve.Range( 73, 74 ),
					new ve.Range( 82, 83 )
				]
			},
			{
				msg: 'Character which changes length after toLowerCase (İ) doesn\'t break later offsets',
				query: 'land',
				options: {},
				ranges: [
					new ve.Range( 78, 82 )
				]
			},
			{
				msg: 'Diacritic insensitive & case sensitive match',
				query: 'Egalite',
				options: {
					diacriticInsensitiveString: true,
					caseSensitiveString: true
				},
				ranges: [
					new ve.Range( 35, 42 )
				]
			},
			{
				msg: 'Diacritic insensitive but failing case sensitive',
				query: 'egalite',
				options: {
					diacriticInsensitiveString: true,
					caseSensitiveString: true
				},
				ranges: []
			},
			{
				msg: 'Diacritic insensitive case insensitive match',
				query: 'egalite',
				options: {
					diacriticInsensitiveString: true
				},
				ranges: [
					new ve.Range( 35, 42 )
				]
			},
			{
				msg: 'Diacritic insensitive & whole word match',
				query: 'Egalite',
				options: {
					diacriticInsensitiveString: true,
					wholeWord: true
				},
				ranges: [
					new ve.Range( 35, 42 )
				]
			},
			{
				msg: 'Diacritic insensitive & whole word fail',
				query: 'Egal',
				options: {
					diacriticInsensitiveString: true,
					wholeWord: true
				},
				ranges: []
			},
			{
				msg: 'Diacritic insensitive with overlaps',
				query: 'ee',
				options: {
					diacriticInsensitiveString: true
				},
				ranges: [
					new ve.Range( 57, 59 ),
					new ve.Range( 58, 60 )
				]
			},
			{
				msg: 'Diacritic insensitive without overlaps',
				query: 'ee',
				options: {
					diacriticInsensitiveString: true,
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 57, 59 )
				]
			},
			{
				msg: 'ő matches o in English locale',
				query: 'Erdos',
				options: {
					diacriticInsensitiveString: true,
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 62, 67 )
				]
			},
			{
				msg: 'ő does not match o in Hungarian locale',
				lang: 'hu',
				query: 'Erdos',
				options: {
					diacriticInsensitiveString: true,
					noOverlaps: true
				},
				ranges: []
			},
			{
				msg: 'ö matches o in German locale',
				lang: 'de',
				query: 'Malmo',
				options: {
					diacriticInsensitiveString: true,
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 69, 74 )
				]
			},
			{
				msg: 'ö does not match o in Swedish locale',
				lang: 'sv',
				query: 'Malmo',
				options: {
					diacriticInsensitiveString: true,
					noOverlaps: true
				},
				ranges: []
			}
		];

	cases.forEach( function ( caseItem ) {
		doc.lang = caseItem.lang || 'en';
		var ranges = doc.findText( caseItem.query, caseItem.options );
		assert.deepEqual( ranges, caseItem.ranges, caseItem.msg );
	} );
} );

QUnit.test( 'findText (non-text content)', function ( assert ) {
	var doc = ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml(
			//   1 3     5     7 9
			//  / /     /     / /
			'<p>ab<img/><img/>cd</p>'
		) ),
		cases = [
			{
				msg: 'Start of line anchor "^" does not match after nodes',
				query: /^./g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 1, 2 )
				]
			},
			{
				msg: 'End of line anchor "$" does not match before nodes',
				query: /.$/g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 8, 9 )
				]
			},
			{
				msg: 'A dot in regexp matches each node once',
				query: /./g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 1, 2 ),
					new ve.Range( 2, 3 ),
					new ve.Range( 3, 5 ),
					new ve.Range( 5, 7 ),
					new ve.Range( 7, 8 ),
					new ve.Range( 8, 9 )
				]
			},
			{
				msg: 'Partial match of a node (start) is extended',
				query: /ab./g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 1, 5 )
				]
			},
			{
				msg: 'Partial match of a node (end) is skipped',
				query: /.cd/g,
				options: {
					noOverlaps: true
				},
				ranges: []
			},
			{
				msg: 'Partial match of a node (end) is skipped, but a shorter match is found',
				query: /.?cd/g,
				options: {
					noOverlaps: true
				},
				ranges: [
					new ve.Range( 7, 9 )
				]
			}
		];

	cases.forEach( function ( caseItem ) {
		var ranges = doc.findText( caseItem.query, caseItem.options );
		assert.deepEqual( ranges, caseItem.ranges, caseItem.msg );
	} );
} );

QUnit.test( 'fixupInsertion', function ( assert ) {
	var doc = new ve.dm.Document( [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph' },
			'a',
			'b',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' }, { type: '/internalList' }
		] ),
		surface = new ve.dm.Surface( doc ),
		fragment = surface.getLinearFragment( new ve.Range( 4, 4 ) ),
		nodeData = [ { type: 'listlessNode' }, 'x', { type: '/listlessNode' } ];

	// TODO: test other parts of fixupInsertion; childNodes and parentNodes.
	// Currently they're mostly tested implicitly in the paste tests
	// elsewhere.

	// We need a node with suggestedParentNodeTypes, and nothing in core actually uses this. So stub one out:
	function ListlessNode() {
		ListlessNode.super.apply( this, arguments );
	}
	OO.inheritClass( ListlessNode, ve.dm.ContentBranchNode );
	ListlessNode.static.name = 'listlessNode';
	ListlessNode.static.suggestedParentNodeTypes = [ 'document', 'tableCell', 'div' ];

	ve.dm.modelRegistry.register( ListlessNode );

	fragment.insertContent( nodeData );
	assert.deepEqual(
		doc.getData( new ve.Range( 3, 14 ) ),
		[
			'a',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'listlessNode' },
			'x',
			{ type: '/listlessNode' },
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph' },
			'b'
		],
		'inserting a listlessNode into a listitem should isolate it from the list'
	);

	surface.undo();
	fragment = surface.getLinearFragment( new ve.Range( 8, 8 ) );
	fragment.insertContent( nodeData );
	assert.deepEqual(
		doc.getData( new ve.Range( 7, 11 ) ),
		[
			{ type: '/list' },
			{ type: 'listlessNode' },
			'x',
			{ type: '/listlessNode' }
		],
		'inserting a listlessNode to the document root should not add any extra closing elements'
	);

	ve.dm.modelRegistry.unregister( ListlessNode );
} );

QUnit.test( 'read-only and offset caching', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		surface = new ve.dm.Surface( doc );

	assert.strictEqual( doc.isReadOnly(), false, 'New document is not read-only' );

	// Set up offset caching test
	assert.strictEqual( doc.getDocumentNode().children[ 1 ].getOffset(), 5, 'Second child node has offset 6' );
	surface.getLinearFragment( new ve.Range( 1 ) ).insertContent( 'Test' );

	doc.setReadOnly( true );
	assert.strictEqual( doc.isReadOnly(), true, 'Document is read-only' );

	// Offset caching test
	assert.strictEqual( doc.getDocumentNode().children[ 1 ].getOffset(), 9, 'Second child node offset has been translated, not cached from before' );

	// Document modified after cache set
	doc.setReadOnly( false );
	surface.getLinearFragment( new ve.Range( 1 ) ).insertContent( 'Quux' );

	doc.setReadOnly( true );
	assert.strictEqual( doc.getDocumentNode().children[ 1 ].getOffset(), 13, 'Second child node offset has been translated again, not cached from before' );
} );
