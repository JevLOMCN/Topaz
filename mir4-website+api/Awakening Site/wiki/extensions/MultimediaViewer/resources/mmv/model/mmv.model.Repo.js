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
	 * Represents information about a single image repository
	 */
	class Repo {
		/**
		 * @param {string} displayName
		 * @param {string} favIcon URL to the repo's favicon
		 * @param {boolean} isLocal
		 */
		constructor(
			displayName,
			favIcon,
			isLocal
		) {
			/** @property {string} displayName Human-readable name of the repository */
			this.displayName = displayName;

			/** @property {string} favIcon An icon that represents the repository */
			this.favIcon = favIcon;

			/** @property {boolean} isLocal Whether the repository is the local wiki */
			this.isLocal = isLocal;
		}

		/**
		 * Returns true if the repo is Wikimedia Commons.
		 *
		 * @return {boolean}
		 */
		isCommons() {
			// there does not seem to be a sensible way to do this
			return this.displayName === 'Wikimedia Commons';
		}

		/**
		 * Gets the article path for the repository.
		 *
		 * @param {boolean} absolute if true, the URL will be absolute (if false, it still might be)
		 * @return {string} Replace $1 with the page name you want to link to.
		 */
		getArticlePath( absolute ) {
			let articlePath = mw.config.get( 'wgArticlePath' );
			if ( absolute ) {
				articlePath = mw.config.get( 'wgServer' ) + articlePath;
			}
			return articlePath;
		}

		/**
		 * Gets the a link to the site where the image was uploaded to.
		 * This is a hack and might break for wikis with exotic config; unfortunately no
		 * better data is provided currently.
		 *
		 * @return {string}
		 */
		getSiteLink() {
			return this.getArticlePath( true ).replace( '$1', '' );
		}
	}

	/**
	 * Represents information about a foreign API repository
	 */
	class ForeignApiRepo extends Repo {

		/**
		 * @inheritdoc
		 * @param {string} displayName
		 * @param {string} favIcon
		 * @param {boolean} isLocal
		 * @param {string} apiUrl URL to the wiki's api.php
		 * @param {string} server Hostname for the wiki
		 * @param {string} articlePath Path to articles on the wiki, relative to the hostname.
		 */
		constructor(
			displayName,
			favIcon,
			isLocal,
			apiUrl,
			server,
			articlePath
		) {
			super( displayName, favIcon, isLocal );

			/** @property {string} apiUrl URL to the wiki's api.php */
			this.apiUrl = apiUrl;

			/** @property {string} server Hostname for the wiki */
			this.server = server;

			/** @property {string} articlePath Path to articles on the wiki, relative to the hostname */
			this.articlePath = articlePath;

			/** @property {string} absoluteArticlePath Path to articles on the wiki, relative to nothing */
			this.absoluteArticlePath = server + articlePath;
		}

		/**
		 * @override
		 * @inheritdoc
		 */
		getArticlePath() {
			return this.absoluteArticlePath;
		}

		/**
		 * @override
		 * @inheritdoc
		 */
		isCommons() {
			// eslint-disable-next-line security/detect-unsafe-regex
			return /^(https?:)?\/\/commons\.wikimedia\.org/.test( this.server );
		}
	}

	/**
	 * Represents information about a foreign, shared DB repository
	 */
	class ForeignDbRepo extends Repo {
		/**
		 * @inheritdoc
		 * @param {string} displayName
		 * @param {string} favIcon
		 * @param {boolean} isLocal
		 * @param {string} descBaseUrl Base URL for description pages - should include the "File:" prefix or similar.
		 */
		constructor(
			displayName,
			favIcon,
			isLocal,
			descBaseUrl
		) {
			super( displayName, favIcon, isLocal );

			/** @property {string} descBaseUrl Base URL for descriptions on the wiki - append a file's title to this to get the description page */
			this.descBaseUrl = descBaseUrl;
		}

		/**
		 * @override
		 * @inheritdoc
		 */
		getArticlePath() {
			return this.descBaseUrl.replace( /[^/:]*:$/, '$1' );
		}

		/**
		 * @override
		 * @inheritdoc
		 */
		isCommons() {
			// eslint-disable-next-line security/detect-unsafe-regex
			return /^(https?:)?\/\/commons\.wikimedia\.org/.test( this.descBaseUrl );
		}
	}

	/**
	 * Creates a new object from repoInfo we found in an API response.
	 *
	 * @static
	 * @param {Object} repoInfo
	 * @return {Repo}
	 */
	function newFromRepoInfo( repoInfo ) {
		if ( repoInfo.apiurl ) {
			return new ForeignApiRepo(
				repoInfo.displayname,
				repoInfo.favicon,
				false,
				repoInfo.apiurl,
				repoInfo.server,
				repoInfo.articlepath
			);
		} else if ( repoInfo.descBaseUrl ) {
			return new ForeignDbRepo(
				repoInfo.displayname,
				repoInfo.favicon,
				false,
				repoInfo.descBaseUrl
			);
		} else {
			return new Repo( repoInfo.displayname, repoInfo.favicon, repoInfo.local );
		}
	}

	module.exports = { Repo, ForeignApiRepo, ForeignDbRepo, newFromRepoInfo };
}() );
