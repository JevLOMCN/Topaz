( function () {
	const UriUtil = require( '../../../resources/skins.minerva.scripts/UriUtil.js' );
	const mwUriOrg = mw.Uri;

	QUnit.module( 'Minerva UriUtil', {
		beforeEach: function () {
			mw.Uri = mw.UriRelative( 'https://meta.wikimedia.org/w/index.php' );
		},
		afterEach: function () {
			mw.Uri = mwUriOrg;
		}
	} );

	QUnit.test( '.isInternal()', function ( assert ) {
		assert.true(
			UriUtil.isInternal( new mw.Uri( '/relative' ) ),
			'relative URLs are internal'
		);
		assert.true(
			UriUtil.isInternal( new mw.Uri( 'http://meta.wikimedia.org/' ) ),
			'matching hosts are internal'
		);
		assert.true(
			UriUtil.isInternal( new mw.Uri( 'https:/meta.wikimedia.org/' ) ),
			'protocol is irrelevant'
		);
		assert.true(
			UriUtil.isInternal( new mw.Uri( 'https://meta.wikimedia.org/path' ) ),
			'path is irrelevant'
		);
		assert.false(
			UriUtil.isInternal( new mw.Uri( 'https://archive.org/' ) ),
			'external links are not internal'
		);
		assert.false(
			UriUtil.isInternal( new mw.Uri( 'https://www.meta.wikimedia.org/' ) ),
			'differing subdomains are not internal'
		);
	} );
}() );
