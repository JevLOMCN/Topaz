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

const { ImageProvider } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.provider.Image', QUnit.newMwEnvironment() );

	QUnit.test( 'Image constructor sense check', function ( assert ) {
		const imageProvider = new ImageProvider();

		assert.true( imageProvider instanceof ImageProvider );
	} );

	QUnit.test( 'Image load success', function ( assert ) {
		const url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0' +
				'iAAAABlBMVEUAAAD///+l2Z/dAAAAM0lEQVR4nGP4/5/h/1+G/58ZDrAz3D/McH' +
				'8yw83NDDeNGe4Ug9C9zwz3gVLMDA/A6P9/AFGGFyjOXZtQAAAAAElFTkSuQmCC';
		const imageProvider = new ImageProvider();

		imageProvider.imagePreloadingSupported = () => false;

		return imageProvider.get( url ).then( function ( image ) {
			assert.true( image instanceof HTMLImageElement,
				'success handler was called with the image element' );
			assert.strictEqual( image.src, url, 'image src is correct' );
		} );
	} );

	QUnit.test( 'Image caching', function ( assert ) {
		const url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0' +
				'iAAAABlBMVEUAAAD///+l2Z/dAAAAM0lEQVR4nGP4/5/h/1+G/58ZDrAz3D/McH' +
				'8yw83NDDeNGe4Ug9C9zwz3gVLMDA/A6P9/AFGGFyjOXZtQAAAAAElFTkSuQmCC';
		const url2 = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		let result;
		const imageProvider = new ImageProvider();

		imageProvider.imagePreloadingSupported = () => false;

		return QUnit.whenPromisesComplete(
			imageProvider.get( url ).then( function ( image ) {
				result = image;
				assert.true( image instanceof HTMLImageElement,
					'success handler was called with the image element' );
				assert.strictEqual( image.src, url, 'image src is correct' );
			} ),

			imageProvider.get( url ).then( function ( image ) {
				assert.strictEqual( image, result, 'image element is cached and not regenerated' );
				assert.strictEqual( image.src, url, 'image src is correct' );
			} ),

			imageProvider.get( url2 ).then( function ( image ) {
				assert.notStrictEqual( image, result, 'image element for different url is not cached' );
				assert.strictEqual( image.src, url2, 'image src is correct' );
			} )
		);
	} );

	QUnit.test( 'Image load fail', function ( assert ) {
		const imageProvider = new ImageProvider();
		const oldMwLog = mw.log;
		const done = assert.async();
		let mwLogCalled = false;

		imageProvider.imagePreloadingSupported = () => false;
		mw.log = function () {
			mwLogCalled = true;
		};

		imageProvider.get( 'doesntexist.png' ).fail( function () {
			assert.true( true, 'fail handler was called' );
			assert.true( mwLogCalled, 'mw.log was called' );
			mw.log = oldMwLog;
			done();
		} );
	} );

	QUnit.test( 'Image load with preloading supported', function ( assert ) {
		const url = mw.config.get( 'wgExtensionAssetsPath' ) + '/MultimediaViewer/resources/mmv.bootstrap/img/expand.svg';
		const imageProvider = new ImageProvider();
		const endsWith = function ( a, b ) {
			return a.indexOf( b ) === a.length - b.length;
		};

		imageProvider.imagePreloadingSupported = () => true;
		imageProvider.performance = {
			record: function () {
				return $.Deferred().resolve();
			}
		};

		return imageProvider.get( url ).then( function ( image ) {
			// can't test equality as browsers transform this to a full URL
			assert.true( endsWith( image.src, url ), 'local image loaded with correct source' );
		} );
	} );

	QUnit.test( 'Failed image load with preloading supported', function ( assert ) {
		const url = 'nosuchimage.png';
		const imageProvider = new ImageProvider();
		const done = assert.async();

		imageProvider.imagePreloadingSupported = () => true;
		imageProvider.performance = {
			record: function () {
				return $.Deferred().resolve();
			}
		};

		imageProvider.get( url ).fail( function () {
			assert.true( true, 'Fail callback called for non-existing image' );
			done();
		} );
	} );

	QUnit.test( 'imageQueryParameter', function ( assert ) {
		const imageProvider = new ImageProvider( 'foo' );
		const done = assert.async();

		imageProvider.imagePreloadingSupported = () => false;
		imageProvider.rawGet = function ( url ) {
			assert.strictEqual( url, 'http://www.wikipedia.org/?foo', 'Extra parameter added' );

			return $.Deferred().resolve();
		};

		imageProvider.get( 'http://www.wikipedia.org/' ).then( function () {
			done();
		} );
	} );
}() );
