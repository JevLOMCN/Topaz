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

const { Tab } = require( 'mmv.ui.reuse.shareembed' );

( function () {
	const $fixture = $( '#qunit-fixture' );

	function makeReuseTab() {
		return new Tab( $( '<div>' ).appendTo( $fixture ), $fixture );
	}

	QUnit.module( 'mmv.ui.reuse.Tab', QUnit.newMwEnvironment() );

	QUnit.test( 'Object creation, UI construction and basic funtionality', function ( assert ) {
		const reuseTab = makeReuseTab();

		assert.true( reuseTab instanceof Tab, 'Reuse UI element is created.' );
		assert.strictEqual( reuseTab.$pane.length, 1, 'Pane created.' );

		assert.strictEqual( reuseTab.$pane.hasClass( 'active' ), false, 'Tab is not active.' );

		reuseTab.show();

		assert.strictEqual( reuseTab.$pane.hasClass( 'active' ), true, 'Tab is active.' );

		reuseTab.hide();

		assert.strictEqual( reuseTab.$pane.hasClass( 'active' ), false, 'Tab is not active.' );
	} );
}() );
