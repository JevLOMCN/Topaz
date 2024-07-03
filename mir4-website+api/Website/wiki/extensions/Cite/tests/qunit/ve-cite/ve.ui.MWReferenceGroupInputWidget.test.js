'use strict';

QUnit.module( 've.ui.MWReferenceGroupInputWidget (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'Constructor', function ( assert ) {
	const widget = new ve.ui.MWReferenceGroupInputWidget( {
		emptyGroupName: '—'
	} );
	assert.strictEqual( widget.emptyGroupName, '—' );
	assert.strictEqual( widget.getMenu().getItemCount(), 0 );
} );

QUnit.test( 'populateMenu', function ( assert ) {
	const doc = ve.dm.citeExample.createExampleDocument( 'references' );

	const widget = new ve.ui.MWReferenceGroupInputWidget( {
		emptyGroupName: 'empty'
	} );
	widget.populateMenu( doc.getInternalList() );

	assert.strictEqual( widget.getMenu().getItemCount(), 2 );

	assert.strictEqual( widget.getMenu().items[ 0 ].getData(), '' );
	assert.strictEqual( widget.getMenu().items[ 0 ].getLabel(), 'empty' );

	assert.strictEqual( widget.getMenu().items[ 1 ].getData(), 'foo' );
	assert.strictEqual( widget.getMenu().items[ 1 ].getLabel(), 'foo' );
} );
