/*!
 * VisualEditor DataModel Transaction tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.Transaction' );

/* Helper methods */

/* Tests */
// TODO: Change the variable names to reflect the use of TransactionBuilder

QUnit.test( 'translateOffset', function ( assert ) {
	var b = [ ve.dm.example.boldHash ];

	var tx = new ve.dm.Transaction( [
		{ type: 'replace', remove: [], insert: [ 'a', 'b', 'c' ] },
		{ type: 'retain', length: 5 },
		{ type: 'replace', remove: [ 'd', 'e', 'f', 'g' ], insert: [] },
		{ type: 'retain', length: 3 },
		{ type: 'replace', remove: [ 'h' ], insert: [ 'i', 'j', 'k', 'l', 'm' ] },
		{ type: 'retain', length: 2 },
		{ type: 'replace', remove: [], insert: [ 'n', 'o', 'p' ] },
		{ type: 'retain', length: 2 },
		{ type: 'replace', remove: [ 'o', 'k' ], insert: [ [ 'o', b ], [ 'k', b ] ] },
		{ type: 'retain', length: 2 },
		{ type: 'replace', remove: [ 'n', 'o', 'n' ], insert: [ [ 'n', b ], [ 'o', b ] ] },
		{ type: 'retain', length: 2 },
		{ type: 'replace', remove: [ 'h', 'i' ], insert: [ [ 'l', b ], [ 'o', b ] ] }
	] );

	var mapping = {
		0: [ 0, 3 ],
		1: 4,
		2: 5,
		3: 6,
		4: 7,
		5: 8,
		6: 8,
		7: 8,
		8: 8,
		9: 8,
		10: 9,
		11: 10,
		12: 11,
		13: [ 12, 16 ],
		14: 17,
		15: [ 18, 21 ],
		16: 22,
		17: 23,
		18: 24,
		19: 25,
		20: 26,
		21: 27,
		22: 29,
		23: 29,
		24: 29,
		25: 30,
		26: 31,
		27: 33,
		28: 33
	};

	for ( var offset in mapping ) {
		var expected = Array.isArray( mapping[ offset ] ) ? mapping[ offset ] : [ mapping[ offset ], mapping[ offset ] ];
		assert.strictEqual( tx.translateOffset( Number( offset ) ), expected[ 1 ], offset );
		assert.strictEqual( tx.translateOffset( Number( offset ), true ), expected[ 0 ], offset + ' (excludeInsertion)' );
	}
} );

QUnit.test( 'translateRange', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		txBuilder = new ve.dm.TransactionBuilder();
	txBuilder.pushRetain( 55 );
	txBuilder.pushReplacement( doc, 55, 0, [ { type: 'list', attributes: { style: 'number' } } ] );
	txBuilder.pushReplacement( doc, 55, 0, [ { type: 'listItem' } ] );
	txBuilder.pushRetain( 3 );
	txBuilder.pushReplacement( doc, 58, 0, [ { type: '/listItem' } ] );
	txBuilder.pushReplacement( doc, 58, 0, [ { type: 'listItem' } ] );
	txBuilder.pushRetain( 3 );
	txBuilder.pushReplacement( doc, 61, 0, [ { type: '/listItem' } ] );
	txBuilder.pushReplacement( doc, 61, 0, [ { type: '/list' } ] );
	var tx = txBuilder.getTransaction();

	var cases = [
		{
			before: new ve.Range( 55, 61 ),
			after: new ve.Range( 55, 67 ),
			msg: 'Wrapped range is translated to outer range'
		},
		{
			before: new ve.Range( 54, 62 ),
			after: new ve.Range( 54, 68 ),
			msg: 'Wrapped range plus one each side is translated to outer range plus one each side'
		},
		{
			before: new ve.Range( 54, 61 ),
			after: new ve.Range( 54, 67 ),
			msg: 'Wrapped range plus one on the left'
		},
		{
			before: new ve.Range( 55, 62 ),
			after: new ve.Range( 55, 68 ),
			msg: 'wrapped range plus one on the right'
		}
	];

	cases.forEach( function ( caseItem ) {
		assert.equalRange( tx.translateRange( caseItem.before ), caseItem.after, caseItem.msg );
		assert.equalRange( tx.translateRange( caseItem.before.flip() ), caseItem.after.flip(), caseItem.msg + ' (reversed)' );
	} );
} );

