/*
 * This file is part of the MediaWiki extension MultimediaViewer.
 *
 * MultimediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MultimediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MultimediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

// Included on every page which has images so keep it lightweight.
( function () {
	module.exports = {
		/**
		 * The media route prefix
		 *
		 * @member mw.mmv
		 */
		ROUTE: 'media',
		/**
		 * RegExp representing the media route
		 *
		 * @member mw.mmv
		 */
		ROUTE_REGEXP: /^\/media\/(.+)$/,
		/**
		 * @property {RegExp}
		 * Regular expression representing the legacy media route
		 * @member mw.mmv
		 */
		LEGACY_ROUTE_REGEXP: /^mediaviewer\/(.+)$/,

		/**
		 * Returns true if MediaViewer should handle thumbnail clicks.
		 *
		 * @param {Map} mwConfig
		 * @param {Object} mwUser
		 * @param {mw.SafeStorage} mwStorage
		 * @return {boolean}
		 */
		isMediaViewerEnabledOnClick( mwConfig = mw.config, mwUser = mw.user, mwStorage = mw.storage ) {
			return mwConfig.get( 'wgMediaViewer' ) && // global opt-out switch, can be set in user JS
				mwConfig.get( 'wgMediaViewerOnClick' ) && // thumbnail opt-out, can be set in preferences
				( mwUser.isNamed() || !mwStorage.get( 'wgMediaViewerOnClick' ) || mwStorage.get( 'wgMediaViewerOnClick' ) === '1' ); // thumbnail opt-out for anons
		},

		/**
		 * Returns the location hash (route string) for the given file title.
		 *
		 * @param {string} imageFileTitle the file title
		 * @return {string} the location hash
		 * @member mw.mmv
		 */
		getMediaHash: ( imageFileTitle ) => `#/media/${ encodeURI( imageFileTitle ) }`
	};
}() );
