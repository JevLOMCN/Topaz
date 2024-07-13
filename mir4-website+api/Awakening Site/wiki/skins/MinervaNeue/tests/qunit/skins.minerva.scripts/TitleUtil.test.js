( function () {
	const TitleUtil = require( '../../../resources/skins.minerva.scripts/TitleUtil.js' );
	const mwUriOrg = mw.Uri;

	QUnit.module( 'Minerva TitleUtil', QUnit.newMwEnvironment( {
		beforeEach: function () {
			this.mwUriOrg = mw.Uri;
			mw.Uri = mw.UriRelative( 'https://meta.wikimedia.org/w/index.php' );
			mw.config.set( {
				wgArticlePath: '/wiki/$1',
				wgScriptPath: '/w'
			} );
		},
		afterEach: function () {
			mw.Uri = mwUriOrg;
		},
		// mw.Title relies on these three config vars
		// Restore them after each test run
		config: {
			wgFormattedNamespaces: {
				'-2': 'Media',
				'-1': 'Special',
				0: '',
				1: 'Talk',
				2: 'User',
				3: 'User talk',
				4: 'Wikipedia',
				5: 'Wikipedia talk',
				6: 'File',
				7: 'File talk',
				8: 'MediaWiki',
				9: 'MediaWiki talk',
				10: 'Template',
				11: 'Template talk',
				12: 'Help',
				13: 'Help talk',
				14: 'Category',
				15: 'Category talk',
				// testing custom / localized namespace
				100: 'Penguins'
			},
			wgNamespaceIds: {
				/* eslint-disable camelcase */
				media: -2,
				special: -1,
				'': 0,
				talk: 1,
				user: 2,
				user_talk: 3,
				wikipedia: 4,
				wikipedia_talk: 5,
				file: 6,
				file_talk: 7,
				mediawiki: 8,
				mediawiki_talk: 9,
				template: 10,
				template_talk: 11,
				help: 12,
				help_talk: 13,
				category: 14,
				category_talk: 15,
				image: 6,
				image_talk: 7,
				project: 4,
				project_talk: 5,
				// Testing custom namespaces and aliases
				penguins: 100,
				antarctic_waterfowl: 100
				/* eslint-enable camelcase */
			},
			wgCaseSensitiveNamespaces: []
		}
	} ) );

	QUnit.test.each( '.newFromUri() authority', {
		empty: '',
		metawiki: 'https://meta.wikimedia.org'
	}, function ( assert, authority ) {
		const validateReadOnlyLink = { validateReadOnlyLink: true };
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/w/index.php?title=Title' ).getPrefixedDb(),
			'Title',
			'title is in query parameter'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/wiki/Title' ).getPrefixedDb(),
			'Title',
			'title is in path'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/foo/bar/wiki/Title' ),
			null,
			'title is not in nonmatching path'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/wiki/%E6%B8%AC%E8%A9%A6' ).getPrefixedDb(),
			'測試',
			'title is decoded'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/wiki/Foo bar' ).getPrefixedDb(),
			'Foo_bar',
			'title with space is decoded'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/wiki/Foo%20bar' ).getPrefixedDb(),
			'Foo_bar',
			'title with encoded space is decoded'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/w/index.php?title=Title#fragment' ).getPrefixedDb(),
			'Title',
			'fragment is omitted from query title'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/wiki/Title#fragment' ).getPrefixedDb(),
			'Title',
			'fragment is omitted from path title'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/w/index.php?title=Title#fragment' ).getFragment(),
			'fragment',
			'fragment is present after query parameter'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/wiki/Title#fragment' ).getFragment(),
			'fragment',
			'fragment is present after path'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/w/index.php?title=Title#foo%20bar' ).getFragment(),
			'foo bar',
			'fragment is decoded'
		);

		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/w/index.php?title=Title', validateReadOnlyLink ).getPrefixedDb(),
			'Title',
			'query title is read-only'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/wiki/Title', validateReadOnlyLink ).getPrefixedDb(),
			'Title',
			'path title is read-only'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/w/index.php?title=Title&oldid=123', validateReadOnlyLink ).getPrefixedDb(),
			'Title',
			'query title with revision is read-only'
		);
		assert.strictEqual(
			TitleUtil.newFromUri( authority + '/w/index.php?title=Title&param', validateReadOnlyLink ),
			null,
			'query title with unknown parameter is not read-only'
		);
	} );

	QUnit.test.each( '.newFromUri() bad input', [
		'%', null, undefined, '', ' ', '/', {}, '\\', '/wiki/%', '/w/index.php?title=%'
	], function ( assert, input ) {
		assert.strictEqual(
			TitleUtil.newFromUri( input ),
			null,
			'no Title in bad input input="' + input + '"'
		);
	} );

	QUnit.test( '.newFromUri() misc', function ( assert ) {
		// Parameters are passed to Uri's constructor.
		assert.strictEqual(
			TitleUtil.newFromUri( { protocol: 'https',
				host: 'meta.wikimedia.org',
				path: '/wiki/Title' } ).getPrefixedDb(),
			'Title',
			'title is in Uri parameters'
		);

		// A Uri itself can be passed.
		assert.strictEqual(
			TitleUtil.newFromUri( new mw.Uri( 'https://meta.wikimedia.org/wiki/Title' ) ).getPrefixedDb(),
			'Title',
			'title is in Uri'
		);

		// JSDoc examples.
		// https://meta.wikimedia.org/wiki/Foo → Foo (path title)
		assert.strictEqual(
			TitleUtil.newFromUri( 'https://meta.wikimedia.org/wiki/Foo' ).getPrefixedDb(),
			'Foo',
			'path title'
		);

		// http://meta.wikimedia.org/wiki/Foo → Foo (mismatching protocol)
		assert.strictEqual(
			TitleUtil.newFromUri( 'http://meta.wikimedia.org/wiki/Foo' ).getPrefixedDb(),
			'Foo',
			'mismatching protocol'
		);

		// /wiki/Foo → Foo (relative URI)
		assert.strictEqual(
			TitleUtil.newFromUri( '/wiki/Foo' ).getPrefixedDb(),
			'Foo',
			'relative URI'
		);

		// /w/index.php?title=Foo → Foo (title query parameter)
		assert.strictEqual(
			TitleUtil.newFromUri( '/w/index.php?title=Foo' ).getPrefixedDb(),
			'Foo',
			'title query parameter'
		);

		// /wiki/Talk:Foo → Talk:Foo (non-main namespace URI)
		assert.strictEqual(
			TitleUtil.newFromUri( '/wiki/Talk:Foo' ).getPrefixedDb(),
			'Talk:Foo',
			'non-main namespace URI'
		);

		// /wiki/Foo bar → Foo_bar (name with spaces)
		assert.strictEqual(
			TitleUtil.newFromUri( '/wiki/Foo bar' ).getPrefixedDb(),
			'Foo_bar',
			'name with spaces'
		);

		// /wiki/Foo%20bar → Foo_bar (name with percent encoded spaces)
		assert.strictEqual(
			TitleUtil.newFromUri( '/wiki/Foo%20bar' ).getPrefixedDb(),
			'Foo_bar',
			'name with percent encoded spaces'
		);

		// /wiki/Foo+bar → Foo+bar (name with +)
		assert.strictEqual(
			TitleUtil.newFromUri( '/wiki/Foo+bar' ).getPrefixedDb(),
			'Foo+bar',
			'name with +'
		);

		// /w/index.php?title=Foo%2bbar → Foo+bar (query parameter with +)
		assert.strictEqual(
			TitleUtil.newFromUri( '/w/index.php?title=Foo%2bbar' ).getPrefixedDb(),
			'Foo+bar',
			'query parameter with +'
		);

		// / → null (mismatching article path)
		assert.strictEqual(
			TitleUtil.newFromUri( '/' ),
			null,
			'mismatching article path'
		);

		// /wiki/index.php?title=Foo → null (mismatching script path)
		assert.strictEqual(
			TitleUtil.newFromUri( '/wiki/index.php?title=Foo' ),
			null,
			'mismatching script path'
		);

		// https://archive.org/ → null (mismatching host)
		assert.strictEqual(
			TitleUtil.newFromUri( 'https://archive.org/' ),
			null,
			'mismatching host (0)'
		);

		// https://foo.wikimedia.org/ → null (mismatching host)
		assert.strictEqual(
			TitleUtil.newFromUri( 'https://foo.wikimedia.org/' ),
			null,
			'mismatching host (1)'
		);

		// https://en.wikipedia.org/wiki/Bar → null (mismatching host)
		assert.strictEqual(
			TitleUtil.newFromUri( 'https://en.wikipedia.org/wiki/Bar' ),
			null,
			'mismatching host (2)'
		);
	} );
}() );
