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
	 * Represents an image on the page.
	 */
	class LightboxImage {
		/**
		 * @param {string} fileLink Link to the file - generally a thumb URL
		 * @param {string} filePageLink Link to the File: page
		 * @param {mw.Title} fileTitle Represents the File: page
		 * @param {number} index Which number file this is
		 * @param {HTMLImageElement} thumb The thumbnail that represents this image on the page
		 * @param {string} [caption] The caption, if any.
		 * @param {string} [alt] The alt text of the image
		 */
		constructor( fileLink, filePageLink, fileTitle, index, thumb, caption, alt ) {
			/** @property {string} Link to the file - generally a thumb URL */
			this.src = fileLink;

			/** @property {string} filePageLink URL to the image's file page */
			this.filePageLink = filePageLink;

			/** @property {mw.Title} filePageTitle Title of the image's file page */
			this.filePageTitle = fileTitle;

			/** @property {number} index What number this image is in the array of indexed images */
			this.index = index;

			/** @property {HTMLImageElement} thumbnail The <img> element that holds the already-loaded thumbnail of the image */
			this.thumbnail = thumb;

			/** @property {string} caption The caption of the image, if any */
			this.caption = caption;

			/** @property {string} alt The alt text of the image */
			this.alt = alt;

			/** @property {number|undefined} originalWidth Width of the full-sized file (read from HTML data attribute, might be missing) */
			this.originalWidth = undefined;

			/** @property {number|undefined} originalHeight Height of the full-sized file (read from HTML data attribute, might be missing) */
			this.originalHeight = undefined;
		}
	}

	module.exports = LightboxImage;
}() );
