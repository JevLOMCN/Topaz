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

const { Utils } = require( 'mmv.ui.ondemandshareddependencies' );

( function () {
	QUnit.module( 'mmv.ui.reuse.utils', QUnit.newMwEnvironment() );

	QUnit.test( 'Sense test, object creation and UI construction', function ( assert ) {
		const utils = new Utils();

		assert.true( utils instanceof Utils, 'ReuseUtils object is created.' );
	} );

	QUnit.test( 'createPulldownMenu():', function ( assert ) {
		const utils = new Utils();
		const menuItems = [ 'original', 'small', 'medium', 'large' ];
		const def = 'large';
		const menu = utils.createPulldownMenu(
			menuItems,
			[ 'mw-mmv-download-size' ],
			def
		);
		const options = menu.getMenu().getItems();

		assert.strictEqual( options.length, 4, 'Menu has correct number of items.' );

		for ( let i = 0; i < menuItems.length; i++ ) {
			const data = options[ i ].getData();

			assert.strictEqual( data.name, menuItems[ i ], 'Correct item name on the list.' );
			assert.strictEqual( data.height, null, 'Correct item height on the list.' );
			assert.strictEqual( data.width, null, 'Correct item width on the list.' );
		}

		assert.strictEqual( menu.getMenu().findSelectedItem(), options[ 3 ], 'Default set correctly.' );
	} );

	QUnit.test( 'updateMenuOptions():', function ( assert ) {
		const utils = new Utils();
		const menu = utils.createPulldownMenu(
			[ 'original', 'small', 'medium', 'large' ],
			[ 'mw-mmv-download-size' ],
			'original'
		);
		const options = menu.getMenu().getItems();
		const width = 700;
		const height = 500;
		const sizes = utils.getPossibleImageSizesForHtml( width, height );
		const oldMessage = mw.message;

		mw.message = function ( messageKey ) {
			assert.true( /^multimediaviewer-(small|medium|original|embed-dimensions)/.test( messageKey ), 'messageKey passed correctly.' );

			return { text: function () {} };
		};

		utils.updateMenuOptions( sizes, options );

		mw.message = oldMessage;
	} );

	QUnit.test( 'getPossibleImageSizesForHtml()', function ( assert ) {
		const utils = new Utils();
		const exampleSizes = [
			{
				test: 'Extra large wide image',
				width: 6000, height: 4000,
				expected: {
					small: { width: 640, height: 427 },
					medium: { width: 1080, height: 720 },
					large: { width: 1620, height: 1080 },
					xl: { width: 3240, height: 2160 },
					original: { width: 6000, height: 4000 }
				}
			},

			{
				test: 'Big wide image',
				width: 2048, height: 1536,
				expected: {
					small: { width: 640, height: 480 },
					medium: { width: 960, height: 720 },
					large: { width: 1440, height: 1080 },
					original: { width: 2048, height: 1536 }
				}
			},

			{
				test: 'Big tall image',
				width: 201, height: 1536,
				expected: {
					small: { width: 63, height: 480 },
					medium: { width: 94, height: 720 },
					large: { width: 141, height: 1080 },
					original: { width: 201, height: 1536 }
				}
			},

			{
				test: 'Very small image',
				width: 15, height: 20,
				expected: {
					original: { width: 15, height: 20 }
				}
			}
		];
		for ( let i = 0; i < exampleSizes.length; i++ ) {
			const cursize = exampleSizes[ i ];
			const opts = utils.getPossibleImageSizesForHtml( cursize.width, cursize.height );
			assert.deepEqual( opts, cursize.expected, 'Size calculation for "' + cursize.test + '" gives expected results' );
		}
	} );

}() );
