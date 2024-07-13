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

const { CanvasButtons } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.ui.CanvasButtons', QUnit.newMwEnvironment() );

	QUnit.test( 'Prev/Next', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		const buttons = new CanvasButtons( $qf, $( '<div>' ), $( '<div>' ) );

		buttons.on( 'next', function () {
			assert.true( true, 'Switched to next image' );
		} );

		buttons.on( 'prev', function () {
			assert.true( true, 'Switched to prev image' );
		} );

		buttons.$next.trigger( 'click' );
		buttons.$prev.trigger( 'click' );
	} );
}() );
