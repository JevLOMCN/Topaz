'use strict';

/*!
 * VisualEditor DataModel Cite-specific InternalList tests.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

QUnit.module( 've.dm.InternalList (Cite)', ve.test.utils.newMwEnvironment() );

/* Tests */

QUnit.test( 'addNode/removeNode', function ( assert ) {
	const doc = ve.dm.citeExample.createExampleDocument( 'references' );
	let newInternalList = new ve.dm.InternalList( doc );
	const referenceNodes = [
		doc.getDocumentNode().children[ 0 ].children[ 0 ],
		doc.getDocumentNode().children[ 1 ].children[ 1 ],
		doc.getDocumentNode().children[ 1 ].children[ 3 ],
		doc.getDocumentNode().children[ 1 ].children[ 5 ],
		doc.getDocumentNode().children[ 2 ].children[ 0 ],
		doc.getDocumentNode().children[ 2 ].children[ 1 ]
	];
	const expectedNodes = {
		'mwReference/': {
			keyedNodes: {
				'auto/0': [ referenceNodes[ 0 ] ],
				'literal/bar': [ referenceNodes[ 1 ], referenceNodes[ 3 ] ],
				'literal/:3': [ referenceNodes[ 2 ] ],
				'auto/1': [ referenceNodes[ 4 ] ]
			},
			firstNodes: [
				referenceNodes[ 0 ],
				referenceNodes[ 1 ],
				referenceNodes[ 2 ],
				referenceNodes[ 4 ]
			],
			indexOrder: [ 0, 1, 2, 3 ],
			uniqueListKeys: {},
			uniqueListKeysInUse: {}
		},
		'mwReference/foo': {
			keyedNodes: {
				'auto/2': [ referenceNodes[ 5 ] ]
			},
			firstNodes: [ undefined, undefined, undefined, undefined, referenceNodes[ 5 ] ],
			indexOrder: [ 4 ],
			uniqueListKeys: {},
			uniqueListKeysInUse: {}
		}
	};

	assert.deepEqualWithNodeTree(
		doc.internalList.nodes,
		expectedNodes,
		'Document construction populates internal list correctly'
	);

	newInternalList.addNode( 'mwReference/', 'auto/0', 0, referenceNodes[ 0 ] );
	newInternalList.addNode( 'mwReference/', 'literal/bar', 1, referenceNodes[ 1 ] );
	newInternalList.addNode( 'mwReference/', 'literal/:3', 2, referenceNodes[ 2 ] );
	newInternalList.addNode( 'mwReference/', 'literal/bar', 1, referenceNodes[ 3 ] );
	newInternalList.addNode( 'mwReference/', 'auto/1', 3, referenceNodes[ 4 ] );
	newInternalList.addNode( 'mwReference/foo', 'auto/2', 4, referenceNodes[ 5 ] );
	newInternalList.onTransact();

	assert.deepEqualWithNodeTree(
		newInternalList.nodes,
		expectedNodes,
		'Nodes added in order'
	);

	newInternalList = new ve.dm.InternalList( doc );

	newInternalList.addNode( 'mwReference/foo', 'auto/2', 4, referenceNodes[ 5 ] );
	newInternalList.addNode( 'mwReference/', 'auto/1', 3, referenceNodes[ 4 ] );
	newInternalList.addNode( 'mwReference/', 'literal/bar', 1, referenceNodes[ 3 ] );
	newInternalList.addNode( 'mwReference/', 'literal/:3', 2, referenceNodes[ 2 ] );
	newInternalList.addNode( 'mwReference/', 'literal/bar', 1, referenceNodes[ 1 ] );
	newInternalList.addNode( 'mwReference/', 'auto/0', 0, referenceNodes[ 0 ] );
	newInternalList.onTransact();

	assert.deepEqualWithNodeTree(
		newInternalList.nodes,
		expectedNodes,
		'Nodes added in reverse order'
	);

	newInternalList.removeNode( 'mwReference/', 'literal/bar', 1, referenceNodes[ 1 ] );
	newInternalList.onTransact();

	assert.deepEqualWithNodeTree(
		newInternalList.nodes,
		{
			'mwReference/': {
				keyedNodes: {
					'auto/0': [ referenceNodes[ 0 ] ],
					'literal/bar': [ referenceNodes[ 3 ] ],
					'literal/:3': [ referenceNodes[ 2 ] ],
					'auto/1': [ referenceNodes[ 4 ] ]
				},
				firstNodes: [
					referenceNodes[ 0 ],
					referenceNodes[ 3 ],
					referenceNodes[ 2 ],
					referenceNodes[ 4 ]
				],
				indexOrder: [ 0, 2, 1, 3 ],
				uniqueListKeys: {},
				uniqueListKeysInUse: {}
			},
			'mwReference/foo': {
				keyedNodes: {
					'auto/2': [ referenceNodes[ 5 ] ]
				},
				firstNodes: [ undefined, undefined, undefined, undefined, referenceNodes[ 5 ] ],
				indexOrder: [ 4 ],
				uniqueListKeys: {},
				uniqueListKeysInUse: {}
			}
		},
		'Keys re-ordered after one item of key removed'
	);

	newInternalList.removeNode( 'mwReference/', 'literal/bar', 1, referenceNodes[ 3 ] );
	newInternalList.onTransact();

	assert.deepEqualWithNodeTree(
		newInternalList.nodes,
		{
			'mwReference/': {
				keyedNodes: {
					'auto/0': [ referenceNodes[ 0 ] ],
					'literal/:3': [ referenceNodes[ 2 ] ],
					'auto/1': [ referenceNodes[ 4 ] ]
				},
				firstNodes: [
					referenceNodes[ 0 ],
					undefined,
					referenceNodes[ 2 ],
					referenceNodes[ 4 ]
				],
				indexOrder: [ 0, 2, 3 ],
				uniqueListKeys: {},
				uniqueListKeysInUse: {}
			},
			'mwReference/foo': {
				keyedNodes: {
					'auto/2': [ referenceNodes[ 5 ] ]
				},
				firstNodes: [ undefined, undefined, undefined, undefined, referenceNodes[ 5 ] ],
				indexOrder: [ 4 ],
				uniqueListKeys: {},
				uniqueListKeysInUse: {}
			}
		},
		'Keys truncated after last item of key removed'
	);

	newInternalList.removeNode( 'mwReference/', 'auto/0', 0, referenceNodes[ 0 ] );
	newInternalList.removeNode( 'mwReference/foo', 'auto/2', 4, referenceNodes[ 5 ] );
	newInternalList.removeNode( 'mwReference/', 'auto/1', 3, referenceNodes[ 4 ] );
	newInternalList.removeNode( 'mwReference/', 'literal/:3', 2, referenceNodes[ 2 ] );
	newInternalList.onTransact();

	assert.deepEqualWithNodeTree(
		newInternalList.nodes,
		{
			'mwReference/': {
				keyedNodes: {},
				firstNodes: new Array( 4 ),
				indexOrder: [],
				uniqueListKeys: {},
				uniqueListKeysInUse: {}
			},
			'mwReference/foo': {
				keyedNodes: {},
				firstNodes: new Array( 5 ),
				indexOrder: [],
				uniqueListKeys: {},
				uniqueListKeysInUse: {}
			}
		},
		'All nodes removed'
	);
} );

