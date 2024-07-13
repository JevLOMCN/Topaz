'use strict';

QUnit.module( 've.ui.MWCitationDialogTool (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'isCompatibleWith', function ( assert ) {
	const model = new ve.dm.MWReferenceNode();
	assert.true( ve.ui.MWCitationDialogTool.static.isCompatibleWith( model ) );
} );
