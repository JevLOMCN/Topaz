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

const { ProgressBar } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.ui.ProgressBar', QUnit.newMwEnvironment() );

	QUnit.test( 'Constructor sense check', function ( assert ) {
		const progressBar = new ProgressBar( $( '<div>' ) );
		assert.true( progressBar instanceof ProgressBar, 'ProgressBar created sccessfully' );
		assert.strictEqual( progressBar.$progress.hasClass( 'empty' ), true, 'ProgressBar starts empty' );
	} );

	QUnit.test( 'animateTo()', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		const $div = $( '<div>' ).css( { width: 250, position: 'relative' } ).appendTo( $qf );
		const progress = new ProgressBar( $div );

		assert.strictEqual( progress.$progress.hasClass( 'empty' ), true, 'Progress bar is hidden' );
		assert.strictEqual( progress.$percent.width(), 0, 'Progress bar\'s indicator is at 0' );

		this.sandbox.stub( $.fn, 'animate', function ( target ) {
			$( this ).css( target );
			assert.strictEqual( target.width, '50%', 'Animation should go to 50%' );
		} );
		progress.animateTo( 50 );
		assert.strictEqual( progress.$progress.hasClass( 'empty' ), false, 'Progress bar is visible' );

		assert.strictEqual( progress.$percent.width(), 125, 'Progress bar\'s indicator is at half' );

		$.fn.animate.restore();
		this.sandbox.stub( $.fn, 'animate', function ( target, duration, transition, callback ) {
			$( this ).css( target );

			assert.strictEqual( target.width, '100%', 'Animation should go to 100%' );

			if ( callback !== undefined ) {
				callback();
			}
		} );
		progress.animateTo( 100 );
		assert.strictEqual( progress.$progress.hasClass( 'empty' ), true, 'Progress bar is hidden' );
		assert.strictEqual( progress.$percent.width(), 0, 'Progress bar\'s indicator is at 0' );
	} );

	QUnit.test( 'jumpTo()/hide()', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		const $div = $( '<div>' ).css( { width: 250, position: 'relative' } ).appendTo( $qf );
		const progress = new ProgressBar( $div );

		assert.strictEqual( progress.$progress.hasClass( 'empty' ), true, 'Progress bar is hidden' );
		assert.strictEqual( progress.$percent.width(), 0, 'Progress bar\'s indicator is at 0' );

		progress.jumpTo( 50 );

		assert.strictEqual( progress.$progress.hasClass( 'empty' ), false, 'Progress bar is visible' );
		assert.strictEqual( progress.$percent.width(), 125, 'Progress bar\'s indicator is at half' );

		progress.hide();

		assert.strictEqual( progress.$progress.hasClass( 'empty' ), true, 'Progress bar is hidden' );
		assert.strictEqual( progress.$percent.width(), 0, 'Progress bar\'s indicator is at 0' );
	} );
}() );
