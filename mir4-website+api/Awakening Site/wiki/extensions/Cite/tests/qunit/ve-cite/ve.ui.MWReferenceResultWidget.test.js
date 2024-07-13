'use strict';

QUnit.module( 've.ui.MWReferenceResultWidget (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'Initialization', function ( assert ) {
	const widget = new ve.ui.MWReferenceResultWidget();
	assert.true( widget instanceof OO.ui.OptionWidget );
	assert.strictEqual( widget.$element.children( '.ve-ui-mwReferenceResultWidget-shield' ).length, 1 );
} );
