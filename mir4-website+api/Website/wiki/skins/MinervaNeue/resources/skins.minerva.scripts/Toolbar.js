const
	mobile = require( 'mobile.startup' ),
	ToggleList = require( '../../includes/Skins/ToggleList/ToggleList.js' ),
	page = mobile.currentPage(),
	/** The top level menu. */
	selector = '.page-actions-menu',
	/** The secondary overflow submenu component container. */
	overflowSubmenuSelector = '#page-actions-overflow',
	overflowListSelector = '.toggle-list__list';

/**
 * @param {Window} window
 * @param {Element} toolbar
 */
function bind( window, toolbar ) {
	const overflowSubmenu = toolbar.querySelector( overflowSubmenuSelector );
	if ( overflowSubmenu ) {
		ToggleList.bind( window, overflowSubmenu );
	}
}

/**
 * @param {Window} window
 * @param {Element} toolbar
 */
function render( window, toolbar ) {
	const overflowList = toolbar.querySelector( overflowListSelector );
	checkForReadOnlyMode();
	renderDownloadButton( window, overflowList );
}

/**
 * Initialize page edit action link (#ca-edit) for read only mode.
 * (e.g. when $wgReadOnly is set in LocalSettings.php)
 *
 * Mark the edit link as disabled if the user is not actually able to edit the page for some
 * reason (e.g. page is protected or user is blocked).
 *
 * Note that the link is still clickable, but clicking it will probably open a view-source
 * form or display an error message, rather than open an edit form.
 *
 * This check occurs in JavaScript as anonymous page views are cached
 * in Varnish.
 *
 * @ignore
 */
function checkForReadOnlyMode() {
	if ( mw.config.get( 'wgMinervaReadOnly' ) ) {
		document.body.classList.add( 'minerva-read-only' );
	}
}

/**
 * Initialize and inject the download button
 *
 * There are many restrictions when we can show the download button, this function should handle
 * all device/os/operating system related checks and if device supports printing it will inject
 * the Download icon
 *
 * @param {Window} window
 * @param {Element|null} overflowList
 */
function renderDownloadButton( window, overflowList ) {
	const downloadPageAction = require( './downloadPageAction.js' ).downloadPageAction,
		$downloadAction = downloadPageAction( page,
			mw.config.get( 'wgMinervaDownloadNamespaces', [] ), window, !!overflowList );

	if ( $downloadAction ) {
		mw.track( 'minerva.downloadAsPDF', {
			action: 'buttonVisible'
		} );
	}
}

module.exports = {
	selector,
	bind,
	render
};
