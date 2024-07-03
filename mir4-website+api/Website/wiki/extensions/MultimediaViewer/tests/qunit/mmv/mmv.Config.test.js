/*
 * This file is part of the MediaWiki extension MediaViewer.
 *
 * MediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

const { isMediaViewerEnabledOnClick } = require( 'mmv.head' );
const { Config } = require( 'mmv.bootstrap' );
const { createLocalStorage, getDisabledLocalStorage, getFakeLocalStorage, getUnsupportedLocalStorage } = require( './mmv.testhelpers.js' );

( function () {
	QUnit.module( 'mmv.Config', QUnit.newMwEnvironment() );

	QUnit.test( 'Constructor sense test', function ( assert ) {
		const config = new Config( {}, {}, {}, {}, null );
		assert.true( config instanceof Config );
	} );

	QUnit.test( 'Localstorage get', function ( assert ) {
		let localStorage, config;

		localStorage = getUnsupportedLocalStorage(); // no browser support
		config = new Config( {}, {}, {}, {}, localStorage );
		assert.strictEqual( config.getFromLocalStorage( 'foo' ), null, 'Returns null when not supported' );
		assert.strictEqual( config.getFromLocalStorage( 'foo', 'bar' ), 'bar', 'Returns fallback when not supported' );

		localStorage = getDisabledLocalStorage(); // browser supports it but disabled
		config = new Config( {}, {}, {}, {}, localStorage );
		assert.strictEqual( config.getFromLocalStorage( 'foo' ), null, 'Returns null when disabled' );
		assert.strictEqual( config.getFromLocalStorage( 'foo', 'bar' ), 'bar', 'Returns fallback when disabled' );

		localStorage = createLocalStorage( { getItem: this.sandbox.stub() } );
		config = new Config( {}, {}, {}, {}, localStorage );

		localStorage.store.getItem.withArgs( 'foo' ).returns( null );
		assert.strictEqual( config.getFromLocalStorage( 'foo' ), null, 'Returns null when key not set' );
		assert.strictEqual( config.getFromLocalStorage( 'foo', 'bar' ), 'bar', 'Returns fallback when key not set' );

		localStorage.store.getItem.reset();
		localStorage.store.getItem.withArgs( 'foo' ).returns( 'boom' );
		assert.strictEqual( config.getFromLocalStorage( 'foo' ), 'boom', 'Returns correct value' );
		assert.strictEqual( config.getFromLocalStorage( 'foo', 'bar' ), 'boom', 'Returns correct value ignoring fallback' );
	} );

	QUnit.test( 'Localstorage set', function ( assert ) {
		let localStorage, config;

		localStorage = getUnsupportedLocalStorage(); // no browser support
		config = new Config( {}, {}, {}, {}, localStorage );
		assert.strictEqual( config.setInLocalStorage( 'foo', 'bar' ), false, 'Returns false when not supported' );

		localStorage = getDisabledLocalStorage(); // browser supports it but disabled
		config = new Config( {}, {}, {}, {}, localStorage );
		assert.strictEqual( config.setInLocalStorage( 'foo', 'bar' ), false, 'Returns false when disabled' );

		localStorage = createLocalStorage( { setItem: this.sandbox.stub(), removeItem: this.sandbox.stub() } );
		config = new Config( {}, {}, {}, {}, localStorage );

		assert.strictEqual( config.setInLocalStorage( 'foo', 'bar' ), true, 'Returns true when works' );

		localStorage.store.setItem.throwsException( 'localStorage full!' );
		assert.strictEqual( config.setInLocalStorage( 'foo', 'bar' ), false, 'Returns false on error' );
	} );

	QUnit.test( 'Localstorage remove', function ( assert ) {
		let localStorage, config;

		localStorage = getUnsupportedLocalStorage(); // no browser support
		config = new Config( {}, {}, {}, {}, localStorage );
		assert.strictEqual( config.removeFromLocalStorage( 'foo' ), true, 'Returns true when not supported' );

		localStorage = getDisabledLocalStorage(); // browser supports it but disabled
		config = new Config( {}, {}, {}, {}, localStorage );
		assert.strictEqual( config.removeFromLocalStorage( 'foo' ), true, 'Returns true when disabled' );

		localStorage = createLocalStorage( { removeItem: this.sandbox.stub() } );
		config = new Config( {}, {}, {}, {}, localStorage );
		assert.strictEqual( config.removeFromLocalStorage( 'foo' ), true, 'Returns true when works' );
	} );

	QUnit.test( 'isMediaViewerEnabledOnClick', function ( assert ) {
		const localStorage = createLocalStorage( { getItem: this.sandbox.stub() } );
		const mwConfig = { get: this.sandbox.stub() };
		const mwUser = { isNamed: this.sandbox.stub() };

		mwUser.isNamed.returns( true );
		mwConfig.get.withArgs( 'wgMediaViewer' ).returns( true );
		mwConfig.get.withArgs( 'wgMediaViewerOnClick' ).returns( true );
		assert.strictEqual( isMediaViewerEnabledOnClick( mwConfig, mwUser, localStorage ), true, 'Returns true for logged-in with standard settings' );

		mwUser.isNamed.returns( true );
		mwConfig.get.withArgs( 'wgMediaViewer' ).returns( false );
		mwConfig.get.withArgs( 'wgMediaViewerOnClick' ).returns( true );
		assert.strictEqual( isMediaViewerEnabledOnClick( mwConfig, mwUser, localStorage ), false, 'Returns false if opted out via user JS flag' );

		mwUser.isNamed.returns( true );
		mwConfig.get.withArgs( 'wgMediaViewer' ).returns( true );
		mwConfig.get.withArgs( 'wgMediaViewerOnClick' ).returns( false );
		assert.strictEqual( isMediaViewerEnabledOnClick( mwConfig, mwUser, localStorage ), false, 'Returns false if opted out via preferences' );

		mwUser.isNamed.returns( false );
		mwConfig.get.withArgs( 'wgMediaViewer' ).returns( false );
		mwConfig.get.withArgs( 'wgMediaViewerOnClick' ).returns( true );
		assert.strictEqual( isMediaViewerEnabledOnClick( mwConfig, mwUser, localStorage ), false, 'Returns false if anon user opted out via user JS flag' );

		mwUser.isNamed.returns( false );
		mwConfig.get.withArgs( 'wgMediaViewer' ).returns( true );
		mwConfig.get.withArgs( 'wgMediaViewerOnClick' ).returns( false );
		assert.strictEqual( isMediaViewerEnabledOnClick( mwConfig, mwUser, localStorage ), false, 'Returns false if anon user opted out in some weird way' ); // apparently someone created a browser extension to do this

		mwUser.isNamed.returns( false );
		mwConfig.get.withArgs( 'wgMediaViewer' ).returns( true );
		mwConfig.get.withArgs( 'wgMediaViewerOnClick' ).returns( true );
		localStorage.store.getItem.withArgs( 'wgMediaViewerOnClick' ).returns( null );
		assert.strictEqual( isMediaViewerEnabledOnClick( mwConfig, mwUser, localStorage ), true, 'Returns true for anon with standard settings' );

		mwUser.isNamed.returns( false );
		mwConfig.get.withArgs( 'wgMediaViewer' ).returns( true );
		mwConfig.get.withArgs( 'wgMediaViewerOnClick' ).returns( true );
		localStorage.store.getItem.withArgs( 'wgMediaViewerOnClick' ).returns( '0' );
		assert.strictEqual( isMediaViewerEnabledOnClick( mwConfig, mwUser, localStorage ), false, 'Returns true for anon opted out via localSettings' );
	} );

	QUnit.test( 'setMediaViewerEnabledOnClick sense check', function ( assert ) {
		const localStorage = createLocalStorage( {
			getItem: this.sandbox.stub(),
			setItem: this.sandbox.stub(),
			removeItem: this.sandbox.stub()
		} );
		const mwUser = { isNamed: this.sandbox.stub() };
		const mwConfig = new Map();
		const api = { saveOption: this.sandbox.stub().returns( $.Deferred().resolve() ) };
		const config = new Config( {}, mwConfig, mwUser, api, localStorage );
		mwConfig.set( 'wgMediaViewerEnabledByDefault', false );

		mwUser.isNamed.returns( true );
		api.saveOption.returns( $.Deferred().resolve() );
		config.setMediaViewerEnabledOnClick( false );
		assert.true( api.saveOption.called, 'For logged-in users, pref change is via API' );

		mwUser.isNamed.returns( false );
		config.setMediaViewerEnabledOnClick( false );
		assert.true( localStorage.store.setItem.called, 'For anons, opt-out is set in localStorage' );

		mwUser.isNamed.returns( false );
		config.setMediaViewerEnabledOnClick( true );
		assert.true( localStorage.store.removeItem.called, 'For anons, opt-in means clearing localStorage' );
	} );

	QUnit.test( 'shouldShowStatusInfo', function ( assert ) {
		const mwConfig = new Map();
		const fakeLocalStorage = getFakeLocalStorage();
		const mwUser = { isNamed: this.sandbox.stub() };
		const api = { saveOption: this.sandbox.stub().returns( $.Deferred().resolve() ) };

		mwConfig.set( 'wgMediaViewer', true );
		mwConfig.set( 'wgMediaViewerOnClick', true );
		mwConfig.set( 'wgMediaViewerEnabledByDefault', true );
		const config = new Config( {}, mwConfig, mwUser, api, fakeLocalStorage );
		mwUser.isNamed.returns( true );

		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Status info is not shown by default' );
		config.setMediaViewerEnabledOnClick( false );
		assert.strictEqual( config.shouldShowStatusInfo(), true, 'Status info is shown after MMV is disabled the first time' );
		config.setMediaViewerEnabledOnClick( true );
		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Status info is not shown when MMV is enabled' );
		config.setMediaViewerEnabledOnClick( false );
		assert.strictEqual( config.shouldShowStatusInfo(), true, 'Status info is shown after MMV is disabled the first time #2' );
		config.disableStatusInfo();
		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Status info is not shown when already displayed once' );
		config.setMediaViewerEnabledOnClick( true );
		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Further status changes have no effect' );
		config.setMediaViewerEnabledOnClick( false );
		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Further status changes have no effect #2' );

		// make sure disabling calls maybeEnableStatusInfo() for logged-in as well
		config.localStorage = getFakeLocalStorage();
		mwUser.isNamed.returns( false );
		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Status info is not shown by default for logged-in users' );
		config.setMediaViewerEnabledOnClick( false );
		assert.strictEqual( config.shouldShowStatusInfo(), true, 'Status info is shown after MMV is disabled the first time for logged-in users' );

		// make sure popup is not shown immediately on disabled-by-default sites, but still works otherwise
		config.localStorage = getFakeLocalStorage();
		mwConfig.set( 'wgMediaViewerEnabledByDefault', false );
		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Status info is not shown by default #2' );
		config.setMediaViewerEnabledOnClick( true );
		assert.strictEqual( config.shouldShowStatusInfo(), false, 'Status info is not shown when MMV is enabled #2' );
		config.setMediaViewerEnabledOnClick( false );
		assert.strictEqual( config.shouldShowStatusInfo(), true, 'Status info is shown after MMV is disabled the first time #2' );
	} );
}() );
