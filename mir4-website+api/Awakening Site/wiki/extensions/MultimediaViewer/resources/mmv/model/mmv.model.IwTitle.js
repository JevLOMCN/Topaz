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

( function () {

	/**
	 * IwTitle represents a title in a foreign wiki. The long-term goal is to have an interface
	 * largely compatible with mw.Title, but for now we only implement what we actually need.
	 */
	class IwTitle {
		/**
		 * @param {string} namespaceId namespace number
		 * @param {string} title full title, including namespace name; with underscores (as in mw.Title#getPrefixedDb())
		 * @param {string} domain domain name of the wiki
		 * @param {string} url full URL to the page
		 */
		constructor( namespaceId, title, domain, url ) {
			/** @property {number} namespaceId - */
			this.namespaceId = namespaceId;

			/** @property {string} title - */
			this.title = title;

			/** @property {string} domain - */
			this.domain = domain;

			/** @property {string} url - */
			this.url = url;
		}

		getUrl() {
			return this.url;
		}

		getPrefixedDb() {
			return this.title;
		}

		getPrefixedText() {
			return text( this.getPrefixedDb() );
		}

		getDomain() {
			return this.domain;
		}
	}

	/**
	 * Turn underscores into spaces.
	 * Copy of the private function in mw.Title.
	 *
	 * @param {string} s
	 * @return {string}
	 */
	function text( s ) {
		return s ? s.replace( /_/g, ' ' ) : '';
	}

	module.exports = IwTitle;
}() );
