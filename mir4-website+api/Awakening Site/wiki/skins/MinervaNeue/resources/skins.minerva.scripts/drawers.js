const $drawerContainer = $( document.body );
const BODY_CLASS_SCROLL_LOCKED = 'has-drawer--with-scroll-locked';

/**
 * Discard a drawer from display on the page.
 *
 * @ignore
 * @param {Drawer} drawer
 */
function discardDrawer( drawer ) {
	// remove the class
	$drawerContainer.removeClass( [ BODY_CLASS_SCROLL_LOCKED ] );
	// FIXME: queue removal from DOM (using setTimeout so that any animations have time to run)
	// This works around an issue in MobileFrontend that the Drawer onBeforeHide method is
	// called /before/ the animation for closing has completed. This needs to be accounted
	// for in Drawer so this function can be synchronous.
	setTimeout( function () {
		// detach the node from the DOM. Use detach rather than remove to allow reuse without
		// losing any existing events.
		drawer.$el.detach();
	}, 100 );
}

/**
 * Lock scroll of viewport.
 */
function lockScroll() {
	$drawerContainer.addClass( BODY_CLASS_SCROLL_LOCKED );
}

/**
 * @param {Drawer} drawer to display
 * @param {Object} options for display
 * @param {boolean} options.hideOnScroll whether a scroll closes the drawer
 */
function displayDrawer( drawer, options ) {
	$drawerContainer.append( drawer.$el );
	drawer.show();
	if ( options.hideOnScroll ) {
		$( window ).one( 'scroll.drawer', function () {
			drawer.hide();
		} );
	}
}
module.exports = {
	displayDrawer: displayDrawer,
	lockScroll: lockScroll,
	discardDrawer: discardDrawer
};