QUnit.test( 'getItemInsertion', function ( assert ) {
	const doc = ve.dm.citeExample.createExampleDocument( 'references' );
	const internalList = doc.getInternalList();

	let insertion = internalList.getItemInsertion( 'mwReference/', 'literal/foo', [] );
	const index = internalList.getItemNodeCount();
	assert.strictEqual( insertion.index, index, 'Insertion creates a new reference' );
	assert.deepEqual(
		insertion.transaction.getOperations(),
		[
			{ type: 'retain', length: 91 },
			{
				type: 'replace',
				remove: [],
				insert: [
					{ type: 'internalItem' },
					{ type: '/internalItem' }
				]
			},
			{ type: 'retain', length: 1 }
		],
		'New reference operations match' );

	insertion = internalList.getItemInsertion( 'mwReference/', 'literal/foo', [] );
	assert.strictEqual( insertion.index, index, 'Insertion with duplicate key reuses old index' );
	assert.strictEqual( insertion.transaction, null, 'Insertion with duplicate key has null transaction' );
} );

QUnit.test( 'getUniqueListKey', function ( assert ) {
	const doc = ve.dm.citeExample.createExampleDocument( 'references' );
	const internalList = doc.getInternalList();

	let generatedName;
	generatedName = internalList.getUniqueListKey( 'mwReference/', 'auto/0', 'literal/:' );
	assert.strictEqual( generatedName, 'literal/:0', '0 maps to 0' );
	generatedName = internalList.getUniqueListKey( 'mwReference/', 'auto/1', 'literal/:' );
	assert.strictEqual( generatedName, 'literal/:1', '1 maps to 1' );
	generatedName = internalList.getUniqueListKey( 'mwReference/', 'auto/2', 'literal/:' );
	assert.strictEqual( generatedName, 'literal/:2', '2 maps to 2' );
	generatedName = internalList.getUniqueListKey( 'mwReference/', 'auto/3', 'literal/:' );
	assert.strictEqual( generatedName, 'literal/:4', '3 maps to 4 (because a literal :3 is present)' );
	generatedName = internalList.getUniqueListKey( 'mwReference/', 'auto/4', 'literal/:' );
	assert.strictEqual( generatedName, 'literal/:5', '4 maps to 5' );

	generatedName = internalList.getUniqueListKey( 'mwReference/', 'auto/0', 'literal/:' );
	assert.strictEqual( generatedName, 'literal/:0', 'Reusing a key reuses the name' );

	generatedName = internalList.getUniqueListKey( 'mwReference/foo', 'auto/4', 'literal/:' );
	assert.strictEqual( generatedName, 'literal/:0', 'Different groups are treated separately' );
} );
