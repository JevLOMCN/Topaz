// Someone has to maintain this wherever it lives. If it live in Core, it becomes a public API.
// If it lives in some client-side target of mediawiki-title that accepts a MediaWiki config instead
// of a SiteInfo, it still becomes a public API. If it lives where used, it becomes a copy and paste
// implementation where each copy can deviate but deletion is easy. See additional discussion in
// T218358 and I95b08e77eece5cd4dae62f6f237d492d6b0fe42b.
( function () {
	const UriUtil = require( './UriUtil.js' );

	/**
	 * Returns the decoded wiki page title referenced by the passed link as a string when parsable.
	 * The title query parameter is returned, if present. Otherwise, a heuristic is used to attempt
	 * to extract the title from the path.
	 *
	 * The API is the source of truth for page titles. This function should only be used in
	 * circumstances where the API cannot be consulted.
	 *
	 * Assuming the current page is on metawiki, consider the following example links and
	 * `newFromUri()` outputs:
	 *
	 *     https://meta.wikimedia.org/wiki/Foo → Foo (path title)
	 *     http://meta.wikimedia.org/wiki/Foo → Foo (mismatching protocol)
	 *     /wiki/Foo → Foo (relative URI)
	 *     /w/index.php?title=Foo → Foo (title query parameter)
	 *     /wiki/Talk:Foo → Talk:Foo (non-main namespace URI)
	 *     /wiki/Foo bar → Foo_bar (name with spaces)
	 *     /wiki/Foo%20bar → Foo_bar (name with percent encoded spaces)
	 *     /wiki/Foo+bar → Foo+bar (name with +)
	 *     /w/index.php?title=Foo%2bbar → Foo+bar (query parameter with +)
	 *     / → null (mismatching article path)
	 *     /wiki/index.php?title=Foo → null (mismatching script path)
	 *     https://archive.org/ → null (mismatching host)
	 *     https://foo.wikimedia.org/ → null (mismatching host)
	 *     https://en.wikipedia.org/wiki/Bar → null (mismatching host)
	 *
	 * This function invokes `Uri.isInternal()` to validate that this link is assuredly a local
	 * wiki link and that the internal usage of both the title query parameter and value of
	 * wgArticlePath are relevant.
	 *
	 * This function doesn't throw. `null` is returned for any unparseable input.
	 *
	 * @param {mw.Uri|Object|string} [uri] Passed to Uri.
	 * @param {Object|boolean} [options] Passed to Uri.
	 * @param {Object|boolean} [options.validateReadOnlyLink] If true, only links that would show a
	 *     page for reading are considered. E.g., `/wiki/Foo` and `/w/index.php?title=Foo` would
	 *     validate but `/w/index.php?title=Foo&action=bar` would not.
	 * @return {mw.Title|null} A Title or `null`.
	 */
	function newFromUri( uri, options ) {
		let mwUri;
		let title;

		try {
			// uri may or may not be a Uri but the Uri constructor accepts a Uri parameter.
			mwUri = new mw.Uri( uri, options );
		} catch ( e ) {
			return null;
		}

		if ( !UriUtil.isInternal( mwUri ) ) {
			return null;
		}

		if ( ( options || {} ).validateReadOnlyLink && !isReadOnlyUri( mwUri ) ) {
			// An unknown query parameter is used. This may not be a read-only link.
			return null;
		}

		if ( mwUri.query.title ) {
			// True if input starts with wgScriptPath.
			const regExp = new RegExp( '^' + mw.util.escapeRegExp( mw.config.get( 'wgScriptPath' ) ) + '/' );

			// URL has a nonempty `title` query parameter like `/w/index.php?title=Foo`. The script
			// path should match.
			const matches = regExp.test( mwUri.path );
			if ( !matches ) {
				return null;
			}

			// The parameter was already decoded at Uri construction.
			title = mwUri.query.title;
		} else {
			// True if input starts with wgArticlePath and ends with a nonempty page title. The
			// first matching group (index 1) is the page title.
			const regExp = new RegExp( '^' + mw.util.escapeRegExp( mw.config.get( 'wgArticlePath' ) ).replace( '\\$1', '(.+)' ) );

			// No title query parameter is present so the URL may be "pretty" like `/wiki/Foo`.
			// `Uri.path` should not contain query parameters or a fragment, as is assumed in
			// `Uri.getRelativePath()`. Try to isolate the title.
			const matches = regExp.exec( mwUri.path );
			if ( !matches || !matches[ 1 ] ) {
				return null;
			}

			try {
				// `Uri.path` was not previously decoded, as is assumed in `Uri.getRelativePath()`,
				// and decoding may now fail. Do not use `Uri.decode()` which is designed to be
				// paired with `Uri.encode()` and replaces `+` characters with spaces.
				title = decodeURIComponent( matches[ 1 ] );
			} catch ( e ) {
				return null;
			}
		}

		// Append the fragment, if present.
		title += mwUri.fragment ? '#' + mwUri.fragment : '';

		return mw.Title.newFromText( title );
	}

	/**
	 * Validates that the passed link is for reading.
	 *
	 * The following links return true:
	 *     /wiki/Foo
	 *     /w/index.php?title=Foo
	 *     /w/index.php?oldid=123
	 *
	 * The following links return false:
	 *     /w/index.php?title=Foo&action=bar
	 *
	 * @private
	 * @static
	 * @method isReadOnlyUri
	 * @param {mw.Uri} uri A Uri to an internal wiki page.
	 * @return {boolean} True if uri has no query parameters or only known parameters for reading.
	 */
	function isReadOnlyUri( uri ) {
		const length = Object.keys( uri.query ).length;
		return length === ( ( 'oldid' in uri.query ? 1 : 0 ) + ( 'title' in uri.query ? 1 : 0 ) );
	}

	module.exports = {
		newFromUri: newFromUri
	};
}() );
