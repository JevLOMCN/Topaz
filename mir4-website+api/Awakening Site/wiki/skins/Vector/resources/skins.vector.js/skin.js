const languageButton = require( './languageButton.js' ),
	limitedWidthToggle = require( './limitedWidthToggle.js' ),
	pinnableElement = require( './pinnableElement.js' ),
	searchToggle = require( './searchToggle.js' ),
	echo = require( './echo.js' ),
	initExperiment = require( './AB.js' ),
	ABTestConfig = require( /** @type {string} */ ( './activeABTest.json' ) ),
	initSearchLoader = require( './searchLoader.js' ).initSearchLoader,
	portletsManager = require( './portlets.js' ),
	dropdownMenus = require( './dropdownMenus.js' ).dropdownMenus,
	watchstar = require( './watchstar.js' ).init,
	setupIntersectionObservers = require( './setupIntersectionObservers.js' ),
	menuTabs = require( './menuTabs.js' ),
	legacyMessageBoxStyles = require( './legacyMessageBoxStyles.js' ),
	teleportTarget = /** @type {HTMLElement} */require( /** @type {string} */ ( 'mediawiki.page.ready' ) ).teleportTarget;

/**
 * Wait for first paint before calling this function. That's its whole purpose.
 *
 * Some CSS animations and transitions are "disabled" by default as a workaround to this old Chrome
 * bug, https://bugs.chromium.org/p/chromium/issues/detail?id=332189, which otherwise causes them to
 * render in their terminal state on page load. By adding the `vector-animations-ready` class to the
 * `html` root element **after** first paint, the animation selectors suddenly match causing the
 * animations to become "enabled" when they will work properly. A similar pattern is used in Minerva
 * (see T234570#5779890, T246419).
 *
 * Example usage in Less:
 *
 * ```less
 * .foo {
 *     color: #f00;
 *     transform: translateX( -100% );
 * }
 *
 * // This transition will be disabled initially for JavaScript users. It will never be enabled for
 * // non-JavaScript users.
 * .vector-animations-ready .foo {
 *     transition: transform 100ms ease-out;
 * }
 * ```
 *
 * @param {Document} document
 * @return {void}
 */
function enableCssAnimations( document ) {
	document.documentElement.classList.add( 'vector-animations-ready' );
}

/**
 * @param {Window} window
 * @return {void}
 */
function main( window ) {
	enableCssAnimations( window.document );
	initSearchLoader( document );
	languageButton();
	echo();
	portletsManager.main();
	watchstar();
	limitedWidthToggle();
	// Initialize the search toggle for the main header only. The sticky header
	// toggle is initialized after Codex search loads.
	const searchToggleElement = document.querySelector( '.mw-header .search-toggle' );
	if ( searchToggleElement ) {
		searchToggle( searchToggleElement );
	}
	pinnableElement.initPinnableElement();
	// Initializes the TOC and sticky header, behaviour of which depend on scroll behaviour.
	setupIntersectionObservers.main();
	// Apply body styles to teleported elements
	teleportTarget.classList.add( 'vector-body' );

	// Load client preferences
	const clientPreferenceSelector = '#vector-client-prefs';
	const clientPreferenceExists = document.querySelectorAll( clientPreferenceSelector ).length > 0;
	if ( clientPreferenceExists ) {
		mw.loader.using( [
			'skins.vector.clientPreferences',
			'skins.vector.search.codex.styles',
			'skins.vector.search.codex.scripts'
		] ).then( () => {
			const clientPreferences = require( /** @type {string} */ ( 'skins.vector.clientPreferences' ) );
			const clientPreferenceConfig = ( require( './clientPreferences.json' ) );
			// Can be removed once wgVectorNightMode is removed.
			if ( document.documentElement.classList.contains( 'vector-feature-night-mode-disabled' ) ) {
				// @ts-ignore issues relating to delete operator are not relevant here.
				delete clientPreferenceConfig[ 'skin-theme' ];
			}
			clientPreferences.render( clientPreferenceSelector, clientPreferenceConfig );
		} );
	}

	dropdownMenus();
	// menuTabs should follow `dropdownMenus` as that can move menu items from a
	// tab menu to a dropdown.
	menuTabs();
}

/**
 * @param {Window} window
 * @return {void}
 */
function init( window ) {
	const now = mw.now();
	legacyMessageBoxStyles();
	// This is the earliest time we can run JS for users (and bucket anonymous
	// users for A/B tests).
	// Where the browser supports it, for a 10% sample of users
	// we record a value to give us a sense of the expected delay in running A/B tests or
	// disabling JS features. This will inform us on various things including what to expect
	// with regards to delay while running A/B tests to anonymous users.
	// When EventLogging is not available this will reject.
	// This code can be removed by the end of the Desktop improvements project.
	// https://www.mediawiki.org/wiki/Desktop_improvements
	mw.loader.using( 'ext.eventLogging' ).then( function () {
		if (
			mw.eventLog &&
			mw.eventLog.eventInSample( 100 /* 1 in 100 */ ) &&
			window.performance &&
			window.performance.timing &&
			window.performance.timing.navigationStart
		) {
			mw.track( 'timing.Vector.ready', now - window.performance.timing.navigationStart ); // milliseconds
		}
	} );
}

init( window );
if ( ABTestConfig.enabled && !mw.user.isAnon() ) {
	initExperiment( ABTestConfig, String( mw.user.getId() ) );
}
if ( document.readyState === 'interactive' || document.readyState === 'complete' ) {
	main( window );
} else {
	// This is needed when document.readyState === 'loading'.
	document.addEventListener( 'DOMContentLoaded', function () {
		main( window );
	} );
}

// Provider of skins.vector.js module:
/**
 * skins.vector.js
 * @stable for use inside WikimediaEvents ONLY.
 */
module.exports = { pinnableElement };
