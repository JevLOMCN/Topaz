const BODY_NOTIFICATIONS_REVEAL_CLASS = 'navigation-enabled secondary-navigation-enabled';

/**
 * Wire up the main menu
 */
function init() {

	// See I09c27a084100b223662f84de6cbe01bebe1fe774
	// will trigger every time the Echo notification is opened or closed.
	// This controls the drawer like behaviour of notifications
	// on tablet in mobile mode.
	mw.hook( 'echo.mobile' ).add( function ( isOpen ) {
		if ( isOpen ) {
			$( document.body ).addClass( BODY_NOTIFICATIONS_REVEAL_CLASS );
		} else {
			$( document.body ).removeClass( BODY_NOTIFICATIONS_REVEAL_CLASS );
		}
	} );
}

module.exports = {
	init: init
};
