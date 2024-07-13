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

const { Api } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.provider.Api', QUnit.newMwEnvironment() );

	QUnit.test( 'Api constructor sense check', function ( assert ) {
		const api = { get: function () {} };
		const options = {};
		const apiProvider = new Api( api, options );
		const ApiProviderWithNoOptions = new Api( api );

		assert.true( apiProvider instanceof Api );
		assert.true( ApiProviderWithNoOptions instanceof Api );
	} );

	QUnit.test( 'apiGetWithMaxAge()', function ( assert ) {
		const api = {};
		let options = {};
		let apiProvider = new Api( api, options );

		api.get = this.sandbox.stub();
		apiProvider.apiGetWithMaxAge( {} );
		assert.false( 'maxage' in api.get.getCall( 0 ).args[ 0 ], 'maxage is not set by default' );
		assert.false( 'smaxage' in api.get.getCall( 0 ).args[ 0 ], 'smaxage is not set by default' );

		options = { maxage: 123 };
		apiProvider = new Api( api, options );

		api.get = this.sandbox.stub();
		apiProvider.apiGetWithMaxAge( {} );
		assert.strictEqual( api.get.getCall( 0 ).args[ 0 ].maxage, 123, 'maxage falls back to provider default' );
		assert.strictEqual( api.get.getCall( 0 ).args[ 0 ].smaxage, 123, 'smaxage falls back to provider default' );

		api.get = this.sandbox.stub();
		apiProvider.apiGetWithMaxAge( {}, null, 456 );
		assert.strictEqual( api.get.getCall( 0 ).args[ 0 ].maxage, 456, 'maxage can be overridden' );
		assert.strictEqual( api.get.getCall( 0 ).args[ 0 ].smaxage, 456, 'smaxage can be overridden' );

		api.get = this.sandbox.stub();
		apiProvider.apiGetWithMaxAge( {}, null, null );
		assert.false( 'maxage' in api.get.getCall( 0 ).args[ 0 ], 'maxage can be overridden to unset' );
		assert.false( 'smaxage' in api.get.getCall( 0 ).args[ 0 ], 'smaxage can be overridden to unset' );
	} );

	QUnit.test( 'getCachedPromise success', function ( assert ) {
		const api = { get: function () {} };
		const apiProvider = new Api( api );
		const oldMwLog = mw.log;
		let promiseShouldBeCached = false;

		mw.log = function () {
			assert.true( false, 'mw.log should not have been called' );
		};

		const promiseSource = function ( result ) {
			return function () {
				assert.strictEqual( promiseShouldBeCached, false, 'promise was not cached' );
				return $.Deferred().resolve( result );
			};
		};

		apiProvider.getCachedPromise( 'foo', promiseSource( 1 ) ).done( function ( result ) {
			assert.strictEqual( result, 1, 'result comes from the promise source' );
		} );

		apiProvider.getCachedPromise( 'bar', promiseSource( 2 ) ).done( function ( result ) {
			assert.strictEqual( result, 2, 'result comes from the promise source' );
		} );

		promiseShouldBeCached = true;
		apiProvider.getCachedPromise( 'foo', promiseSource( 3 ) ).done( function ( result ) {
			assert.strictEqual( result, 1, 'result comes from cache' );
		} );

		mw.log = oldMwLog;
	} );

	QUnit.test( 'getCachedPromise failure', function ( assert ) {
		const api = { get: function () {} };
		const apiProvider = new Api( api );
		const oldMwLog = mw.log;
		let promiseShouldBeCached = false;

		mw.log = function () {
			assert.true( true, 'mw.log was called' );
		};

		const promiseSource = function ( result ) {
			return function () {
				assert.strictEqual( promiseShouldBeCached, false, 'promise was not cached' );
				return $.Deferred().reject( result );
			};
		};

		apiProvider.getCachedPromise( 'foo', promiseSource( 1 ) ).fail( function ( result ) {
			assert.strictEqual( result, 1, 'result comes from the promise source' );
		} );

		apiProvider.getCachedPromise( 'bar', promiseSource( 2 ) ).fail( function ( result ) {
			assert.strictEqual( result, 2, 'result comes from the promise source' );
		} );

		promiseShouldBeCached = true;
		apiProvider.getCachedPromise( 'foo', promiseSource( 3 ) ).fail( function ( result ) {
			assert.strictEqual( result, 1, 'result comes from cache' );
		} );

		mw.log = oldMwLog;
	} );

	QUnit.test( 'getErrorMessage', function ( assert ) {
		const api = { get: function () {} };
		const apiProvider = new Api( api );

		const errorMessage = apiProvider.getErrorMessage( {
			servedby: 'mw1194',
			error: {
				code: 'unknown_action',
				info: 'Unrecognized value for parameter \'action\': FOO'
			}
		} );
		assert.strictEqual( errorMessage,
			'unknown_action: Unrecognized value for parameter \'action\': FOO',
			'error message is parsed correctly' );

		assert.strictEqual( apiProvider.getErrorMessage( {} ), 'unknown error', 'missing error message is handled' );
	} );

	QUnit.test( 'getQueryField', function ( assert ) {
		const api = { get: function () {} };
		const apiProvider = new Api( api );
		const done = assert.async( 3 );

		const data = {
			query: {
				imageusage: [
					{
						pageid: 736,
						ns: 0,
						title: 'Albert Einstein'
					}
				]
			}
		};

		apiProvider.getQueryField( 'imageusage', data ).then( function ( field ) {
			assert.strictEqual( field, data.query.imageusage, 'specified field is found' );
			done();
		} );
		apiProvider.getQueryField( 'imageusage', {} ).fail( function () {
			assert.true( true, 'promise rejected when data is missing' );
			done();
		} );

		apiProvider.getQueryField( 'imageusage', { data: { query: {} } } ).fail( function () {
			assert.true( true, 'promise rejected when field is missing' );
			done();
		} );
	} );

	QUnit.test( 'getQueryPage', function ( assert ) {
		const api = { get: function () {} };
		const apiProvider = new Api( api );
		const done = assert.async( 5 );

		const data = {
			query: {
				pages: [
					{
						title: 'File:Stuff.jpg'
					}
				]
			}
		};

		apiProvider.getQueryPage( data ).then( function ( field ) {
			assert.strictEqual( field, data.query.pages[ 0 ], 'specified page is found' );
			done();
		} );

		apiProvider.getQueryPage( {} ).fail( function () {
			assert.true( true, 'promise rejected when data is missing' );
			done();
		} );

		apiProvider.getQueryPage( { data: { query: {} } } ).fail( function () {
			assert.true( true, 'promise rejected when pages are missing' );
			done();
		} );

		apiProvider.getQueryPage( { data: { query: { pages: [] } } } ).fail( function () {
			assert.true( true, 'promise rejected when pages are empty' );
			done();
		} );

		apiProvider.getQueryPage( {
			query: {
				pages: [
					{
						title: 'File:Stuff.jpg'
					},
					{
						title: 'File:OtherStuff.jpg'
					}
				]
			}
		} ).fail( function () {
			assert.true( true, 'promise rejected when data contains two entries' );
			done();
		} );

	} );
}() );
