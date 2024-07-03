/*!
 * VisualEditor DataModel InternalList tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.InternalList' );

/* Tests */

QUnit.test( 'getDocument', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		internalList = doc.getInternalList();
	assert.deepEqual( internalList.getDocument(), doc, 'Returns original document' );
} );

QUnit.test( 'queueItemHtml/getItemHtmlQueue', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		internalList = doc.getInternalList();
	assert.deepEqual(
		internalList.queueItemHtml( 'reference', 'foo', 'Bar' ),
		{ index: 0, isNew: true },
		'First queued item returns index 0 and is new'
	);
	assert.deepEqual(
		internalList.queueItemHtml( 'reference', 'foo', 'Baz' ),
		{ index: 0, isNew: false },
		'Duplicate key returns index 0 and is not new'
	);
	assert.deepEqual(
		internalList.queueItemHtml( 'reference', 'bar', 'Baz' ),
		{ index: 1, isNew: true },
		'Second queued item returns index 1 and is new'
	);

	// Queue up empty data
	internalList.queueItemHtml( 'reference', 'baz', '' );
	assert.deepEqual(
		internalList.queueItemHtml( 'reference', 'baz', 'Quux' ),
		{ index: 2, isNew: true },
		'Third queued item is new because existing data in queue was empty'
	);

	assert.deepEqual( internalList.getItemHtmlQueue(), [ 'Bar', 'Baz', 'Quux' ], 'getItemHtmlQueue returns stored HTML items' );
} );

QUnit.test( 'convertToData', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		htmlDoc = doc.getHtmlDocument(),
		internalList = doc.getInternalList(),
		expectedData = [
			{ type: 'internalList' },
			{ type: 'internalItem', attributes: { originalHtml: 'Bar' } },
			{ type: 'paragraph', internal: { generated: 'wrapper', metaItems: [] } },
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: 'internalItem', attributes: { originalHtml: 'Baz' } },
			{ type: 'paragraph', internal: { generated: 'wrapper', metaItems: [] } },
			'B', 'a', 'z',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		];

	// Mimic convert state setup (as done in ve.dm.Converter#getDataFromDom)
	// TODO: The test should not (directly) reference the global instance
	ve.dm.converter.doc = htmlDoc;
	ve.dm.converter.store = doc.getStore();
	ve.dm.converter.internalList = internalList;
	ve.dm.converter.contextStack = [];

	internalList.queueItemHtml( 'reference', 'foo', 'Bar' );
	internalList.queueItemHtml( 'reference', 'bar', 'Baz' );
	assert.deepEqual( internalList.convertToData( ve.dm.converter, htmlDoc ), expectedData, 'Data matches' );
	assert.deepEqual( internalList.getItemHtmlQueue(), [], 'Items html is emptied after conversion' );
} );

QUnit.test( 'clone', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		doc2 = ve.dm.example.createExampleDocument(),
		internalList = doc.getInternalList();

	internalList.getNextUniqueNumber(); // =0
	var internalListClone = internalList.clone();
	internalList.getNextUniqueNumber(); // =1
	var internalListClone2 = internalList.clone( doc2 );
	internalList.getNextUniqueNumber(); // =2

	assert.strictEqual( internalListClone.getDocument(), internalList.getDocument(), 'Documents match' );
	assert.strictEqual( internalListClone2.getDocument(), doc2, 'Cloning with document parameter' );

	assert.strictEqual( internalList.getNextUniqueNumber(), 3, 'Original internal list has nextUniqueNumber = 3' );
	assert.strictEqual( internalListClone.getNextUniqueNumber(), 4, 'Clone from original document has nextUniqueNumber = 4' );
	assert.strictEqual( internalListClone2.getNextUniqueNumber(), 0, 'Clone with different document has nextUniqueNumber = 0' );
} );
