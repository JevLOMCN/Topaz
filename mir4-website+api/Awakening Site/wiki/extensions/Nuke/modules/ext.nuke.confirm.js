( function () {
	$( function () {
		// Confirm nuke
		// eslint-disable-next-line no-jquery/no-global-selector
		$( 'form[name="nukelist"]' ).on( 'submit', function () {
			var $pages = $( this ).find( 'input[name="pages[]"][type="checkbox"]:checked' );
			if ( $pages.length ) {
				// eslint-disable-next-line no-alert
				return confirm( mw.msg( 'nuke-confirm', $pages.length ) );
			}
		} );

	} );
}() );
