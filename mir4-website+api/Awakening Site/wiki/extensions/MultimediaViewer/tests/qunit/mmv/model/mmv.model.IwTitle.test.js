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

const { IwTitle } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.model.IwTitle', QUnit.newMwEnvironment() );

	QUnit.test( 'constructor sense test', function ( assert ) {
		const namespace = 4;
		const fullPageName = 'User_talk:John_Doe';
		const domain = 'en.wikipedia.org';
		const url = 'https://en.wikipedia.org/wiki/User_talk:John_Doe';
		const title = new IwTitle( namespace, fullPageName, domain, url );

		assert.true( title instanceof IwTitle );
	} );

	QUnit.test( 'getters', function ( assert ) {
		const namespace = 4;
		const fullPageName = 'User_talk:John_Doe';
		const domain = 'en.wikipedia.org';
		const url = 'https://en.wikipedia.org/wiki/User_talk:John_Doe';
		const title = new IwTitle( namespace, fullPageName, domain, url );

		assert.strictEqual( title.getUrl(), url, 'getUrl()' );
		assert.strictEqual( title.getDomain(), domain, 'getDomain()' );
		assert.strictEqual( title.getPrefixedDb(), fullPageName, 'getPrefixedDb()' );
		assert.strictEqual( title.getPrefixedText(), 'User talk:John Doe', 'getPrefixedText()' );
	} );
}() );
