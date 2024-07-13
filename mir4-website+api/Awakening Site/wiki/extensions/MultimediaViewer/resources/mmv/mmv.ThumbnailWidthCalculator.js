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

const ThumbnailWidth = require( './model/mmv.model.ThumbnailWidth.js' );

( function () {

	/**
	 * A helper class for bucketing image sizes.
	 * Bucketing helps to avoid cache fragmentation and thus speed up image loading:
	 * instead of generating potentially hundreds of different thumbnail sizes, we restrict
	 * ourselves to a short list of acceptable thumbnail widths, and only ever load thumbnails
	 * of that size. Final size adjustment is done in a thumbnail.
	 *
	 * See also the [Standardized thumbnail sizes RFC][1].
	 *
	 * [1]: https://www.mediawiki.org/wiki/Talk:Requests_for_comment/Standardized_thumbnails_sizes
	 */
	class ThumbnailWidthCalculator {
		/**
		 * @param {Object} [options]
		 * @param {number[]} [options.widthBuckets] see {@link ThumbnailWidthCalculator#widthBuckets}
		 * @param {number} [options.devicePixelRatio] see {@link ThumbnailWidthCalculator#devicePixelRatio};
		 *     will be autodetected if omitted
		 */
		constructor( options ) {
			options = Object.assign( {
				// default image widths
				widthBuckets: [
					320,
					800,
					1024,
					1280,
					1920,
					2560,
					2880
				],

				// screen pixel per CSS pixel
				devicePixelRatio: window.devicePixelRatio || 1
			}, options );

			if ( !options.widthBuckets.length ) {
				throw new Error( 'No buckets!' );
			}

			/**
			 * List of thumbnail width bucket sizes, in pixels.
			 *
			 * @property {number[]}
			 */
			this.widthBuckets = options.widthBuckets;
			this.widthBuckets.sort( ( a, b ) => a - b );

			/**
			 * Screen pixel count per CSS pixel.
			 *
			 * @property {number}
			 */
			this.devicePixelRatio = options.devicePixelRatio;
		}

		/**
		 * Finds the smallest bucket which is large enough to hold the target size
		 * (i. e. the smallest bucket whose size is equal to or greater than the target).
		 * If none of the buckets are large enough, returns the largest bucket.
		 *
		 * @param {number} target
		 * @return {number}
		 */
		findNextBucket( target ) {
			let bucket;
			const buckets = this.widthBuckets;

			for ( let i = 0; i < buckets.length; i++ ) {
				bucket = buckets[ i ];

				if ( bucket >= target ) {
					return bucket;
				}
			}

			// If we failed to find a high enough size...good luck
			return bucket;
		}

		/**
		 * Finds the largest width for an image so that it will still fit into a given bounding box,
		 * based on the size of a sample (some smaller version of the same image, like the thumbnail
		 * shown in the article) which is used to calculate the ratio.
		 *
		 * This is for internal use, you should probably use calculateWidths() instead.
		 *
		 * @protected
		 * @param {number} boundingWidth width of the bounding box
		 * @param {number} boundingHeight height of the bounding box
		 * @param {number} sampleWidth width of the sample image
		 * @param {number} sampleHeight height of the sample image
		 * @return {number} the largest width so that the scaled version of the sample image fits
		 *     into the bounding box (either horizontal or vertical edges touch on both sides).
		 */
		calculateFittingWidth( boundingWidth, boundingHeight, sampleWidth, sampleHeight ) {
			if ( ( boundingWidth / boundingHeight ) > ( sampleWidth / sampleHeight ) ) {
				// we are limited by height; we need to calculate the max width that fits
				return Math.round( ( sampleWidth / sampleHeight ) * boundingHeight );
			} else {
				// simple case, ratio tells us we're limited by width
				return boundingWidth;
			}
		}

		/**
		 * Finds the largest width for an image so that it will still fit into a given bounding box,
		 * based on the size of a sample (some smaller version of the same image, like the thumbnail
		 * shown in the article) which is used to calculate the ratio.
		 *
		 * Returns two values, a CSS width which is the size in pixels that should be used so the image
		 * fits exactly into the bounding box, and a real width which should be the size of the
		 * downloaded image in pixels. The two will be different for two reasons:
		 * - Images are bucketed for more efficient caching, so the real width will always be one of
		 *     the numbers in this.widthBuckets. The resulting thumbnail will be slightly larger than
		 *     the bounding box so that it takes roughly the same amount of bandwidth and
		 *     looks decent when resized by the browser.
		 * - For devices with high pixel density (multiple actual pixels per CSS pixel) we want to use
		 *     a larger image so that there will be roughly one image pixel per physical display pixel.
		 *
		 * @param {number} boundingWidth width of the bounding box, in CSS pixels
		 * @param {number} boundingHeight height of the bounding box, in CSS pixels
		 * @param {number} sampleWidth width of the sample image (in whatever - only used for aspect ratio)
		 * @param {number} sampleHeight height of the sample image (in whatever - only used for aspect ratio)
		 * @return {ThumbnailWidth}
		 */
		calculateWidths( boundingWidth, boundingHeight, sampleWidth, sampleHeight ) {
			const ratio = sampleHeight / sampleWidth;

			const cssWidth = this.calculateFittingWidth( boundingWidth, boundingHeight, sampleWidth, sampleHeight );
			const cssHeight = Math.max( 1, Math.round( cssWidth * ratio ) );

			const screenPixelWidth = Math.max( 1, cssWidth * this.devicePixelRatio );

			const bucketedWidth = this.findNextBucket( screenPixelWidth );

			return new ThumbnailWidth( cssWidth, cssHeight, screenPixelWidth, bucketedWidth );
		}
	}

	module.exports = ThumbnailWidthCalculator;
}() );
