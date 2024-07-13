module.exports = function () {
	const menus = require( './menu.js' );

	// setup main menu
	menus.init();

	( function ( wgRedirectedFrom ) {
		// If the user has been redirected, then show them a toast message (see
		// https://phabricator.wikimedia.org/T146596).

		if ( wgRedirectedFrom === null ) {
			return;
		}

		const redirectedFrom = mw.Title.newFromText( wgRedirectedFrom );

		if ( redirectedFrom ) {
			// mw.Title.getPrefixedText includes the human-readable namespace prefix.
			const title = redirectedFrom.getPrefixedText();
			const $msg = $( '<div>' ).html(
				mw.message( 'mobile-frontend-redirected-from', title ).parse()
			);
			$msg.find( 'a' ).attr( 'href', mw.util.getUrl( title, { redirect: 'no' } ) );
			mw.notify( $msg );
		}
	}( mw.config.get( 'wgRedirectedFrom' ) ) );

};
