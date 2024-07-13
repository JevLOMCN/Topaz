( function ( track ) {
	const MAX_PRINT_TIMEOUT = 3000;
	let printSetTimeoutReference = 0;
	const mobile = require( 'mobile.startup' );

	/**
	 * Helper function to detect iOs
	 *
	 * @ignore
	 * @param {string} userAgent User Agent
	 * @return {boolean}
	 */
	function isIos( userAgent ) {
		return /ipad|iphone|ipod/i.test( userAgent );
	}

	/**
	 * Helper function to retrieve the Android version
	 *
	 * @ignore
	 * @param {string} userAgent User Agent
	 * @return {number|boolean} Integer version number, or false if not found
	 */
	function getAndroidVersion( userAgent ) {
		const match = userAgent.toLowerCase().match( /android\s(\d\.]*)/ );
		return match ? parseInt( match[ 1 ] ) : false;
	}

	/**
	 * Helper function to retrieve the Chrome/Chromium version
	 *
	 * @ignore
	 * @param {string} userAgent User Agent
	 * @return {number|boolean} Integer version number, or false if not found
	 */
	function getChromeVersion( userAgent ) {
		const match = userAgent.toLowerCase().match( /chrom(e|ium)\/(\d+)\./ );
		return match ? parseInt( match[ 2 ] ) : false;
	}

	/**
	 * Checks whether DownloadIcon is available for given user agent
	 *
	 * @memberof DownloadIcon
	 * @instance
	 * @param {Window} windowObj
	 * @param {Page} page to download
	 * @param {string} userAgent User agent
	 * @param {number[]} supportedNamespaces where printing is possible
	 * @return {boolean}
	 */
	function isAvailable( windowObj, page, userAgent, supportedNamespaces ) {
		const androidVersion = getAndroidVersion( userAgent );
		const chromeVersion = getChromeVersion( userAgent );

		if ( typeof window.print !== 'function' ) {
			// T309591: No window.print support
			return false;
		}

		// Download button is restricted to certain namespaces T181152.
		// Not shown on missing pages
		// Defaults to 0, in case cached JS has been served.
		if ( supportedNamespaces.indexOf( page.getNamespaceId() ) === -1 ||
			page.isMainPage() || page.isMissing ) {
			// namespace is not supported or it's a main page
			return false;
		}

		if ( isIos( userAgent ) || chromeVersion === false ||
			windowObj.chrome === undefined
		) {
			// we support only chrome/chromium on desktop/android
			return false;
		}
		if ( ( androidVersion && androidVersion < 5 ) || chromeVersion < 41 ) {
			return false;
		}
		return true;
	}
	/**
	 * onClick handler for button that invokes print function
	 *
	 * @param {HTMLElement} portletItem
	 * @param {Icon} spinner
	 * @param {Function} [loadAllImagesInPage]
	 */
	function onClick( portletItem, spinner, loadAllImagesInPage ) {
		const icon = portletItem.querySelector( '.minerva-icon--download' );
		function doPrint() {
			printSetTimeoutReference = clearTimeout( printSetTimeoutReference );
			track( 'minerva.downloadAsPDF', {
				action: 'callPrint'
			} );
			window.print();
			$( icon ).show();
			spinner.$el.hide();
		}

		function doPrintBeforeTimeout() {
			if ( printSetTimeoutReference ) {
				doPrint();
			}
		}
		// The click handler may be invoked multiple times so if a pending print is occurring
		// do nothing.
		if ( !printSetTimeoutReference ) {
			track( 'minerva.downloadAsPDF', {
				action: 'fetchImages'
			} );
			$( icon ).hide();
			spinner.$el.show();
			// If all image downloads are taking longer to load then the MAX_PRINT_TIMEOUT
			// abort the spinner and print regardless.
			printSetTimeoutReference = setTimeout( doPrint, MAX_PRINT_TIMEOUT );
			( loadAllImagesInPage || mobile.loadAllImagesInPage )()
				.then( doPrintBeforeTimeout, doPrintBeforeTimeout );
		}
	}

	/**
	 * Generate a download icon for triggering print functionality if
	 * printing is available.
	 * Calling this method has side effects:
	 * It calls mw.util.addPortletLink and may inject an element into the page.
	 *
	 * @param {Page} page
	 * @param {number[]} supportedNamespaces
	 * @param {Window} [windowObj] window object
	 * @param {boolean} [overflowList] Append to overflow list
	 * @return {jQuery|null}
	 */
	function downloadPageAction( page, supportedNamespaces, windowObj, overflowList ) {
		const spinner = ( overflowList ) ? mobile.spinner( {
			label: '',
			isIconOnly: false
		} ) : mobile.spinner();

		if (
			isAvailable(
				windowObj, page, navigator.userAgent,
				supportedNamespaces
			)
		) {
			// FIXME: Use p-views when cache has cleared.
			const actionID = document.querySelector( '#p-views' ) ? 'p-views' : 'page-actions';
			const portletLink = mw.util.addPortletLink(
				overflowList ? 'page-actions-overflow' : actionID,
				'#',
				mw.msg( 'minerva-download' ),
				// id
				'minerva-download',
				// tooltip
				mw.msg( 'minerva-download' ),
				// access key
				'p',
				overflowList ? null : document.getElementById( 'page-actions-watch' )
			);
			if ( portletLink ) {
				portletLink.addEventListener( 'click', function () {
					onClick( portletLink, spinner, mobile.loadAllImagesInPage );
				} );
				const iconElement = portletLink.querySelector( '.minerva-icon' );
				if ( iconElement ) {
					iconElement.classList.add( 'minerva-icon--download' );
				}
				spinner.$el.hide().insertBefore(
					$( portletLink ).find( '.minerva-icon' )
				);
			}
			return portletLink;
		} else {
			return null;
		}
	}

	module.exports = {
		downloadPageAction,
		test: {
			isAvailable,
			onClick
		}
	};

}( mw.track ) );
