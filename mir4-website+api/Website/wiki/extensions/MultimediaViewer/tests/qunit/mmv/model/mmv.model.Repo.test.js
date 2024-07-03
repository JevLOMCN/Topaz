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

const { Repo, ForeignApiRepo, ForeignDbRepo } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.model.Repo', QUnit.newMwEnvironment() );

	QUnit.test( 'Repo constructor sense check', function ( assert ) {
		const displayName = 'Wikimedia Commons';
		const favicon = '//commons.wikimedia.org/favicon.ico';
		const apiUrl = '//commons.wikimedia.org/w/api.php';
		const server = '//commons.wikimedia.org';
		const articlePath = '//commons.wikimedia.org/wiki/$1';
		const descBaseUrl = '//commons.wikimedia.org/wiki/File:';
		const localRepo = new Repo( displayName, favicon, true );
		const foreignApiRepo = new ForeignApiRepo( displayName, favicon,
			false, apiUrl, server, articlePath );
		const foreignDbRepo = new ForeignDbRepo( displayName, favicon, false, descBaseUrl );

		assert.true( localRepo instanceof Repo, 'Local repo creation works' );
		assert.true( foreignApiRepo instanceof ForeignApiRepo,
			'Foreign API repo creation works' );
		assert.true( foreignDbRepo instanceof ForeignDbRepo, 'Foreign DB repo creation works' );
	} );

	QUnit.test( 'getArticlePath()', function ( assert ) {
		const displayName = 'Wikimedia Commons';
		const favicon = '//commons.wikimedia.org/favicon.ico';
		const apiUrl = '//commons.wikimedia.org/w/api.php';
		const server = '//commons.wikimedia.org';
		const articlePath = '/wiki/$1';
		const descBaseUrl = '//commons.wikimedia.org/wiki/File:';
		const localRepo = new Repo( displayName, favicon, true );
		const foreignApiRepo = new ForeignApiRepo( displayName, favicon,
			false, apiUrl, server, articlePath );
		const foreignDbRepo = new ForeignDbRepo( displayName, favicon, false, descBaseUrl );
		const expectedLocalArticlePath = '/wiki/$1';
		const expectedFullArticlePath = '//commons.wikimedia.org/wiki/$1';
		const oldWgArticlePath = mw.config.get( 'wgArticlePath' );
		const oldWgServer = mw.config.get( 'wgServer' );

		mw.config.set( 'wgArticlePath', '/wiki/$1' );
		mw.config.set( 'wgServer', server );

		assert.strictEqual( localRepo.getArticlePath(), expectedLocalArticlePath,
			'Local repo article path is correct' );
		assert.strictEqual( localRepo.getArticlePath( true ), expectedFullArticlePath,
			'Local repo absolute article path is correct' );
		assert.strictEqual( foreignApiRepo.getArticlePath(), expectedFullArticlePath,
			'Foreign API article path is correct' );
		assert.strictEqual( foreignDbRepo.getArticlePath(), expectedFullArticlePath,
			'Foreign DB article path is correct' );

		mw.config.set( 'wgArticlePath', oldWgArticlePath );
		mw.config.set( 'wgServer', oldWgServer );
	} );

	QUnit.test( 'getSiteLink()', function ( assert ) {
		const displayName = 'Wikimedia Commons';
		const favicon = '//commons.wikimedia.org/favicon.ico';
		const apiUrl = '//commons.wikimedia.org/w/api.php';
		const server = '//commons.wikimedia.org';
		const articlePath = '/wiki/$1';
		const descBaseUrl = '//commons.wikimedia.org/wiki/File:';
		const localRepo = new Repo( displayName, favicon, true );
		const foreignApiRepo = new ForeignApiRepo( displayName, favicon,
			false, apiUrl, server, articlePath );
		const foreignDbRepo = new ForeignDbRepo( displayName, favicon, false, descBaseUrl );
		const expectedSiteLink = '//commons.wikimedia.org/wiki/';
		const oldWgArticlePath = mw.config.get( 'wgArticlePath' );
		const oldWgServer = mw.config.get( 'wgServer' );

		mw.config.set( 'wgArticlePath', '/wiki/$1' );
		mw.config.set( 'wgServer', server );

		assert.strictEqual( localRepo.getSiteLink(), expectedSiteLink,
			'Local repo site link is correct' );
		assert.strictEqual( foreignApiRepo.getSiteLink(), expectedSiteLink,
			'Foreign API repo site link is correct' );
		assert.strictEqual( foreignDbRepo.getSiteLink(), expectedSiteLink,
			'Foreign DB repo site link is correct' );

		mw.config.set( 'wgArticlePath', oldWgArticlePath );
		mw.config.set( 'wgServer', oldWgServer );
	} );

}() );
