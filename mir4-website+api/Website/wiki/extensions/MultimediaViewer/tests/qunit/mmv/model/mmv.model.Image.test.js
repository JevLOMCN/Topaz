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

const { ImageModel, License } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.model.Image', QUnit.newMwEnvironment() );

	QUnit.test( 'Image model constructor sense check', function ( assert ) {
		const title = mw.Title.newFromText( 'File:Foobar.jpg' );
		const name = 'Foo bar';
		const size = 100;
		const width = 10;
		const height = 15;
		const mime = 'image/jpeg';
		const url = 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg';
		const pageID = 42;
		const descurl = 'https://commons.wikimedia.org/wiki/File:Foobar.jpg';
		const descShortUrl = '';
		const repo = 'wikimediacommons';
		const datetime = '2011-07-04T23:31:14Z';
		const anondatetime = '20110704000000';
		const origdatetime = '2010-07-04T23:31:14Z';
		const description = 'This is a test file.';
		const source = 'WMF';
		const author = 'Ryan Kaldari';
		const authorCount = 1;
		const permission = 'only use for good, not evil';
		const deletionReason = 'poor quality';
		const license = new License( 'cc0' );
		const attribution = 'Created by my cats on a winter morning';
		const latitude = 39.12381283;
		const longitude = 100.983829;
		const restrictions = [ 'trademarked' ];
		const imageData = new ImageModel(
			title, name, size, width, height, mime, url,
			descurl, descShortUrl, pageID, repo, datetime, anondatetime, origdatetime,
			description, source, author, authorCount, license, permission, attribution,
			deletionReason, latitude, longitude, restrictions );

		assert.strictEqual( imageData.title, title, 'Title is set correctly' );
		assert.strictEqual( imageData.name, name, 'Name is set correctly' );
		assert.strictEqual( imageData.size, size, 'Size is set correctly' );
		assert.strictEqual( imageData.width, width, 'Width is set correctly' );
		assert.strictEqual( imageData.height, height, 'Height is set correctly' );
		assert.strictEqual( imageData.mimeType, mime, 'MIME type is set correctly' );
		assert.strictEqual( imageData.url, url, 'URL for original image is set correctly' );
		assert.strictEqual( imageData.descriptionUrl, descurl, 'URL for image description page is set correctly' );
		assert.strictEqual( imageData.pageID, pageID, 'Page ID of image description is set correctly' );
		assert.strictEqual( imageData.repo, repo, 'Repository name is set correctly' );
		assert.strictEqual( imageData.uploadDateTime, datetime, 'Date and time of last upload is set correctly' );
		assert.strictEqual( imageData.anonymizedUploadDateTime, anondatetime, 'Anonymized date and time of last upload is set correctly' );
		assert.strictEqual( imageData.creationDateTime, origdatetime, 'Date and time of original upload is set correctly' );
		assert.strictEqual( imageData.description, description, 'Description is set correctly' );
		assert.strictEqual( imageData.source, source, 'Source is set correctly' );
		assert.strictEqual( imageData.author, author, 'Author is set correctly' );
		assert.strictEqual( imageData.authorCount, authorCount, 'Author is set correctly' );
		assert.strictEqual( imageData.license, license, 'License is set correctly' );
		assert.strictEqual( imageData.permission, permission, 'Permission is set correctly' );
		assert.strictEqual( imageData.attribution, attribution, 'Attribution is set correctly' );
		assert.strictEqual( imageData.deletionReason, deletionReason, 'Deletion reason is set correctly' );
		assert.strictEqual( imageData.latitude, latitude, 'Latitude is set correctly' );
		assert.strictEqual( imageData.longitude, longitude, 'Longitude is set correctly' );
		assert.deepEqual( imageData.restrictions, restrictions, 'Restrictions is set correctly' );
		assert.true( $.isPlainObject( imageData.thumbUrls ), 'Thumb URL cache is set up properly' );
	} );

	QUnit.test( 'hasCoords()', function ( assert ) {
		const firstImageData = new ImageModel(
			mw.Title.newFromText( 'File:Foobar.pdf.jpg' ), 'Foo bar',
			10, 10, 10, 'image/jpeg', 'http://example.org', 'http://example.com', 42,
			'example', 'tester', '2013-11-10', '20131110', '2013-11-09', 'Blah blah blah',
			'A person', 'Another person', 1, 'CC-BY-SA-3.0', 'Permitted', 'My cat'
		);
		const secondImageData = new ImageModel(
			mw.Title.newFromText( 'File:Foobar.pdf.jpg' ), 'Foo bar',
			10, 10, 10, 'image/jpeg', 'http://example.org', 'http://example.com', 42,
			'example', 'tester', '2013-11-10', '20131110', '2013-11-09', 'Blah blah blah',
			'A person', 'Another person', 1, 'CC-BY-SA-3.0', 'Permitted', 'My cat',
			undefined, '39.91820938', '78.09812938'
		);

		assert.strictEqual( firstImageData.hasCoords(), false, 'No coordinates present means hasCoords returns false.' );
		assert.strictEqual( secondImageData.hasCoords(), true, 'Coordinates present means hasCoords returns true.' );
	} );

	QUnit.test( 'parseExtmeta()', function ( assert ) {
		const stringData = { value: 'foo' };
		const plaintextData = { value: 'fo<b>o</b>' };
		const integerData = { value: 3 };
		const integerStringData = { value: '3' };
		const zeroPrefixedIntegerStringData = { value: '03' };
		const floatData = { value: 1.23 };
		const floatStringData = { value: '1.23' };
		const booleanData = { value: 'yes' };
		const wrongBooleanData = { value: 'blah' };
		const listDataEmpty = { value: '' };
		const listDataSingle = { value: 'foo' };
		const listDataMultiple = { value: 'foo|bar|baz' };
		const missingData = undefined;

		assert.strictEqual( ImageModel.parseExtmeta( stringData, 'string' ), 'foo',
			'Extmeta string parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( plaintextData, 'plaintext' ), 'foo',
			'Extmeta plaintext parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( floatData, 'float' ), 1.23,
			'Extmeta float parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( floatStringData, 'float' ), 1.23,
			'Extmeta float string parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( booleanData, 'boolean' ), true,
			'Extmeta boolean string parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( wrongBooleanData, 'boolean' ), undefined,
			'Extmeta boolean string with error ignored.' );
		assert.strictEqual( ImageModel.parseExtmeta( integerData, 'integer' ), 3,
			'Extmeta integer parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( integerStringData, 'integer' ), 3,
			'Extmeta integer string parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( zeroPrefixedIntegerStringData, 'integer' ), 3,
			'Extmeta zero-prefixed integer string parsed correctly.' );
		assert.deepEqual( ImageModel.parseExtmeta( listDataEmpty, 'list' ), [],
			'Extmeta empty list parsed correctly.' );
		assert.deepEqual( ImageModel.parseExtmeta( listDataSingle, 'list' ), [ 'foo' ],
			'Extmeta list with single element parsed correctly.' );
		assert.deepEqual( ImageModel.parseExtmeta( listDataMultiple, 'list' ), [ 'foo', 'bar', 'baz' ],
			'Extmeta list with multiple elements parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( missingData, 'string' ), undefined,
			'Extmeta missing data parsed correctly.' );

		assert.strictEqual( ImageModel.parseExtmeta( { value: '1960-03-14' }, 'datetime' ), '1960-03-14',
			'Extmeta date is parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( { value: '1960' }, 'datetime' ), '1960',
			'Extmeta year is parsed correctly.' );
		assert.strictEqual( ImageModel.parseExtmeta( { value: '1926<div style="display: none;">date QS:P571,+1926-00-00T00:00:00Z/9</div>' }, 'datetime' ), '1926',
			'Extmeta year is extracted from hidden div.' );

		assert.throws( function () {
			ImageModel.parseExtmeta( stringData, 'strong' );
		}, 'Exception is thrown on invalid argument' );
	} );

}() );
