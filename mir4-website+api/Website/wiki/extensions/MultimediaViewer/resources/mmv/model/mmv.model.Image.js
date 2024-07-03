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

const License = require( './mmv.model.License.js' );

( function () {

	/**
	 * Represents information about a single image
	 */
	class ImageModel {
		/**
		 * @param {mw.Title} title
		 * @param {string} name Image name (e.g. title of the artwork) or human-readable file if there is no better title
		 * @param {number} size Filesize in bytes of the original image
		 * @param {number} width Width of the original image
		 * @param {number} height Height of the original image
		 * @param {string} mimeType
		 * @param {string} url URL to the image itself (original version)
		 * @param {string} descriptionUrl URL to the image description page
		 * @param {string} descriptionShortUrl A short URL to the description page for the image, using curid=...
		 * @param {string} pageID pageId of the description page for the image
		 * @param {string} repo The repository this image belongs to
		 * @param {string} uploadDateTime The time and date the last upload occurred
		 * @param {string} anonymizedUploadDateTime Anonymized and EL-friendly version of uploadDateTime
		 * @param {string} creationDateTime The time and date the original upload occurred
		 * @param {string} description
		 * @param {string} source
		 * @param {string} author
		 * @param {number} authorCount
		 * @param {License} license
		 * @param {string} permission
		 * @param {string} attribution Custom attribution string that replaces credit line when set
		 * @param {string} deletionReason
		 * @param {number} latitude
		 * @param {number} longitude
		 * @param {string[]} restrictions
		 */
		constructor(
			title,
			name,
			size,
			width,
			height,
			mimeType,
			url,
			descriptionUrl,
			descriptionShortUrl,
			pageID,
			repo,
			uploadDateTime,
			anonymizedUploadDateTime,
			creationDateTime,
			description,
			source,
			author,
			authorCount,
			license,
			permission,
			attribution,
			deletionReason,
			latitude,
			longitude,
			restrictions
		) {
			/** @property {mw.Title} title The title of the image file */
			this.title = title;

			/** @property {string} name Image name (e.g. title of the artwork) or human-readable file if there is no better title */
			this.name = name;

			/** @property {number} size The filesize, in bytes, of the original image */
			this.size = size;

			/** @property {number} width The width, in pixels, of the original image */
			this.width = width;

			/** @property {number} height The height, in pixels, of the original image */
			this.height = height;

			/** @property {string} mimeType The MIME type of the original image */
			this.mimeType = mimeType;

			/** @property {string} url The URL to the original image */
			this.url = url;

			/** @property {string} descriptionUrl The URL to the description page for the image */
			this.descriptionUrl = descriptionUrl;

			/** @property {string} descriptionShortUrl A short URL to the description page for the image, using curid=... */
			this.descriptionShortUrl = descriptionShortUrl;

			/** @property {number} pageId of the description page for the image */
			this.pageID = pageID;

			/** @property {string} repo The name of the repository where this image is stored */
			this.repo = repo;

			/** @property {string} uploadDateTime The date and time of the last upload */
			this.uploadDateTime = uploadDateTime;

			/** @property {string} anonymizedUploadDateTime The anonymized date and time of the last upload */
			this.anonymizedUploadDateTime = anonymizedUploadDateTime;

			/** @property {string} creationDateTime The date and time that the image was created */
			this.creationDateTime = creationDateTime;

			/** @property {string} description The description from the file page - unsafe HTML sometimes goes here */
			this.description = description;

			/** @property {string} source The source for the image (could be an organization, e.g.) - unsafe HTML sometimes goes here */
			this.source = source;

			/** @property {string} author The author of the image - unsafe HTML sometimes goes here */
			this.author = author;

			/**
			 * @property {number} authorCount The number of different authors of the image. This is guessed by the
			 *   number of templates with author fields, so might be less than the number of actual authors.
			 */
			this.authorCount = authorCount;

			/** @property {License} license The license under which the image is distributed */
			this.license = license;

			/** @property {string} additional license conditions by the author (note that this is usually a big ugly HTML blob) */
			this.permission = permission;

			/** @property {string} attribution custom attribution string set by uploader that replaces credit line */
			this.attribution = attribution;

			/** @property {string|null} reason for pending deletion, null if image is not about to be deleted */
			this.deletionReason = deletionReason;

			/** @property {number} latitude The latitude of the place where the image was created */
			this.latitude = latitude;

			/** @property {number} longitude The longitude of the place where the image was created */
			this.longitude = longitude;

			/** @property {string[]} restrictions Any re-use restrictions for the image, eg trademarked */
			this.restrictions = restrictions;

			/**
			 * @property {Object} thumbUrls
			 * An object indexed by image widths
			 * with URLs to appropriately sized thumbnails
			 */
			this.thumbUrls = {};
		}

		/**
		 * Constructs a new Image object out of an object containing
		 *
		 * imageinfo data from an API response.
		 *
		 * @static
		 * @param {mw.Title} title
		 * @param {Object} imageInfo
		 * @return {Image}
		 */
		static newFromImageInfo( title, imageInfo ) {
			let name;
			let uploadDateTime;
			let anonymizedUploadDateTime;
			let creationDateTime;
			let description;
			let source;
			let author;
			let authorCount;
			let license;
			let permission;
			let attribution;
			let deletionReason;
			let latitude;
			let longitude;
			let restrictions;
			const innerInfo = imageInfo.imageinfo[ 0 ];
			const extmeta = innerInfo.extmetadata;

			if ( extmeta ) {
				creationDateTime = this.parseExtmeta( extmeta.DateTimeOriginal, 'datetime' );
				uploadDateTime = this.parseExtmeta( extmeta.DateTime, 'datetime' );

				// Convert to "timestamp" format commonly used in EventLogging
				anonymizedUploadDateTime = uploadDateTime.replace( /[^\d]/g, '' );

				// Anonymise the timestamp to avoid making the file identifiable
				// We only need to know the day
				anonymizedUploadDateTime = `${ anonymizedUploadDateTime.slice( 0, anonymizedUploadDateTime.length - 6 ) }000000`;

				name = this.parseExtmeta( extmeta.ObjectName, 'plaintext' );

				description = this.parseExtmeta( extmeta.ImageDescription, 'string' );
				source = this.parseExtmeta( extmeta.Credit, 'string' );
				author = this.parseExtmeta( extmeta.Artist, 'string' );
				authorCount = this.parseExtmeta( extmeta.AuthorCount, 'integer' );

				license = this.newLicenseFromImageInfo( extmeta );
				permission = this.parseExtmeta( extmeta.Permission, 'string' );
				attribution = this.parseExtmeta( extmeta.Attribution, 'string' );
				deletionReason = this.parseExtmeta( extmeta.DeletionReason, 'string' );
				restrictions = this.parseExtmeta( extmeta.Restrictions, 'list' );

				latitude = this.parseExtmeta( extmeta.GPSLatitude, 'float' );
				longitude = this.parseExtmeta( extmeta.GPSLongitude, 'float' );
			}

			if ( !name ) {
				name = title.getNameText();
			}

			const imageData = new ImageModel(
				title,
				name,
				innerInfo.size,
				innerInfo.width,
				innerInfo.height,
				innerInfo.mime,
				innerInfo.url,
				innerInfo.descriptionurl,
				innerInfo.descriptionshorturl,
				imageInfo.pageid,
				imageInfo.imagerepository,
				uploadDateTime,
				anonymizedUploadDateTime,
				creationDateTime,
				description,
				source,
				author,
				authorCount,
				license,
				permission,
				attribution,
				deletionReason,
				latitude,
				longitude,
				restrictions
			);

			if ( innerInfo.thumburl ) {
				imageData.addThumbUrl(
					innerInfo.thumbwidth,
					innerInfo.thumburl
				);
			}

			return imageData;
		}

		/**
		 * Constructs a new License object out of an object containing
		 * imageinfo data from an API response.
		 *
		 * @static
		 * @param {Object} extmeta the extmeta array of the imageinfo data
		 * @return {License|undefined}
		 */
		static newLicenseFromImageInfo( extmeta ) {
			let license;

			if ( extmeta.LicenseShortName ) {
				license = new License(
					this.parseExtmeta( extmeta.LicenseShortName, 'string' ),
					this.parseExtmeta( extmeta.License, 'string' ),
					this.parseExtmeta( extmeta.UsageTerms, 'string' ),
					this.parseExtmeta( extmeta.LicenseUrl, 'string' ),
					this.parseExtmeta( extmeta.AttributionRequired, 'boolean' ),
					this.parseExtmeta( extmeta.NonFree, 'boolean' )
				);
			}

			return license;
		}

		/**
		 * Reads and parses a value from the imageinfo API extmetadata field.
		 *
		 * @param {Array} data
		 * @param {string} type one of 'plaintext', 'string', 'float', 'boolean', 'list', 'datetime'
		 * @return {string|number|boolean|Array} value or undefined if it is missing
		 */
		static parseExtmeta( data, type ) {
			let value = data && data.value;
			if ( value === null || value === undefined ) {
				return undefined;
			} else if ( type === 'plaintext' ) {
				return value.toString().replace( /<.*?>/g, '' );
			} else if ( type === 'string' ) {
				return value.toString();
			} else if ( type === 'integer' ) {
				return parseInt( value, 10 );
			} else if ( type === 'float' ) {
				return parseFloat( value );
			} else if ( type === 'boolean' ) {
				value = value.toString().toLowerCase().replace( /^\s+|\s+$/g, '' );
				if ( value in { 1: null, yes: null, true: null } ) {
					return true;
				} else if ( value in { 0: null, no: null, false: null } ) {
					return false;
				} else {
					return undefined;
				}
			} else if ( type === 'datetime' ) {
				value = value.toString();
				// https://datatracker.ietf.org/doc/html/rfc3339
				// adapted from https://stackoverflow.com/questions/3143070/regex-to-match-an-iso-8601-datetime-string
				const rfc3339 = /\d{4}-[01]\d-[0-3]\d(T[0-2]\d:[0-5]\d:[0-5]\d(\.\d+)?Z?)?/;
				const match = value.match( rfc3339 );
				if ( !match ) {
					return value.replace( /<.*?>/g, '' );
				}
				value = match[ 0 ];
				if ( value.match( /^\d{4}-00-00/ ) ) {
					// assume yyyy
					return value.slice( 0, 4 );
				}
				return value;
			} else if ( type === 'list' ) {
				return value === '' ? [] : value.split( '|' );
			} else {
				throw new Error( 'Image.parseExtmeta: unknown type' );
			}
		}

		/**
		 * Add a thumb URL
		 *
		 * @param {number} width
		 * @param {string} url
		 */
		addThumbUrl( width, url ) {
			this.thumbUrls[ width ] = url;
		}

		/**
		 * Get a thumb URL if we have it.
		 *
		 * @param {number} width
		 * @return {string|undefined}
		 */
		getThumbUrl( width ) {
			return this.thumbUrls[ width ];
		}

		/**
		 * Check whether the image has geolocation data.
		 *
		 * @return {boolean}
		 */
		hasCoords() {
			return this.latitude !== undefined && this.latitude !== null &&
				this.longitude !== undefined && this.longitude !== null;
		}
	}

	module.exports = ImageModel;
}() );
