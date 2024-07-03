$( function () {
	var location = mw.config.get( 'wgLinterErrorLocation' );

	function highlightPosition( $textbox ) {
		$textbox.trigger( 'focus' ).textSelection( 'setSelection', { start: location[ 0 ], end: location[ 1 ] } );
		$textbox.textSelection( 'scrollToCaretPosition' );
	}

	if ( location ) {
		// eslint-disable-next-line no-jquery/no-global-selector
		highlightPosition( $( '#wpTextbox1' ) );

		mw.hook( 've.wikitextInteractive' ).add( function () {
			if ( mw.libs.ve.tempWikitextEditor ) {
				highlightPosition( mw.libs.ve.tempWikitextEditor.$element );
			} else {
				// VE dummy textbox
				// eslint-disable-next-line no-jquery/no-global-selector
				highlightPosition( $( '#wpTextbox1' ) );
			}
		} );
	}
} );
