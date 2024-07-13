'use strict';
const reportIfNightModeWasDisabledOnPage = require(
	'../../../resources/skins.minerva.scripts/reportIfNightModeWasDisabledOnPage.js'
);
const nightModeDisabledDoc = document.createElement( 'html' );
nightModeDisabledDoc.setAttribute( 'class', 'skin-night-mode-page-disabled' );

const userOptionsEnabled = new Map();
userOptionsEnabled.set( 'minerva-theme', 'night' );
const userOptionsDisabled = new Map();
userOptionsDisabled.set( 'minerva-theme', 'day' );
const userOptionsAutomatic = new Map();
userOptionsAutomatic.set( 'minerva-theme', 'os' );

const notify = () => {};
const msg = () => {};

describe( 'reportIfNightModeWasDisabledOnPage.js', () => {
	it( 'returns false if no skin-night-mode-page-disabled class is on the document element', () => {
		global.mw = {};
		expect( reportIfNightModeWasDisabledOnPage( document.createElement( 'html' ) ) ).toBe( false );
	} );

	it( 'shows notification for logged in users with night mode preferred, reading from options', () => {
		global.mw = {
			msg,
			notify,
			user: {
				isNamed: () => true,
				options: userOptionsEnabled
			}
		};
		expect( reportIfNightModeWasDisabledOnPage( nightModeDisabledDoc ) ).toBe( true );
	} );

	it( 'shows no notification for logged in users with night mode disabled, reading from options', () => {
		global.mw = {
			msg,
			notify,
			user: {
				isNamed: () => true,
				options: userOptionsDisabled
			}
		};
		expect( reportIfNightModeWasDisabledOnPage( nightModeDisabledDoc ) ).toBe( false );
	} );

	it( 'respects automatic mode when displaying a notification for logged in users', () => {
		global.mw = {
			msg,
			notify,
			user: {
				isNamed: () => true,
				options: userOptionsAutomatic
			}
		};
		window.matchMedia = () => {
			return {
				matches: true
			};
		};
		expect( reportIfNightModeWasDisabledOnPage( nightModeDisabledDoc ) ).toBe( true );
		window.matchMedia = () => {
			return {
				matches: false
			};
		};
		expect( reportIfNightModeWasDisabledOnPage( nightModeDisabledDoc ) ).toBe( false );
	} );

	it( 'reads from cookie for anons', () => {
		global.mw = {
			msg,
			notify,
			cookie: {
				get: () => 'skin-theme-clientpref-night'
			},
			user: {
				isNamed: () => false,
				options: userOptionsDisabled
			}
		};
		expect( reportIfNightModeWasDisabledOnPage( nightModeDisabledDoc ) ).toBe( true );
	} );

	it( 'reads from cookie for anons, can handle unset cookie', () => {
		global.mw = {
			msg,
			notify,
			cookie: {
				get: () => null
			},
			user: {
				isNamed: () => false,
				options: userOptionsDisabled
			}
		};
		expect( reportIfNightModeWasDisabledOnPage( nightModeDisabledDoc ) ).toBe( false );
	} );
} );
