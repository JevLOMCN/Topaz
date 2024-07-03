'use strict';

/*!
 * @license MIT
 */

QUnit.module( 've.dm.MWReferenceModel (Cite)', ve.test.utils.newMwEnvironment() );

/* Tests */

QUnit.test( 'find an unknown ref', function ( assert ) {
	const doc = ve.dm.citeExample.createExampleDocument( 'references' );
	const surface = new ve.dm.Surface( doc );

	const refNode = new ve.dm.MWReferenceNode( {
		type: 'mwReference',
		attributes: {},
		originalDomElementsHash: Math.random()
	} );
	refNode.setDocument( doc );
	const refModel = ve.dm.MWReferenceModel.static.newFromReferenceNode( refNode );
	// TODO: Callers might be surprised, the docs hint that a missing entry results in `null`.
	assert.strictEqual( refModel.findInternalItem( surface ), undefined );
} );

QUnit.test( 'find a known ref', function ( assert ) {
	const doc = ve.dm.citeExample.createExampleDocument( 'references' );
	const surface = new ve.dm.Surface( doc );

	// We know exactly where the third ref node is, grab it from the document.
	const refNode = doc.getDocumentNode().children[ 1 ].children[ 3 ];
	const refModel = ve.dm.MWReferenceModel.static.newFromReferenceNode( refNode );
	const found = refModel.findInternalItem( surface );
	assert.strictEqual( found.type, 'internalItem' );
} );

QUnit.test( 'insert new ref', function ( assert ) {
	const doc = new ve.dm.Document( [
		{ type: 'paragraph', internal: { generated: 'empty' } },
		{ type: '/paragraph' },
		{ type: 'internalList' },
		{ type: '/internalList' }
	] );
	const surface = new ve.dm.Surface( doc );
	const internalList = doc.getInternalList();

	// Create a new, blank reference model linked to the doc.
	const refModel = new ve.dm.MWReferenceModel( doc );

	const oldNodeCount = internalList.getItemNodeCount();
	const oldDocLength = doc.getLength();

	refModel.insertInternalItem( surface );
	assert.strictEqual( internalList.getItemNodeCount(), oldNodeCount + 1, 'internalItem added' );

	surface.setSelection( new ve.dm.LinearSelection( new ve.Range( 1 ) ) );
	refModel.insertReferenceNode( surface.getFragment().collapseToEnd() );
	assert.strictEqual( doc.getLength(), oldDocLength + 6, 'mwReference added to document' );

	refModel.updateInternalItem( surface );
	assert.strictEqual( internalList.getNodeGroup( 'mwReference/' ).keyedNodes[ 'auto/0' ].length, 1, 'keyedNodes track the ref' );
} );

QUnit.test( 'insert ref reuse', function ( assert ) {
	const doc = ve.dm.citeExample.createExampleDocument( 'references' );
	const surface = new ve.dm.Surface( doc );
	const internalList = doc.getInternalList();

	// Duplicate a ref.
	const refNode = doc.getDocumentNode().children[ 1 ].children[ 3 ];
	const refModel = ve.dm.MWReferenceModel.static.newFromReferenceNode( refNode );

	const oldNodeCount = internalList.getItemNodeCount();
	const oldDocLength = doc.getLength();

	assert.strictEqual( internalList.getNodeGroup( 'mwReference/' ).keyedNodes[ 'auto/0' ].length, 1, 'Initial document does not reuse ref' );
	refModel.insertInternalItem( surface );
	assert.strictEqual( internalList.getItemNodeCount(), oldNodeCount + 1, 'internalItem added' );

	surface.setSelection( new ve.dm.LinearSelection( new ve.Range( 1 ) ) );
	refModel.insertReferenceNode( surface.getFragment().collapseToEnd() );
	assert.strictEqual( doc.getLength(), oldDocLength + 10, 'mwReference added to document' );

	refModel.updateInternalItem( surface );
	assert.strictEqual( internalList.getNodeGroup( 'mwReference/' ).keyedNodes[ 'auto/0' ].length, 2, 'keyedNodes track the ref reuse' );
} );
