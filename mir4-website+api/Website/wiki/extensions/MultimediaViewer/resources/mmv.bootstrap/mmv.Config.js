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
( function () {

	/**
	 * Contains/retrieves configuration/environment information for MediaViewer.
	 */
	class Config {
		/**
		 * @param {Object} viewerConfig
		 * @param {Map} mwConfig
		 * @param {Object} mwUser
		 * @param {mw.Api} api
		 * @param {mw.SafeStorage} localStorage
		 */
		constructor( viewerConfig, mwConfig, mwUser, api, localStorage ) {
			/**
			 * A plain object storing MediaViewer-specific settings
			 *
			 * @type {Object}
			 */
			this.viewerConfig = viewerConfig;

			/**
			 * The mw.config object, for dependency injection
			 *
			 * @type {Map}
			 */
			this.mwConfig = mwConfig;

			/**
			 * mw.user object, for dependency injection
			 *
			 * @type {Object}
			 */
			this.mwUser = mwUser;

			/**
			 * API object, for dependency injection
			 *
			 * @type {mw.Api}
			 */
			this.api = api;

			/**
			 * The localStorage object, for dependency injection
			 *
			 * @type {mw.SafeStorage}
			 */
			this.localStorage = localStorage;
		}

		/**
		 * Get value from local storage or fail gracefully.
		 *
		 * @param {string} key
		 * @param {any} [fallback] value to return when key is not set or localStorage is not supported
		 * @return {string|null} stored value or fallback or null if neither exists
		 */
		getFromLocalStorage( key, fallback ) {
			const value = this.localStorage.get( key );

			// localStorage will only store strings; if values `null`, `false` or
			// `0` are set, they'll come out as `"null"`, `"false"` or `"0"`, so we
			// can be certain that an actual null is a failure to locate the item,
			// and false is an issue with localStorage itself
			if ( value !== null && value !== false ) {
				return value;
			}

			if ( value === null ) {
				mw.log( `Failed to fetch item ${ key } from localStorage` );
			}

			return fallback !== undefined ? fallback : null;
		}

		/**
		 * Set item in local storage or fail gracefully.
		 *
		 * @param {string} key
		 * @param {any} value
		 * @return {boolean} whether storing the item was successful
		 */
		setInLocalStorage( key, value ) {
			return this.localStorage.set( key, value );
		}

		/**
		 * Remove item from local storage or fail gracefully.
		 *
		 * @param {string} key
		 * @return {boolean} whether storing the item was successful
		 */
		removeFromLocalStorage( key ) {
			this.localStorage.remove( key );

			// mw.storage.remove catches all exceptions and returns false if any
			// occur, so we can't distinguish between actual issues, and
			// localStorage not being supported - however, localStorage.removeItem
			// is not documented to throw any errors, so nothing to worry about;
			// when localStorage is not supported, we'll consider removal successful
			// (it can't have been there in the first place)
			return true;
		}

		/**
		 * Set user preference via AJAX
		 *
		 * @param {string} key
		 * @param {string} value
		 * @return {jQuery.Promise} a deferred which resolves/rejects on success/failure respectively
		 */
		setUserPreference( key, value ) {
			return this.api.saveOption( key, value );
		}

		/**
		 * (Semi-)permanently stores the setting whether MediaViewer should handle thumbnail clicks.
		 * - for logged-in users, we use preferences
		 * - for anons, we use localStorage
		 * - for anons with old browsers, we don't do anything
		 *
		 * @param {boolean} enabled
		 * @return {jQuery.Promise} a deferred which resolves/rejects on success/failure respectively
		 */
		setMediaViewerEnabledOnClick( enabled ) {
			const defaultPrefValue = this.mwConfig.get( 'wgMediaViewerEnabledByDefault' );
			let deferred;
			let newPrefValue;
			let success = true;

			if ( !this.mwUser.isNamed() ) {
				if ( !enabled ) {
					success = this.setInLocalStorage( 'wgMediaViewerOnClick', '0' ); // localStorage stringifies everything, best use strings in the first place
				} else {
					success = this.removeFromLocalStorage( 'wgMediaViewerOnClick' );
				}
				if ( success ) {
					deferred = $.Deferred().resolve();
				} else {
					deferred = $.Deferred().reject();
				}
			} else {
				// Simulate changing the option in Special:Preferences. Turns out this is quite hard (bug 69942):
				// we need to delete the user_properties row if the new setting is the same as the default,
				// otherwise set '1' for enabled, '' for disabled. In theory the pref API will delete the row
				// if the new value equals the default, but this does not always work.
				if ( defaultPrefValue === true ) {
					newPrefValue = enabled ? '1' : '';
				} else {
					// undefined will cause the API call to omit the optionvalue parameter
					// which in turn will cause the options API to delete the row and revert the pref to default
					newPrefValue = enabled ? '1' : undefined;
				}
				deferred = this.setUserPreference( 'multimediaviewer-enable', newPrefValue );
			}

			return deferred.done( () => {
				// make the change work without a reload
				this.mwConfig.set( 'wgMediaViewerOnClick', enabled );
				if ( !enabled ) {
					// set flag for showing a popup if this was a first-time disable
					this.maybeEnableStatusInfo();
				}
			} );
		}

		/**
		 * True if info about enable/disable status should be displayed (mingle #719).
		 *
		 * @return {boolean}
		 */
		shouldShowStatusInfo() {
			return !isMediaViewerEnabledOnClick( this.mwConfig, this.mwUser, this.localStorage ) && this.getFromLocalStorage( 'mmv-showStatusInfo' ) === '1';
		}

		/**
		 * Called when MediaViewer is disabled. If status info was never displayed before, future
		 * shouldShowStatusInfo() calls will return true.
		 *
		 * @private
		 */
		maybeEnableStatusInfo() {
			const currentShowStatusInfo = this.getFromLocalStorage( 'mmv-showStatusInfo' );
			if ( currentShowStatusInfo === null ) {
				this.setInLocalStorage( 'mmv-showStatusInfo', '1' );
			}
		}

		/**
		 * Called when status info is displayed. Future shouldShowStatusInfo() calls will return false.
		 */
		disableStatusInfo() {
			this.setInLocalStorage( 'mmv-showStatusInfo', '0' );
		}

		/**
		 * Returns file extensions handled by Media Viewer.
		 *
		 * The object's keys are the file extensions.
		 * The object's values are either 'default' when Media Viewer handles that file extension
		 * directly or the name of a ResourceLoader module to load when such a file is opened.
		 *
		 * @return {Object}
		 */
		extensions() {
			return this.viewerConfig.extensions;
		}

		/**
		 * Returns UI language
		 *
		 * @return {string} Language code
		 */
		language() {
			return this.mwConfig.get( 'wgUserLanguage', false ) || this.mwConfig.get( 'wgContentLanguage', 'en' );
		}

		/**
		 * Returns URI of virtual view beacon or false if not set
		 *
		 * @return {string|boolean} URI
		 */
		recordVirtualViewBeaconURI() {
			return this.viewerConfig.recordVirtualViewBeaconURI;
		}

		/**
		 * Returns useThumbnailGuessing flag
		 *
		 * @return {boolean}
		 */
		useThumbnailGuessing() {
			return this.viewerConfig.useThumbnailGuessing;
		}

		/**
		 * Returns imageQueryParameter, if set
		 *
		 * @return {string|boolean}
		 */
		imageQueryParameter() {
			return this.viewerConfig.imageQueryParameter;
		}
	}

	module.exports = Config;
}() );
