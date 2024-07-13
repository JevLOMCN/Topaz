'use strict';

QUnit.module( 've.ui.MWReferenceSearchWidget (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'buildIndex', function ( assert ) {
	const widget = new ve.ui.MWReferenceSearchWidget();
	widget.internalList = { getNodeGroups: () => ( {} ) };
	assert.false( widget.built );

	widget.buildIndex();
	assert.true( widget.built );
	assert.deepEqual( widget.index, [] );

	widget.onInternalListUpdate( [ 'mwReference/' ] );
	assert.false( widget.built );
	assert.deepEqual( widget.index, [] );

	widget.buildIndex();
	assert.true( widget.built );
	assert.deepEqual( widget.index, [] );

	widget.onListNodeUpdate();
	assert.false( widget.built );
	assert.deepEqual( widget.index, [] );
} );

QUnit.test( 'isIndexEmpty', function ( assert ) {
	const widget = new ve.ui.MWReferenceSearchWidget();
	assert.true( widget.isIndexEmpty() );

	// XXX: This is a regression test with a fragile setup. Please feel free to delete this test
	// when you feel like it doesn't make sense to update it.
	const internalList = {
		connect: () => null,
		getListNode: () => ( { connect: () => null } ),
		getNodeGroups: () => ( { 'mwReference/': { indexOrder: [ 0 ] } } )
	};
	widget.setInternalList( internalList );
	assert.false( widget.isIndexEmpty() );
} );

QUnit.test( 'addResults', function ( assert ) {
	const widget = new ve.ui.MWReferenceSearchWidget();
	widget.getQuery().setValue( 'a' );
	widget.index = [ { text: 'a' }, { text: 'b' } ];

	assert.strictEqual( widget.getResults().getItemCount(), 0 );
	widget.addResults();
	assert.strictEqual( widget.getResults().getItemCount(), 1 );
} );
