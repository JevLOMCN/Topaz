QUnit.module( 've.ui.MWTransclusionOutlineButtonWidget' );

QUnit.test( 'Constructor', ( assert ) => {
	const widget = new ve.ui.MWTransclusionOutlineButtonWidget( {} );

	// eslint-disable-next-line no-jquery/no-class-state
	assert.true( widget.$element.hasClass( 've-ui-mwTransclusionOutlineButtonWidget' ) );
} );

QUnit.test( 'onKeyDown', ( assert ) => {
	const done = assert.async(),
		widget = new ve.ui.MWTransclusionOutlineButtonWidget( {} ),
		event = $.Event( 'keydown', { which: 32 } );

	widget.on( 'keyPressed', ( key ) => {
		assert.strictEqual( key, 32 );
		done();
	} );
	widget.onKeyDown( event );
} );
