'use strict';

/*!
 * VisualEditor DataModel Cite-specific Transaction tests.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

QUnit.module( 've.dm.Transaction (Cite)', ve.test.utils.newMwEnvironment() );

// FIXME: Duplicates test runner; should be using a data provider
QUnit.test( 'newFromDocumentInsertion with references', function ( assert ) {
	const complexDoc = ve.dm.citeExample.createExampleDocument( 'complexInternalData' ),
		withReference = [
			{ type: 'paragraph' },
			'B', 'a', 'r',
			{ type: 'mwReference', attributes: {
				mw: {},
				about: '#mwt4',
				listIndex: 0,
				listGroup: 'mwReference/',
				listKey: 'auto/0',
				refGroup: '',
				contentsUsed: true
			} },
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: 'internalItem' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'B',
			'a',
			'z',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		],
		cases = [
			{
				msg: 'inserting a brand new document; internal lists are merged and items renumbered',
				doc: 'complexInternalData',
				offset: 7,
				newDocData: withReference,
				removalOps: [],
				expectedOps: [
					{ type: 'retain', length: 7 },
					{
						type: 'replace',
						remove: [],
						insert: withReference.slice( 0, 4 )
							// Renumber listIndex from 0 to 2
							// Renumber listKey from auto/0 to auto/1
							.concat( [
								ve.extendObject( true, {}, withReference[ 4 ],
									{ attributes: { listIndex: 2, listKey: 'auto/1' } }
								)
							] )
							.concat( withReference.slice( 5, 7 ) )
					},
					{ type: 'retain', length: 1 },
					{
						type: 'replace',
						remove: complexDoc.getData( new ve.Range( 8, 32 ) ),
						insert: complexDoc.getData( new ve.Range( 8, 32 ) )
							.concat( withReference.slice( 8, 15 ) )
					},
					{ type: 'retain', length: 1 }
				]
			}
		];

	cases.forEach( function ( caseItem ) {
		const doc = ve.dm.citeExample.createExampleDocument( caseItem.doc );
		let doc2, removalOps;
		if ( caseItem.newDocData ) {
			doc2 = new ve.dm.Document( caseItem.newDocData );
			removalOps = [];
		} else if ( caseItem.range ) {
			doc2 = doc.cloneFromRange( caseItem.range );
			caseItem.modify( doc2 );
			const removalTx = ve.dm.TransactionBuilder.static
				.newFromRemoval( doc, caseItem.range, true );
			doc.commit( removalTx );
			removalOps = removalTx.getOperations();
		}

		assert.deepEqualWithDomElements(
			removalOps, caseItem.removalOps, caseItem.msg + ': removal'
		);

		const tx = ve.dm.TransactionBuilder.static.newFromDocumentInsertion(
			doc, caseItem.offset, doc2
		);
		assert.deepEqualWithDomElements(
			tx.getOperations(), caseItem.expectedOps, caseItem.msg + ': transaction'
		);

		const expectedStoreItems = caseItem.expectedStoreItems || [];
		const actualStoreItems = expectedStoreItems.map( function ( item ) {
			return doc.store.value( OO.getHash( item ) );
		} );
		assert.deepEqual( actualStoreItems, expectedStoreItems, caseItem.msg + ': store items' );
	} );
} );
