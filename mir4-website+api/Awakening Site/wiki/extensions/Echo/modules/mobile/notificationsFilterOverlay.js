var Overlay = require( 'mobile.startup' ).Overlay;

/**
 * Overlay for notifications filter
 *
 * @class NotificationsFilterOverlay
 * @param {Object} options
 * @param {Function} options.onBeforeExit executes before overlay closes
 * @param {jQuery.Object} options.$notifReadState - notification read status widgets
 * @param {jQuery.Object} options.$crossWikiUnreadFilter - notification unread filter
 *
 */
function notificationsFilterOverlay( options ) {
	// Don't call overlay.hide(), because that doesn't invoke the onBeforeExit callback (T258954)
	// Instead, change the hash, so that the OverlayManager hides the overlay for us
	function hideOverlay() {
		location.hash = '#';
	}

	// Close overlay when a selection is made
	options.$crossWikiUnreadFilter.on( 'click', hideOverlay );
	options.$notifReadState.find( '.oo-ui-buttonElement' ).on( 'click', hideOverlay );

	var $content = $( '<div>' ).append(
		$( '<div>' )
			.addClass( 'notifications-filter-overlay-read-state' )
			.append( options.$notifReadState ),
		options.$crossWikiUnreadFilter
	);

	var overlay = Overlay.make( {
		onBeforeExit: options.onBeforeExit,
		heading: '<strong>' + mw.message( 'echo-mobile-notifications-filter-title' ).escaped() + '</strong>',
		className: 'overlay notifications-filter-overlay notifications-overlay navigation-drawer'
	}, { $el: $content } );
	return overlay;
}

module.exports = notificationsFilterOverlay;
