'use strict';

QUnit.module( 've.ui.MWReferenceDialog (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'useReference', function ( assert ) {
	const dialog = new ve.ui.MWReferenceDialog();

	dialog.referenceGroupInput = new ve.ui.MWReferenceGroupInputWidget( {} );
	dialog.reuseWarning = new OO.ui.MessageWidget();

	// XXX: This is a regression test with a fragile setup. Please feel free to delete this test
	// when you feel like it doesn't make sense to update it.
	dialog.referenceTarget = {
		setDocument: () => null
	};
	dialog.fragment = {
		getDocument: () => ( {
			getInternalList: () => ( {
				getNodeGroup: () => null
			} )
		} )
	};

	const parentDoc = {
		cloneWithData: () => null
	};
	const ref = new ve.dm.MWReferenceModel( parentDoc );
	ref.setGroup( 'g' );
	dialog.useReference( ref );

	assert.strictEqual( dialog.referenceModel, ref );
	assert.strictEqual( dialog.originalGroup, 'g' );
	assert.strictEqual( dialog.referenceGroupInput.getValue(), 'g' );
	assert.false( dialog.referenceGroupInput.isDisabled() );
	assert.false( dialog.reuseWarning.isVisible() );
} );
