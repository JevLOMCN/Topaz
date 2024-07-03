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

const { ImageInfo } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.provider.ImageInfo', QUnit.newMwEnvironment( {
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

	QUnit.test( 'ImageInfo constructor sense check', function ( assert ) {
		const api = { get: function () {} };
		const imageInfoProvider = new ImageInfo( api );

		assert.true( imageInfoProvider instanceof ImageInfo );
	} );

	QUnit.test( 'ImageInfo get test', function ( assert ) {
		let apiCallCount = 0;
		const api = { get: function () {
			apiCallCount++;
			return $.Deferred().resolve( {
				query: {
					pages: [
						{
							ns: 6,
							title: 'File:Stuff.jpg',
							missing: true,
							imagerepository: 'shared',
							imageinfo: [
								{
									timestamp: '2013-08-25T14:41:02Z',
									userid: '3053121',
									size: 346684,
									width: 720,
									height: 1412,
									comment: 'User created page with UploadWizard',
									url: 'https://upload.wikimedia.org/wikipedia/commons/1/19/Stuff.jpg',
									descriptionurl: 'https://commons.wikimedia.org/wiki/File:Stuff.jpg',
									sha1: 'a1ba23d471f4dad208b71c143e2e105a0e3032db',
									metadata: [],
									extmetadata: {
										ObjectName: {
											value: 'Some stuff',
											source: 'commons-templates'
										},
										License: {
											value: 'cc0',
											source: 'commons-templates',
											hidden: ''
										},
										LicenseShortName: {
											value: 'CC0',
											source: 'commons-templates'
										},
										UsageTerms: {
											value: 'Creative Commons Public Domain Dedication',
											source: 'commons-templates'
										},
										LicenseUrl: {
											value: 'http://creativecommons.org/publicdomain/zero/1.0/',
											source: 'commons-templates'
										},
										GPSLatitude: {
											value: '90.000000',
											source: 'commons-desc-page'
										},
										GPSLongitude: {
											value: ' 180.000000',
											source: 'commons-desc-page'
										},
										ImageDescription: {
											value: 'Wikis stuff',
											source: 'commons-desc-page'
										},
										DateTimeOriginal: {
											value: '<time class="dtstart" datetime="2009-02-18">18 February 2009</time>\u00a0(according to <a href="//en.wikipedia.org/wiki/Exchangeable_image_file_format" class="extiw" title="en:Exchangeable image file format">EXIF</a> data)',
											source: 'commons-desc-page'
										},
										DateTime: {
											value: '2013-08-25T14:41:02Z',
											source: 'commons-desc-page'
										},
										Credit: {
											value: 'Wikipedia',
											source: 'commons-desc-page',
											hidden: ''
										},
										Artist: {
											value: 'John Smith',
											source: 'commons-desc-page'
										},
										AuthorCount: {
											value: '2',
											source: 'commons-desc-page'
										},
										Attribution: {
											value: 'By John Smith',
											source: 'commons-desc-page'
										},
										Permission: {
											value: 'Do not use. Ever.',
											source: 'commons-desc-page'
										},
										AttributionRequired: {
											value: 'no',
											source: 'commons-desc-page'
										},
										NonFree: {
											value: 'yes',
											source: 'commons-desc-page'
										},
										Restrictions: {
											value: 'trademarked|insignia',
											source: 'commons-desc-page'
										},
										DeletionReason: {
											value: 'copyvio',
											source: 'commons-desc-page'
										}
									},
									mime: 'image/jpeg',
									mediatype: 'BITMAP'
								}
							]
						}
					]
				}
			} );
		} };
		const file = new mw.Title( 'File:Stuff.jpg' );
		const imageInfoProvider = new ImageInfo( api );

		return imageInfoProvider.get( file ).then( function ( image ) {
			assert.strictEqual( image.title.getPrefixedDb(), 'File:Stuff.jpg', 'title is set correctly' );
			assert.strictEqual( image.name, 'Some stuff', 'name is set correctly' );
			assert.strictEqual( image.size, 346684, 'size is set correctly' );
			assert.strictEqual( image.width, 720, 'width is set correctly' );
			assert.strictEqual( image.height, 1412, 'height is set correctly' );
			assert.strictEqual( image.mimeType, 'image/jpeg', 'mimeType is set correctly' );
			assert.strictEqual( image.url, 'https://upload.wikimedia.org/wikipedia/commons/1/19/Stuff.jpg', 'url is set correctly' );
			assert.strictEqual( image.descriptionUrl, 'https://commons.wikimedia.org/wiki/File:Stuff.jpg', 'descriptionUrl is set correctly' );
			assert.strictEqual( image.repo, 'shared', 'repo is set correctly' );
			assert.strictEqual( image.uploadDateTime, '2013-08-25T14:41:02Z', 'uploadDateTime is set correctly' );
			assert.strictEqual( image.anonymizedUploadDateTime, '20130825000000', 'anonymizedUploadDateTime is set correctly' );
			assert.strictEqual( image.creationDateTime, '2009-02-18', 'creationDateTime is set correctly' );
			assert.strictEqual( image.description, 'Wikis stuff', 'description is set correctly' );
			assert.strictEqual( image.source, 'Wikipedia', 'source is set correctly' );
			assert.strictEqual( image.author, 'John Smith', 'author is set correctly' );
			assert.strictEqual( image.authorCount, 2, 'author count is set correctly' );
			assert.strictEqual( image.attribution, 'By John Smith', 'attribution is set correctly' );
			assert.strictEqual( image.license.shortName, 'CC0', 'license short name is set correctly' );
			assert.strictEqual( image.license.internalName, 'cc0', 'license internal name is set correctly' );
			assert.strictEqual( image.license.longName, 'Creative Commons Public Domain Dedication', 'license long name is set correctly' );
			assert.strictEqual( image.license.deedUrl, 'http://creativecommons.org/publicdomain/zero/1.0/', 'license URL is set correctly' );
			assert.strictEqual( image.license.attributionRequired, false, 'Attribution required flag is honored' );
			assert.strictEqual( image.license.nonFree, true, 'Non-free flag is honored' );
			assert.strictEqual( image.permission, 'Do not use. Ever.', 'permission is set correctly' );
			assert.strictEqual( image.deletionReason, 'copyvio', 'permission is set correctly' );
			assert.strictEqual( image.latitude, 90, 'latitude is set correctly' );
			assert.strictEqual( image.longitude, 180, 'longitude is set correctly' );
			assert.deepEqual( image.restrictions, [ 'trademarked', 'insignia' ], 'restrictions is set correctly' );
		} ).then( function () {
			// call the data provider a second time to check caching
			return imageInfoProvider.get( file );
		} ).then( function () {
			assert.strictEqual( apiCallCount, 1 );
		} );
	} );

	QUnit.test( 'ImageInfo fail test', function ( assert ) {
		const api = { get: function () {
			return $.Deferred().resolve( {} );
		} };
		const file = new mw.Title( 'File:Stuff.jpg' );
		const done = assert.async();
		const imageInfoProvider = new ImageInfo( api );

		imageInfoProvider.get( file ).fail( function () {
			assert.true( true, 'promise rejected when no data is returned' );
			done();
		} );
	} );

	QUnit.test( 'ImageInfo fail test 2', function ( assert ) {
		const api = { get: function () {
			return $.Deferred().resolve( {
				query: {
					pages: [
						{
							title: 'File:Stuff.jpg'
						}
					]
				}
			} );
		} };
		const file = new mw.Title( 'File:Stuff.jpg' );
		const done = assert.async();
		const imageInfoProvider = new ImageInfo( api );

		imageInfoProvider.get( file ).fail( function () {
			assert.true( true, 'promise rejected when imageinfo is missing' );
			done();
		} );
	} );

	QUnit.test( 'ImageInfo missing page test', function ( assert ) {
		const api = { get: function () {
			return $.Deferred().resolve( {
				query: {
					pages: [
						{
							title: 'File:Stuff.jpg',
							missing: true,
							imagerepository: ''
						}
					]
				}
			} );
		} };
		const file = new mw.Title( 'File:Stuff.jpg' );
		const done = assert.async();
		const imageInfoProvider = new ImageInfo( api );

		imageInfoProvider.get( file ).fail( function ( errorMessage ) {
			assert.strictEqual( errorMessage, 'file does not exist: File:Stuff.jpg',
				'error message is set correctly for missing file' );
			done();
		} );
	} );
}() );
