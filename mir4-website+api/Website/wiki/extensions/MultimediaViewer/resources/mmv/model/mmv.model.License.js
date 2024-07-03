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

const { HtmlUtils } = require( 'mmv.bootstrap' );

( function () {

	/**
	 * Class for storing license information about an image. For available fields, see
	 * TemplateParser::$licenseFieldClasses in the CommonsMetadata extension.
	 */
	class License {
		/**
		 * @param {string} shortName see {@link #shortName}
		 * @param {string} [internalName] see {@link #internalName}
		 * @param {string} [longName] see {@link #longName}
		 * @param {string} [deedUrl] see {@link #deedUrl}
		 * @param {boolean} [attributionRequired] see {@link #attributionRequired}
		 * @param {boolean} [nonFree] see {@link #nonFree}
		 */
		constructor(
			shortName,
			internalName,
			longName,
			deedUrl,
			attributionRequired,
			nonFree
		) {
			if ( !shortName ) {
				throw new Error( 'License: shortName is required' );
			}

			/** @property {string} shortName short (abbreviated) name of the license (e.g. CC-BY-SA-3.0) */
			this.shortName = shortName;

			/** @property {string} internalName internal name of the license, used for localization (e.g. cc-by-sa ) */
			this.internalName = internalName;

			/** @property {string} longName full name of the license (e.g. Creative Commons etc. etc.) */
			this.longName = longName;

			/** @property {string} deedUrl URL to the description of the license (e.g. the CC deed) */
			this.deedUrl = deedUrl;

			/** @property {boolean} attributionRequired does the author need to be attributed on reuse? */
			this.attributionRequired = attributionRequired;

			/** @property {boolean} nonFree is this a non-free license? */
			this.nonFree = nonFree;

			/** @property {HtmlUtils} htmlUtils - */
			this.htmlUtils = new HtmlUtils();
		}

		/**
		 * Check whether this is a Creative Commons license.
		 *
		 * @return {boolean}
		 */
		isCc() {
			return this.internalName ? this.internalName.slice( 0, 2 ) === 'cc' : false;
		}

		/**
		 * Check whether this is a public domain "license".
		 *
		 * @return {boolean}
		 */
		isPd() {
			return this.internalName === 'pd';
		}

		/**
		 * Check whether this is a free license.
		 *
		 * @return {boolean}
		 */
		isFree() {
			// licenses with missing nonfree information are assumed free
			return !this.nonFree;
		}

		/**
		 * Check whether reusers need to attribute the author
		 *
		 * @return {boolean}
		 */
		needsAttribution() {
			// to be on the safe side, if the attribution required flag is not set, it is assumed to be true
			return !this.isPd() && this.attributionRequired !== false;
		}

		/**
		 * Returns the short name of the license:
		 * - if we have interface messages for this license (basically just CC and PD), use those
		 * - otherwise use the short name from the license template (might or might not be translated
		 *   still, depending on how the template is set up)
		 *
		 * @return {string}
		 * FIXME a model should not depend on an i18n class. We should probably use view models.
		 */
		getShortName() {
			const message = `multimediaviewer-license-${ this.internalName || '' }`;
			if ( mw.messages.exists( message ) ) {
				// The following messages are used here:
				// * multimediaviewer-license-cc-by-1.0
				// * multimediaviewer-license-cc-sa-1.0
				// * multimediaviewer-license-cc-by-sa-1.0
				// * multimediaviewer-license-cc-by-2.0
				// * multimediaviewer-license-cc-by-sa-2.0
				// * multimediaviewer-license-cc-by-2.1
				// * multimediaviewer-license-cc-by-sa-2.1
				// * multimediaviewer-license-cc-by-2.5
				// * multimediaviewer-license-cc-by-sa-2.5
				// * multimediaviewer-license-cc-by-3.0
				// * multimediaviewer-license-cc-by-sa-3.0
				// * multimediaviewer-license-cc-by-4.0
				// * multimediaviewer-license-cc-by-sa-4.0
				// * multimediaviewer-license-cc-pd
				// * multimediaviewer-license-cc-zero
				// * multimediaviewer-license-pd
				// * multimediaviewer-license-default
				return mw.message( message ).text();
			} else {
				return this.shortName;
			}
		}

		/**
		 * Returns a short HTML representation of the license.
		 *
		 * @return {string}
		 */
		getShortLink() {
			const shortName = this.getShortName();

			if ( this.deedUrl ) {
				return this.htmlUtils.makeLinkText( shortName, {
					href: this.deedUrl,
					title: this.longName || shortName
				} );
			} else {
				return shortName;
			}
		}
	}

	module.exports = License;
}() );
