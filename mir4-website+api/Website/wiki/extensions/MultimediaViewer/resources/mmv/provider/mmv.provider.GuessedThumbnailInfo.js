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

const Thumbnail = require( '../model/mmv.model.Thumbnail.js' );

( function () {
	/**
	 * This provider is similar to ThumbnailInfo, but instead of making an API call
	 * to get the thumbnail URL, it tries to guess it. There are two failure modes:
	 * - known failure: in the given situation it does not seem possible or safe to guess the URL.
	 *   It is up to the caller to obtain it by falling back to the normal provider.
	 * - unexpected failure: we guess an URL but it does not work. The current implementation is
	 *   conservative so at least on WMF wikis this probably won't happen, but should be reckoned
	 *   with anyway. On other wikis (especially ones which do not generate thumbnails on demand
	 *   via the 404 handler) this could be more frequent. Again, it is the caller's responsibility
	 *   to handle this by detecting image loading errors and falling back to the normal provider.
	 */
	class GuessedThumbnailInfo {

		/**
		 * File extensions which are vector types (as opposed to bitmap).
		 * Thumbnails of vector types can be larger than the original file.
		 *
		 * @return {Object.<string, number>}
		 */
		get vectorExtensions() {
			return {
				svg: 1
			};
		}

		/**
		 * File extensions which can be displayed in the browser.
		 * Other file types need to be thumbnailed even if the size of the original file would be right.
		 *
		 * @return {Object.<string, number>}
		 */
		get displayableExtensions() {
			return {
				png: 1,
				jpg: 1,
				jpeg: 1,
				gif: 1
			};
		}

		/**
		 * Try to guess the thumbnailinfo for a thumbnail without doing an API request.
		 * An existing thumbnail URL is required.
		 *
		 * There is no guarantee this function will be successful - in some cases, it is impossible
		 * to guess how the URL would look. If that's the case, the promise just rejects.
		 *
		 * @param {mw.Title} file
		 * @param {string} sampleUrl a thumbnail URL for the same file (but with different size).
		 * @param {number} width thumbnail width in pixels
		 * @param {number} originalWidth width of original image in pixels
		 * @param {number} originalHeight height of original image in pixels
		 * @return {jQuery.Promise.<Thumbnail>}
		 */
		get( file, sampleUrl, width, originalWidth, originalHeight ) {
			const url = this.getUrl( file, sampleUrl, width, originalWidth );
			if ( url ) {
				return $.Deferred().resolve( new Thumbnail(
					url,
					this.guessWidth( file, width, originalWidth ),
					this.guessHeight( file, width, originalWidth, originalHeight )
				) );
			} else {
				return $.Deferred().reject( 'Could not guess thumbnail URL' );
			}
		}

		/**
		 * Try to guess the URL of a thumbnail without doing an API request.
		 * See #get().
		 *
		 * @param {mw.Title} file
		 * @param {string} sampleUrl a thumbnail URL for the same file (but with different size)
		 * @param {number} width thumbnail width in pixels
		 * @param {number} originalWidth width of original image in pixels
		 * @return {string|undefined} a thumbnail URL or nothing
		 */
		getUrl( file, sampleUrl, width, originalWidth ) {
			const needsFullSize = this.needsOriginal( file, width, originalWidth );
			const sampleIsFullSize = this.isFullSizeUrl( sampleUrl, file );

			if ( sampleIsFullSize && needsFullSize ) {
				// sample thumbnail uses full size, and we need full size as well - the sample URL
				// happens to be just the right one for us
				return sampleUrl;
			} else if ( !sampleIsFullSize && !needsFullSize ) {
				// need to convert a scaled thumbnail URL to another scaled thumbnail URL
				return this.replaceSize( file, sampleUrl, width );
			} else if ( !sampleIsFullSize && needsFullSize ) {
				if ( this.canBeDisplayedInBrowser( file ) ) {
					// the size requested is larger than the original - we need to return an URL
					// to the original file instead
					return this.guessFullUrl( file, sampleUrl );
				} else {
					// the size requested is larger than the original, but this file type cannot
					// be displayed by all browsers, so needs to be thumbnailed anyway,
					// but the thumbnail still cannot be larger than the original file
					return this.replaceSize( file, sampleUrl, originalWidth );
				}
			} else { // sampleIsFullSize && !needsOriginal
				return this.guessThumbUrl( file, sampleUrl, width );
			}
		}

		/**
		 * True if the original image needs to be used as a thumbnail.
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @param {number} width thumbnail width in pixels
		 * @param {number} originalWidth width of original image in pixels
		 * @return {boolean}
		 */
		needsOriginal( file, width, originalWidth ) {
			return width >= originalWidth && !this.canHaveLargerThumbnailThanOriginal( file );
		}

		/**
		 * Checks if a given thumbnail URL is full-size (the original image) or scaled
		 *
		 * @protected
		 * @param {string} url a thumbnail URL
		 * @param {mw.Title} file
		 * @return {boolean}
		 */
		isFullSizeUrl( url, file ) {
			return !this.obscureFilename( url, file ).match( '/thumb/' );
		}

		/**
		 * Removes the filename in a reversible way. This is useful because the filename can be nearly
		 * anything and could cause false positives when looking for patterns.
		 *
		 * @protected
		 * @param {string} url a thumbnail URL
		 * @param {mw.Title} file
		 * @return {string} thumbnail URL with occurrences of the filename replaced by `<filename>`
		 */
		obscureFilename( url, file ) {
			// corresponds to File::getUrlRel() which uses rawurlencode()
			const filenameInUrl = mw.util.rawurlencode( file.getMain() );

			// In the URL to the original file the filename occurs once. In a thumbnail URL it usually
			// occurs twice, but can occur once if it is too short. We replace twice, can't hurt.
			return url.replace( filenameInUrl, '<filename>' ).replace( filenameInUrl, '<filename>' );
		}

		/**
		 * Undoes #obscureFilename().
		 *
		 * @protected
		 * @param {string} url a thumbnail URL (with obscured filename)
		 * @param {mw.Title} file
		 * @return {string} original thumbnail URL
		 */
		restoreFilename( url, file ) {
			// corresponds to File::getUrlRel() which uses rawurlencode()
			const filenameInUrl = mw.util.rawurlencode( file.getMain() );

			// <> cannot be used in titles, so this is safe
			return url.replace( '<filename>', filenameInUrl ).replace( '<filename>', filenameInUrl );
		}

		/**
		 * True if the file is of a type for which the thumbnail can be scaled beyond the original size.
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @return {boolean}
		 */
		canHaveLargerThumbnailThanOriginal( file ) {
			return ( file.getExtension().toLowerCase() in this.vectorExtensions );
		}

		/**
		 * True if the file type can be displayed in most browsers, false if it needs thumbnailing
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @return {boolean}
		 */
		canBeDisplayedInBrowser( file ) {
			return ( file.getExtension().toLowerCase() in this.displayableExtensions );
		}

		/**
		 * Guess what will be the width of the thumbnail. (Thumbnails for most file formats cannot be
		 * larger than the original file so this might be smaller than the requested width.)
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @param {number} width thumbnail width in pixels
		 * @param {number} originalWidth width of original image in pixels
		 * @return {number} guessed width
		 */
		guessWidth( file, width, originalWidth ) {
			if ( width >= originalWidth && !this.canHaveLargerThumbnailThanOriginal( file ) ) {
				return originalWidth;
			} else {
				return width;
			}
		}

		/**
		 * Guess what will be the height of the thumbnail, given its width.
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @param {number} width thumbnail width in pixels
		 * @param {number} originalWidth width of original image in pixels
		 * @param {number} originalHeight height of original image in pixels
		 * @return {number} guessed height
		 */
		guessHeight( file, width, originalWidth, originalHeight ) {
			if ( width >= originalWidth && !this.canHaveLargerThumbnailThanOriginal( file ) ) {
				return originalHeight;
			} else {
				// might be off 1px due to rounding (we don't know what exact scaling method the
				// backend uses) but that should not cause any issues
				return Math.round( width * ( originalHeight / originalWidth ) );
			}
		}

		/**
		 * Given a thumbnail URL with a wrong size, returns one with the right size.
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @param {string} sampleUrl a thumbnail URL for the same file (but with different size)
		 * @param {number} width thumbnail width in pixels
		 * @return {string|undefined} thumbnail URL with the correct size
		 */
		replaceSize( file, sampleUrl, width ) {
			let url = this.obscureFilename( sampleUrl, file );
			const sizeRegexp = /\b\d{1,5}px\b/;

			// this should never happen, but let's play it safe - returning the sample URL and believing
			// it is the resized one would be bad. Returning a wrong filename is not catastrophic
			// as long as we return a non-working wrong filename, which would not be the case here.
			if ( !url.match( sizeRegexp ) ) {
				return undefined;
			}

			// we are assuming here that the other thumbnail parameters do not look like a size
			url = url.replace( sizeRegexp, `${ width }px` );

			return this.restoreFilename( url, file );
		}

		/**
		 * Try to guess the original URL to the file, from a thumb URL.
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @param {string} thumbnailUrl
		 * @return {string} URL of the original file
		 */
		guessFullUrl( file, thumbnailUrl ) {
			let url = this.obscureFilename( thumbnailUrl, file );

			if ( url === thumbnailUrl ) {
				// Did not find the filename, maybe due to URL encoding issues. Bail out.
				return undefined;
			}

			// this depends on some config settings, but will work with default or WMF settings.
			url = url.replace( /<filename>.*/, '<filename>' );
			url = url.replace( '/thumb', '' );

			return this.restoreFilename( url, file );
		}

		/**
		 * Hardest version: try to guess thumbnail URL from original
		 *
		 * @protected
		 * @param {mw.Title} file
		 * @param {string} originalUrl URL for the original file
		 * @param {number} width thumbnail width in pixels
		 * @return {string|undefined} thumbnail URL
		 */
		guessThumbUrl() {
			// Not implemented. This can be very complicated (the thumbnail might have other
			// parameters than the size, which are impossible to guess, might be converted to some
			// other format, might have a special shortened format depending on the length of the
			// filename) and it is unlikely to be useful - it would be only called when we need
			// a thumbnail that is smaller than the sample (the thumbnail which is already on the page).
			return undefined;
		}
	}

	module.exports = GuessedThumbnailInfo;
}() );
