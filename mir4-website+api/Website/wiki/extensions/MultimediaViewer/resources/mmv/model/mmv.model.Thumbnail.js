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
	 * Represents information about an image thumbnail
	 */
	class Thumbnail {
		/**
		 * @param {string} url URL to the thumbnail
		 * @param {number} width Width in pixels
		 * @param {number} height Height in pixels
		 */
		constructor( url, width, height ) {
			if ( !url || !width || !height ) {
				throw new Error( 'All parameters are required and cannot be empty or zero' );
			}

			/** @property {string} url The URL to the thumbnail */
			this.url = url;

			/** @property {number} width The width of the thumbnail in pixels */
			this.width = width;

			/** @property {number} height The height of the thumbnail in pixels */
			this.height = height;
		}
	}

	module.exports = Thumbnail;
}() );
