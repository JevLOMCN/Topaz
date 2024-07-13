const drawers = require( './drawers.js' );

/*
 * Warn people if they're trying to switch to desktop but have cookies disabled.
 */
module.exports = function ( amcOutreach, currentPage ) {
	/**
	 * Checks whether cookies are enabled
	 *
	 * @method
	 * @ignore
	 * @return {boolean} Whether cookies are enabled
	 */
	function cookiesEnabled() {
		// If session cookie already set, return true
		if ( mw.cookie.get( 'mf_testcookie' ) === 'test_value' ) {
			return true;
			// Otherwise try to set mf_testcookie and return true if it was set
		} else {
			mw.cookie.set( 'mf_testcookie', 'test_value', {
				path: '/'
			} );
			return mw.cookie.get( 'mf_testcookie' ) === 'test_value';
		}
	}

	/**
	 * An event handler for the toggle to desktop link.
	 * If cookies are enabled it will redirect you to desktop site as described in
	 * the link href associated with the handler.
	 * If cookies are not enabled, show a toast and die.
	 *
	 * @method
	 * @ignore
	 * @return {boolean|undefined}
	 */
	function desktopViewClick() {
		if ( !cookiesEnabled() ) {
			mw.notify(
				mw.msg( 'mobile-frontend-cookies-required' ),
				{ type: 'error' }
			);
			// Prevent default action
			return false;
		}
	}

	/**
	 * @method
	 * @ignore
	 * @param {jQuery.Event} ev
	 * @return {boolean|undefined}
	 */
	function amcDesktopClickHandler( ev ) {
		const self = this;
		const executeWrappedEvent = function () {
			if ( desktopViewClick() === false ) {
				return false;
			}

			window.location = self.href;
		};
		const amcCampaign = amcOutreach.loadCampaign();
		const onDismiss = function () {
			executeWrappedEvent();
		};
		const drawer = amcCampaign.showIfEligible(
			amcOutreach.ACTIONS.onDesktopLink,
			onDismiss,
			currentPage.title
		);

		if ( drawer ) {
			ev.preventDefault();
			// stopPropagation is needed to prevent drawer from immediately closing
			// when shown (drawers.js adds a click event to window when drawer is
			// shown
			ev.stopPropagation();

			drawers.displayDrawer( drawer, {} );
			drawers.lockScroll();

			return;
		}

		return executeWrappedEvent();
	}

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '#mw-mf-display-toggle' ).on( 'click', amcDesktopClickHandler );
};
