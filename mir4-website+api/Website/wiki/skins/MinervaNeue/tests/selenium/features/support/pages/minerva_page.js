/**
 * Represents a page the can be presented in desktop
 * or mobile mode (requires mobilefrontend), and has
 * features like public 'beta' mode (requires mobilefrontend).
 *
 * @extends Page
 * @example
 * https://en.m.wikipedia.org/wiki/Barack_Obama
 */

'use strict';

const { Page } = require( './mw_core_pages' );

class MinervaPage extends Page {

	get title() {
		return browser.getTitle();
	}

	/**
	 * Opens a page if it isn't already open.
	 *
	 * @param {string} path
	 */
	open( path = 'Main_Page' ) {
		const currentPage = browser.getUrl(),
			newPage = browser.options.baseUrl + '/index.php?title=' + path;
		if ( currentPage !== newPage ) {
			browser.url( newPage );
		}
	}

	/**
	 * Ensure browser is opened on a MediaWiki page, and set a specified
	 * cookie for that domain.
	 *
	 * @param {string} name - name of the cookie
	 * @param {string} value - value of the cookie
	 */
	async setCookie( name, value ) {
		const currentPage = await browser.getUrl();
		if ( !currentPage.includes( await browser.options.baseUrl ) ) {
			this.open();
		}

		const cookie = await browser.getCookies( [ name ] );

		if ( !cookie || cookie.value !== value ) {
			await browser.setCookies( {
				name: name,
				value: value } );
		}
	}

	/**
	 * Set the mobile cookie
	 */
	setMobileMode() {
		this.setCookie( 'mf_useformat', 'true' );
	}

	/**
	 * Set the beta cookie
	 */
	setBetaMode() {
		this.setCookie( 'optin', 'beta' );
	}

	waitUntilResourceLoaderModuleReady( moduleName ) {
		browser.waitUntil( () => {
			const state = browser.execute( ( m ) => {
				return mw.loader.getState( m );
			}, moduleName );
			return state === 'ready';
		} );
	}
}

module.exports = MinervaPage;
