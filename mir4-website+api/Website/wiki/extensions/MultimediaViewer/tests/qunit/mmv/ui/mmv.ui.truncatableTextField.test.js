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

const { TruncatableTextField } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.ui.TruncatableTextField', QUnit.newMwEnvironment() );

	/**
	 * Create a textfield that can contain exactly width x height characters
	 *
	 * @param {number} width
	 * @param {number} height
	 * @param {jQuery} $qf fixture element
	 * @param {Object} sandbox sinon instance
	 * @return {TruncatableTextField}
	 */
	function getField( width, height, $qf, sandbox ) {
		const $container = $( '<div>' ).appendTo( $qf );
		const $element = $( '<span>' );
		const ttf = new TruncatableTextField( $container, $element, {} );

		ttf.htmlUtils.htmlToTextWithLinks = sandbox.stub().returnsArg( 0 );

		$container.css( {
			fontFamily: 'monospace',
			lineHeight: 1,
			width: width + 'ch',
			height: height + 'em'
		} );

		return ttf;
	}

	QUnit.test( 'Normal constructor', function ( assert ) {
		const $container = $( '#qunit-fixture' );
		const $element = $( '<div>' ).appendTo( $container ).text( 'This is a unique string.' );
		const ttf = new TruncatableTextField( $container, $element );

		assert.strictEqual( ttf.$element.text(), 'This is a unique string.', 'The constructor set the element to the right thing.' );
		assert.strictEqual( ttf.$element.closest( '#qunit-fixture' ).length, 1, 'The constructor put the element into the container.' );
	} );

	QUnit.test( 'Set method', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		const ttf = getField( 3, 2, $qf, this.sandbox );

		ttf.shrink = this.sandbox.stub();
		ttf.set( 'abc' );
		assert.strictEqual( ttf.$element.text(), 'abc', 'Text is set accurately.' );
	} );
}() );
