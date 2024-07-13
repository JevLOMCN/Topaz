( function () {

	mw.hook( 'wikipage.content' ).add( function () {
		var regex = /^mw-scribunto-error-(\w+)/,
			popup;

		$( '.scribunto-error' ).each( function ( index, span ) {
			var matches = regex.exec( span.id );
			if ( matches === null ) {
				mw.log( 'mw.scribunto.errors: regex mismatch!' );
				return;
			}
			var $span = $( span );
			$span.on( 'click', function () {
				var error = mw.config.get( 'ScribuntoErrors-' + matches[ 1 ] );
				if ( typeof error !== 'string' ) {
					mw.log( 'mw.scribunto.errors: error ' + matches[ 1 ] + ' not found.' );
					return;
				}

				if ( !popup ) {
					popup = new OO.ui.PopupWidget( {
						padded: true,
						head: true,
						label: $( '<div>' )
							.text( mw.msg( 'scribunto-parser-dialog-title' ) )
							.addClass( 'scribunto-error-label' )
					} );
					OO.ui.getDefaultOverlay().append( popup.$element );
				}
				popup.$body.html( error );
				popup.setFloatableContainer( $span );
				popup.toggle( true );
			} );
		} );
	} );

}() );
