'use strict';

QUnit.module( 've.ui.MWReferenceContextItem (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'getReferenceNode', function ( assert ) {
	// XXX: This is a regression test with a fragile setup. Please feel free to delete this test
	// when you feel like it doesn't make sense to update it.
	const context = {
		isMobile: () => false,
		getSurface: () => ( {
			isReadOnly: () => false
		} )
	};
	const model = { isEditable: () => false };

	const item = new ve.ui.MWReferenceContextItem( context, model );
	assert.strictEqual( item.getReferenceNode(), null );
} );
