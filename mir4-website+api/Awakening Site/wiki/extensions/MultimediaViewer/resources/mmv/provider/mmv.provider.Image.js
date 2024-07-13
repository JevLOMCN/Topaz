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

( function () {

	/**
	 * Loads an image.
	 */
	class ImageProvider {
		/**
		 * @param {string} imageQueryParameter When defined, is a query parameter to add to every image request
		 */
		constructor( imageQueryParameter ) {
			this.imageQueryParameter = imageQueryParameter;

			/**
			 * AJAX call cache.
			 *
			 * @property {Object.<string, jQuery.Promise>} cache
			 * @protected
			 */
			this.cache = {};
		}

		/**
		 * Loads an image and returns it. When the browser supports it, the image is loaded as an AJAX
		 * request.
		 *
		 * @param {string} url
		 * @return {jQuery.Promise.<HTMLImageElement>} A promise which resolves to the image object.
		 *  When loaded via AJAX, it has progress events, which return an array with the content loaded
		 *  so far and with the progress as a floating-point number between 0 and 100.
		 */
		get( url ) {
			const cacheKey = url;
			const extraParam = {};

			if ( this.imageQueryParameter ) {
				try {
					const uri = new mw.Uri( url );
					extraParam[ this.imageQueryParameter ] = null;
					url = uri.extend( extraParam ).toString();
				} catch ( error ) {
					return $.Deferred().reject( error.message );
				}
			}

			if ( !this.cache[ cacheKey ] ) {
				this.cache[ cacheKey ] = this.rawGet( url, this.imagePreloadingSupported() );
				this.cache[ cacheKey ].fail( ( error ) => {
					mw.log( `${ this.constructor.name } provider failed to load: `, error );
				} );
			}

			return this.cache[ cacheKey ];
		}

		/**
		 * Internal version of get(): no caching, no performance metrics.
		 *
		 * @param {string} url
		 * @param {boolean} [cors] if true, use CORS for preloading
		 * @return {jQuery.Promise.<HTMLImageElement>} a promise which resolves to the image object
		 */
		rawGet( url, cors ) {
			const img = new window.Image();
			const deferred = $.Deferred();

			// This attribute is necessary in Firefox, which needs it for the image request after
			// the XHR to hit the cache by being a proper CORS request.
			if ( cors ) {
				img.crossOrigin = 'anonymous';
			}

			img.onload = () => deferred.resolve( img );
			img.onerror = () => deferred.reject( `could not load image from ${ url }` );

			img.src = url;

			return deferred;
		}

		/**
		 * Checks whether the current browser supports AJAX preloading of images.
		 * This means that:
		 * - the browser supports CORS requests (large wiki farms usually host images on a
		 *   separate domain) and
		 * - either AJAX and normal image loading uses the same cache (when an image is used by a CORS
		 *   request, and then normally by setting img.src, it is only loaded once)
		 * - or (as is the case with Firefox) they are cached separately, but that can be changed by
		 *   setting the crossOrigin attribute
		 *
		 * @return {boolean}
		 */
		imagePreloadingSupported() {
			// This checks if the browser supports CORS requests in XHRs
			return window.XMLHttpRequest !== undefined && 'withCredentials' in new XMLHttpRequest();
		}
	}

	module.exports = ImageProvider;
}() );
