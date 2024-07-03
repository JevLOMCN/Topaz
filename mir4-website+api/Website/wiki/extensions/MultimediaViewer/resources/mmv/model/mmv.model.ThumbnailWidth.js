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
	 * Represents image width information.
	 *
	 * To utilize caching as much as possible, we use images which are displayed at a slightly
	 * different size than their screen size. The ThumbnailWidth model stores the various types of
	 * sizes and helps avoiding accidental incompatible assignments. (Think of it as a slightly
	 * overcomplicated Hungarian notation)
	 */
	class ThumbnailWidth {
		/**
		 * @param {number} cssWidth width in CSS pixels
		 * @param {number} cssHeight height in CSS pixels
		 * @param {number} screen width in screen pixels
		 * @param {number} real width in real pixels
		 */
		constructor( cssWidth, cssHeight, screen, real ) {
			if ( !cssWidth || !cssHeight || !screen || !real ) {
				throw new Error( 'All parameters are required and cannot be empty or zero' );
			}

			/**
			 * Width of the thumbnail on the screen, in CSS pixels. This is the number which can be plugged
			 * into UI code like $element.width(x).
			 *
			 * @property {number}
			 */
			this.cssWidth = cssWidth;

			/**
			 * Height of the thumbnail on the screen, in CSS pixels. This is the number which can be plugged
			 * into UI code like $element.height(x).
			 *
			 * @property {number}
			 */
			this.cssHeight = cssHeight;

			/**
			 * Width of the thumbnail on the screen, in device pixels. On most devices this is the same as
			 * the CSS width, but devices with high pixel density displays have multiple screen pixels
			 * in a CSS pixel.
			 *
			 * This value is mostly used internally; for most purposes you will need one of the others.
			 *
			 * @property {number}
			 */
			this.screen = screen;

			/**
			 * "Real" width of the thumbnail. This is the number you need to use in API requests when
			 * obtaining the thumbnail URL. This is usually larger than the screen width, since
			 * downscaling images via CSS looks OK but upscaling them looks ugly. However, for images
			 * where the full size itself is very small, this can be smaller than the screen width, since
			 * we cannot create a thumbnail which is larger than the original image. (In such cases the
			 * image is just positioned to the center of the intended area and the space around it is
			 * left empty.)
			 *
			 * @property {number}
			 */
			this.real = real;
		}
	}

	module.exports = ThumbnailWidth;

}() );
