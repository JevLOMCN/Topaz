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

const { GuessedThumbnailInfo } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.provider.GuessedThumbnailInfo', QUnit.newMwEnvironment() );

	QUnit.test( 'Constructor sense check', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		assert.true( provider instanceof GuessedThumbnailInfo, 'Constructor call successful' );
	} );

	QUnit.test( 'get()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		const file = new mw.Title( 'File:Copyleft.svg' );
		const sampleUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/180px-Copyleft.svg.png';
		const width = 300;
		const originalWidth = 512;
		const originalHeight = 512;
		const resultUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png';
		const done = assert.async();

		provider.getUrl = () => resultUrl;
		let result = provider.get( file, sampleUrl, width, originalWidth, originalHeight );
		assert.true( typeof result.then === 'function', 'Result is a promise' );
		assert.strictEqual( result.state(), 'resolved', 'Result is resolved' );
		result.then( function ( thumbnailInfo ) {
			assert.true( typeof thumbnailInfo.width === 'number', 'Width is set' );
			assert.true( typeof thumbnailInfo.height === 'number', 'Height is set' );
			assert.strictEqual( thumbnailInfo.url, resultUrl, 'URL is set' );
			done();
		} );

		provider.getUrl = () => undefined;
		result = provider.get( file, sampleUrl, width, originalWidth, originalHeight );
		assert.true( typeof result.then === 'function', 'Result is a promise' );
		assert.strictEqual( result.state(), 'rejected', 'Result is rejected' );
	} );

	QUnit.test( 'getUrl()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Elizabeth_I_George_Gower.jpg' );
		let originalWidth = 922;
		let originalHeight = 968;

		let sampleUrl = 'http://upload.wikimedia.org/wikipedia/commons/7/78/Elizabeth_I_George_Gower.jpg';
		let width = 1000;
		let expectedUrl = 'http://upload.wikimedia.org/wikipedia/commons/7/78/Elizabeth_I_George_Gower.jpg';
		let resultUrl = provider.getUrl( file, sampleUrl, width, originalWidth, originalHeight );
		assert.strictEqual( resultUrl, expectedUrl, 'Simple case - full image, needs no resize' );

		sampleUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Elizabeth_I_George_Gower.jpg/180px-Elizabeth_I_George_Gower.jpg';
		width = 400;
		expectedUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Elizabeth_I_George_Gower.jpg/400px-Elizabeth_I_George_Gower.jpg';
		resultUrl = provider.getUrl( file, sampleUrl, width, originalWidth, originalHeight );
		assert.strictEqual( resultUrl, expectedUrl, 'Mostly simple case - just need to replace size' );

		sampleUrl = 'http://upload.wikimedia.org/wikipedia/commons/7/78/Elizabeth_I_George_Gower.jpg';
		width = 400;
		expectedUrl = undefined;
		resultUrl = provider.getUrl( file, sampleUrl, width, originalWidth, originalHeight );
		assert.strictEqual( resultUrl, expectedUrl, 'We bail on hard case - full to thumbnail' );

		sampleUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Elizabeth_I_George_Gower.jpg/180px-Elizabeth_I_George_Gower.jpg';
		width = 1000;
		expectedUrl = 'http://upload.wikimedia.org/wikipedia/commons/7/78/Elizabeth_I_George_Gower.jpg';
		resultUrl = provider.getUrl( file, sampleUrl, width, originalWidth, originalHeight );
		assert.strictEqual( resultUrl, expectedUrl, 'Thumbnail to full-size, image with limited size' );

		file = new mw.Title( 'File:Ranunculus_gmelinii_NRCS-2.tiff' );
		sampleUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Ranunculus_gmelinii_NRCS-2.tiff/lossy-page1-428px-Ranunculus_gmelinii_NRCS-2.tiff.jpg';
		width = 2000;
		originalWidth = 1500;
		originalHeight = 2100;
		expectedUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Ranunculus_gmelinii_NRCS-2.tiff/lossy-page1-1500px-Ranunculus_gmelinii_NRCS-2.tiff.jpg';
		resultUrl = provider.getUrl( file, sampleUrl, width, originalWidth, originalHeight );
		assert.strictEqual( resultUrl, expectedUrl, 'Thumbnail to full-size, image which cannot be displayed directly' );

		file = new mw.Title( 'File:Copyleft.svg' );
		sampleUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/180px-Copyleft.svg.png';
		width = 1000;
		originalWidth = 512;
		originalHeight = 512;
		expectedUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/1000px-Copyleft.svg.png';
		resultUrl = provider.getUrl( file, sampleUrl, width, originalWidth, originalHeight );
		assert.strictEqual( resultUrl, expectedUrl, 'Thumbnail to "full-size", image with unlimited size' );
	} );

	QUnit.test( 'needsOriginal()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.needsOriginal( file, 100, 1000 ), false, 'Thumbnail of an SVG smaller than the original size doesn\'t need original' );
		assert.strictEqual( provider.needsOriginal( file, 1000, 1000 ), false, 'Thumbnail of an SVG equal to the original size doesn\'t need original' );
		assert.strictEqual( provider.needsOriginal( file, 2000, 1000 ), false, 'Thumbnail of an SVG bigger than the original size doesn\'t need original' );

		file = new mw.Title( 'File:Foo.png' );

		assert.strictEqual( provider.needsOriginal( file, 100, 1000 ), false, 'Thumbnail of a PNG smaller than the original size doesn\'t need original' );
		assert.strictEqual( provider.needsOriginal( file, 1000, 1000 ), true, 'Thumbnail of a PNG equal to the original size needs original' );
		assert.strictEqual( provider.needsOriginal( file, 2000, 1000 ), true, 'Thumbnail of a PNG bigger than the original size needs original' );
	} );

	QUnit.test( 'isFullSizeUrl()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		const file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.isFullSizeUrl( 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png', file ),
			false,
			'Thumbnail url recognized as not being full size' );
		assert.strictEqual( provider.isFullSizeUrl( 'http://upload.wikimedia.org/wikipedia/commons/8/8b/Copyleft.svg', file ),
			true,
			'Original url recognized as being full size' );
	} );

	QUnit.test( 'obscureFilename()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.obscureFilename( 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png', file ),
			'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/<filename>/300px-<filename>.png', 'Filename correctly obscured' );

		file = new mw.Title( 'File:Hoag\'s_object.jpg' );

		assert.strictEqual( provider.obscureFilename( 'http://upload.wikimedia.org/wikipedia/commons/thumb/d/da/Hoag%27s_object.jpg/180px-Hoag%27s_object.jpg', file ),
			'http://upload.wikimedia.org/wikipedia/commons/thumb/d/da/<filename>/180px-<filename>', 'Filename with urlencoded character correctly obscured' );
	} );

	QUnit.test( 'restoreFilename()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		const file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.restoreFilename( 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/<filename>/300px-<filename>.png', file ),
			'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png', 'Filename correctly restored' );

	} );

	QUnit.test( 'canHaveLargerThumbnailThanOriginal()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.canHaveLargerThumbnailThanOriginal( file ), true, 'SVG can have a larger thumbnail than the original' );

		file = new mw.Title( 'File:Foo.jpg' );

		assert.strictEqual( provider.canHaveLargerThumbnailThanOriginal( file ), false, 'JPG can\'t have a larger thumbnail than the original' );

		file = new mw.Title( 'File:Foo.png' );

		assert.strictEqual( provider.canHaveLargerThumbnailThanOriginal( file ), false, 'PNG can\'t have a larger thumbnail than the original' );

		file = new mw.Title( 'File:Foo.jpeg' );

		assert.strictEqual( provider.canHaveLargerThumbnailThanOriginal( file ), false, 'JPEG can\'t have a larger thumbnail than the original' );

		file = new mw.Title( 'File:Foo.tiff' );

		assert.strictEqual( provider.canHaveLargerThumbnailThanOriginal( file ), false, 'TIFF can\'t have a larger thumbnail than the original' );

		file = new mw.Title( 'File:Foo.gif' );

		assert.strictEqual( provider.canHaveLargerThumbnailThanOriginal( file ), false, 'GIF can\'t have a larger thumbnail than the original' );
	} );

	QUnit.test( 'canBeDisplayedInBrowser()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.canBeDisplayedInBrowser( file ), false, 'SVG can\'t be displayed as-is in the browser' );

		file = new mw.Title( 'File:Foo.jpg' );

		assert.strictEqual( provider.canBeDisplayedInBrowser( file ), true, 'JPG can be displayed as-is in the browser' );

		file = new mw.Title( 'File:Foo.png' );

		assert.strictEqual( provider.canBeDisplayedInBrowser( file ), true, 'PNG can be displayed as-is in the browser' );

		file = new mw.Title( 'File:Foo.jpeg' );

		assert.strictEqual( provider.canBeDisplayedInBrowser( file ), true, 'JPEG can be displayed as-is in the browser' );

		file = new mw.Title( 'File:Foo.tiff' );

		assert.strictEqual( provider.canBeDisplayedInBrowser( file ), false, 'TIFF can\'t be displayed as-is in the browser' );

		file = new mw.Title( 'File:Foo.gif' );

		assert.strictEqual( provider.canBeDisplayedInBrowser( file ), true, 'GIF can be displayed as-is in the browser' );
	} );

	QUnit.test( 'guessWidth()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.guessWidth( file, 100, 1000 ), 100, 'Width correctly guessed for SVG thumbnail smaller than the original' );
		assert.strictEqual( provider.guessWidth( file, 2000, 1000 ), 2000, 'Width correctly guessed for SVG thumbnail bigger than the original' );

		file = new mw.Title( 'File:Copyleft.jpg' );

		assert.strictEqual( provider.guessWidth( file, 100, 1000 ), 100, 'Width correctly guessed for JPG thumbnail smaller than the original' );
		assert.strictEqual( provider.guessWidth( file, 2000, 1000 ), 1000, 'Width correctly guessed for JPG thumbnail bigger than the original' );
	} );

	QUnit.test( 'guessHeight()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.guessHeight( file, 100, 1000, 500 ), 50, 'Height correctly guessed for SVG thumbnail smaller than the original' );
		assert.strictEqual( provider.guessHeight( file, 2000, 1000, 500 ), 1000, 'Height correctly guessed for SVG thumbnail bigger than the original' );

		file = new mw.Title( 'File:Copyleft.jpg' );

		assert.strictEqual( provider.guessHeight( file, 100, 1000, 500 ), 50, 'Height correctly guessed for JPG thumbnail smaller than the original' );
		assert.strictEqual( provider.guessHeight( file, 2000, 1000, 500 ), 500, 'Height correctly guessed for JPG thumbnail bigger than the original' );
	} );

	QUnit.test( 'replaceSize()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );

		assert.strictEqual( provider.replaceSize( file, 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png', 220 ),
			'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/220px-Copyleft.svg.png', 'Incorrect size correctly replaced' );
		assert.strictEqual( provider.replaceSize( file, 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png', 300 ),
			'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png', 'Identical size correctly left the same' );
		assert.strictEqual( provider.replaceSize( file, 'http://upload.wikimedia.org/wikipedia/commons/8/8b/Copyleft.svg', 220 ),
			undefined, 'Returns undefined when it cannot handle the URL' );

		file = new mw.Title( 'File:Copyleft-300px.svg' );
		assert.strictEqual( provider.replaceSize( file, 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft-300px.svg/300px-Copyleft-300px.svg.png', 220 ),
			'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft-300px.svg/220px-Copyleft-300px.svg.png', 'Works with strange filename' );

		file = new mw.Title( 'File:Ranunculus_gmelinii_NRCS-2.tiff' );
		assert.strictEqual( provider.replaceSize( file, 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Ranunculus_gmelinii_NRCS-2.tiff/lossy-page1-428px-Ranunculus_gmelinii_NRCS-2.tiff.jpg', 220 ),
			'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Ranunculus_gmelinii_NRCS-2.tiff/lossy-page1-220px-Ranunculus_gmelinii_NRCS-2.tiff.jpg', 'Works with extra parameters' );
	} );

	QUnit.test( 'guessFullUrl()', function ( assert ) {
		const provider = new GuessedThumbnailInfo();
		let file = new mw.Title( 'File:Copyleft.svg' );
		let fullUrl = 'http://upload.wikimedia.org/wikipedia/commons/8/8b/Copyleft.svg';
		let sampleUrl = 'http://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Copyleft.svg/300px-Copyleft.svg.png';

		let result = provider.guessFullUrl( file, sampleUrl );

		assert.strictEqual( result, fullUrl, 'guessFullUrl returns correct full URL for SVG' );

		file = new mw.Title( 'File:அணில்-3-தென்னையின்_வளர்நிலை.jpg' );
		fullUrl = 'https://upload.wikimedia.org/wikipedia/commons/1/15/%E0%AE%85%E0%AE%A3%E0%AE%BF%E0%AE%B2%E0%AF%8D-3-%E0%AE%A4%E0%AF%86%E0%AE%A9%E0%AF%8D%E0%AE%A9%E0%AF%88%E0%AE%AF%E0%AE%BF%E0%AE%A9%E0%AF%8D_%E0%AE%B5%E0%AE%B3%E0%AE%B0%E0%AF%8D%E0%AE%A8%E0%AE%BF%E0%AE%B2%E0%AF%88.jpg';
		sampleUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/%E0%AE%85%E0%AE%A3%E0%AE%BF%E0%AE%B2%E0%AF%8D-3-%E0%AE%A4%E0%AF%86%E0%AE%A9%E0%AF%8D%E0%AE%A9%E0%AF%88%E0%AE%AF%E0%AE%BF%E0%AE%A9%E0%AF%8D_%E0%AE%B5%E0%AE%B3%E0%AE%B0%E0%AF%8D%E0%AE%A8%E0%AE%BF%E0%AE%B2%E0%AF%88.jpg/800px-%E0%AE%85%E0%AE%A3%E0%AE%BF%E0%AE%B2%E0%AF%8D-3-%E0%AE%A4%E0%AF%86%E0%AE%A9%E0%AF%8D%E0%AE%A9%E0%AF%88%E0%AE%AF%E0%AE%BF%E0%AE%A9%E0%AF%8D_%E0%AE%B5%E0%AE%B3%E0%AE%B0%E0%AF%8D%E0%AE%A8%E0%AE%BF%E0%AE%B2%E0%AF%88.jpg';

		result = provider.guessFullUrl( file, sampleUrl );

		assert.strictEqual( result, fullUrl, 'guessFullUrl returns correct full URL for JPG with unicode name' );

		file = new mw.Title( 'File:அணில்-3-தென்னையின்_வளர்நிலை.jpg' );
		sampleUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/அணில்-3-தென்னையின்_வளர்நிலை.jpg/800px-அணில்-3-தென்னையின்_வளர்நிலை.jpg';

		result = provider.guessFullUrl( file, sampleUrl );

		assert.strictEqual( result, undefined, 'guessFullUrl bails out when URL encoding is not as expected' );
	} );
}() );