QUnit.test( 'getModifiedRange', function ( assert ) {
	var doc = ve.dm.example.createExampleDocument(),
		cases = [
			{
				calls: [
					[ 'pushRetain', 5 ]
				],
				range: null,
				msg: 'no-op transaction returns null'
			},
			{
				calls: [
					[ 'pushRetain', 5 ],
					[ 'pushReplacement', doc, 5, 0, [ 'a', 'b', 'c' ] ],
					[ 'pushRetain', 42 ]
				],
				range: new ve.Range( 5, 8 ),
				msg: 'simple insertion returns range covering new content'
			},
			{
				calls: [
					[ 'pushRetain', 5 ],
					[ 'pushReplacement', doc, 5, 13, [] ],
					[ 'pushRetain', 42 ]
				],
				range: new ve.Range( 5 ),
				msg: 'simple removal returns zero-length range at removal'
			},
			{
				calls: [
					[ 'pushRetain', 5 ],
					[ 'pushReplacement', doc, 5, 3, [ 'a', 'b', 'c', 'd' ] ],
					[ 'pushRetain', 42 ]
				],
				range: new ve.Range( 5, 9 ),
				msg: 'simple replacement returns range covering new content (1)'
			},
			{
				calls: [
					[ 'pushRetain', 5 ],
					[ 'pushReplacement', doc, 5, 13, [ 'a', 'b', 'c', 'd' ] ],
					[ 'pushRetain', 42 ]
				],
				range: new ve.Range( 5, 9 ),
				msg: 'simple replacement returns range covering new content (2)'
			},
			{
				calls: [
					[ 'pushRetain', 5 ],
					[ 'pushReplacement', doc, 5, 3, [] ],
					[ 'pushRetain', 42 ],
					[ 'pushReplacement', doc, 50, 0, [ 'h', 'e', 'l', 'l', 'o' ] ],
					[ 'pushRetain', 108 ]
				],
				range: new ve.Range( 5, 52 ),
				msg: 'range covers two operations with retain in between'
			},
			{
				calls: [
					[ 'pushRetain', 5 ],
					[ 'pushReplacement', doc, 5, 3, [ 'a', 'b', 'c', 'd' ] ],
					[ 'pushRetain', 54 ],
					[ 'pushReplacement', doc, 62, 0, [ 'h', 'e', 'l', 'l', 'o' ] ],
					[ 'pushRetain', 1 ]
				],
				range: new ve.Range( 5, 9 ),
				msg: 'range ignores internalList changes'
			},
			{
				calls: [
					[ 'pushReplacement', doc, 0, 3, [] ]
				],
				range: new ve.Range( 0 ),
				msg: 'removal without retains'
			},
			{
				calls: [
					[ 'pushReplacement', doc, 0, 0, [ 'a', 'b', 'c' ] ]
				],
				range: new ve.Range( 0, 3 ),
				msg: 'insertion without retains'
			},
			{
				calls: [
					[ 'pushRetain', 5 ],
					[ 'pushReplaceElementAttribute', 'style', 'bullet', 'number' ]
				],
				range: new ve.Range( 5, 6 ),
				msg: 'single attribute change'
			},
			{
				calls: [
					[ 'pushReplaceElementAttribute', 'style', 'bullet', 'number' ],
					[ 'pushRetain', 42 ],
					[ 'pushReplaceElementAttribute', 'style', 'bullet', 'number' ]
				],
				range: new ve.Range( 0, 43 ),
				msg: 'two attribute changes'
			}
		];

	cases.forEach( function ( caseItem ) {
		var txBuilder = new ve.dm.TransactionBuilder();
		for ( var j = 0; j < caseItem.calls.length; j++ ) {
			txBuilder[ caseItem.calls[ j ][ 0 ] ].apply( txBuilder, caseItem.calls[ j ].slice( 1 ) );
		}
		assert.equalRange( txBuilder.getTransaction().getModifiedRange( doc ), caseItem.range, caseItem.msg );
	} );
} );

QUnit.test( 'Metadata transactions', function ( assert ) {
	var fooMeta = { type: 'alienMeta', attributes: { label: 'foo' } },
		barMeta = { type: 'alienMeta', attributes: { label: 'bar' } },
		data = [
			{ type: 'paragraph' },
			'x',
			{ type: '/paragraph' },
			fooMeta,
			{ type: '/alienMeta' },
			barMeta,
			{ type: '/alienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		events = [];

	function getElements( list ) {
		return list.items.map( function ( item ) {
			return item.element;
		} );
	}

	var doc = new ve.dm.Document( [] );
	var metaList = doc.getMetaList();
	var surface = new ve.dm.Surface( doc );
	var fragment = surface.getFragment();
	metaList.connect( null, {
		insert: function ( item ) {
			events.push( [ 'insert', item.element ] );
		},
		remove: function ( item ) {
			events.push( [ 'remove', item.element ] );
		}
	} );
	surface.change( ve.dm.TransactionBuilder.static.newFromInsertion( doc, 0, data ) );
	assert.deepEqual(
		getElements( metaList ),
		[ fooMeta, barMeta ],
		'Metadata inserted into meta list'
	);
	fragment.removeMeta( doc.documentNode.children[ 1 ] );
	assert.deepEqual(
		doc.data.data,
		data.slice( 0, 3 ).concat( data.slice( 5 ) ),
		'Metadata removed from linear data'
	);
	assert.deepEqual(
		getElements( metaList ),
		[ barMeta ],
		'Metadata removed from meta list'
	);
	fragment.replaceMeta( doc.documentNode.children[ 1 ], fooMeta );
	assert.deepEqual(
		doc.data.data,
		data.slice( 0, 5 ).concat( data.slice( 7 ) ),
		'Metadata replaced in linear data'
	);
	assert.deepEqual(
		getElements( metaList ),
		[ fooMeta ],
		'Metadata replaced in meta list'
	);
	fragment.insertMeta( barMeta );
	assert.deepEqual(
		doc.data.data,
		data,
		'Metadata re-inserted into linear data'
	);
	assert.deepEqual(
		getElements( metaList ),
		[ fooMeta, barMeta ],
		'Metadata re-inserted into meta list'
	);
	assert.deepEqual(
		events,
		[
			[ 'insert', fooMeta ],
			[ 'insert', barMeta ],
			[ 'remove', fooMeta ],
			[ 'remove', barMeta ],
			[ 'insert', fooMeta ],
			[ 'insert', barMeta ]
		],
		'Meta list events'
	);
} );
