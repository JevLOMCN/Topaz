'use strict';

QUnit.module( 've.ui.MWUseExistingReferenceCommand (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'Constructor', function ( assert ) {
	const command = new ve.ui.MWUseExistingReferenceCommand();
	assert.strictEqual( command.name, 'reference/existing' );
	assert.strictEqual( command.action, 'window' );
	assert.strictEqual( command.method, 'open' );
} );

QUnit.test( 'isExecutable', function ( assert ) {
	const command = new ve.ui.MWUseExistingReferenceCommand();

	// XXX: This is a regression test with a fragile setup. Please feel free to delete this test
	// when you feel like it doesn't make sense to update it.
	const groups = {};
	const fragment = {
		getDocument: () => ( {
			getInternalList: () => ( {
				getNodeGroups: () => groups
			} )
		} ),
		getSelection: () => ( {
			getName: () => 'linear'
		} )
	};
	assert.false( command.isExecutable( fragment ) );

	groups[ 'mwReference/' ] = { indexOrder: [ 0 ] };
	assert.true( command.isExecutable( fragment ) );
} );
