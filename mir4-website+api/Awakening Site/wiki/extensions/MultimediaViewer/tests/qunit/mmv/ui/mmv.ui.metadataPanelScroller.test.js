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

const { createLocalStorage, getFakeLocalStorage, getUnsupportedLocalStorage } = require( '../mmv.testhelpers.js' );
const { MetadataPanelScroller } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.ui.metadataPanelScroller', QUnit.newMwEnvironment( {
		beforeEach: function () {
			this.clock = this.sandbox.useFakeTimers();
		}
	} ) );

	QUnit.test( 'empty()', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		const localStorage = getFakeLocalStorage();
		const scroller = new MetadataPanelScroller( $qf, $( '<div>' ).appendTo( $qf ), localStorage );

		scroller.empty();
		assert.strictEqual( scroller.$container.hasClass( 'invite' ), false, 'We successfully reset the invite' );
	} );

	QUnit.test( 'Metadata div is only animated once', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		let displayCount = null; // pretend it doesn't exist at first
		const localStorage = createLocalStorage( {
			// We simulate localStorage to avoid test side-effects
			getItem: function () {
				return displayCount;
			},
			setItem: function ( _, val ) {
				displayCount = val;
			},
			removeItem: function () {
				displayCount = null;
			}
		} );
		const scroller = new MetadataPanelScroller( $qf, $( '<div>' ).appendTo( $qf ), localStorage );

		scroller.attach();

		scroller.animateMetadataOnce();

		assert.true( scroller.hasAnimatedMetadata,
			'The first call to animateMetadataOnce set hasAnimatedMetadata to true' );
		assert.true( $qf.hasClass( 'invite' ),
			'The first call to animateMetadataOnce led to an animation' );

		$qf.removeClass( 'invite' );

		scroller.animateMetadataOnce();

		assert.strictEqual( scroller.hasAnimatedMetadata, true, 'The second call to animateMetadataOnce did not change the value of hasAnimatedMetadata' );
		assert.strictEqual( $qf.hasClass( 'invite' ), false,
			'The second call to animateMetadataOnce did not lead to an animation' );

		scroller.unattach();

		scroller.attach();

		scroller.animateMetadataOnce();
		assert.true( $qf.hasClass( 'invite' ),
			'After closing and opening the viewer, the panel is animated again' );

		scroller.unattach();
	} );

	QUnit.test( 'No localStorage', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		const localStorage = getUnsupportedLocalStorage();
		const scroller = new MetadataPanelScroller( $qf, $( '<div>' ).appendTo( $qf ), localStorage );

		this.sandbox.stub( $.fn, 'scrollTop', () => 10 );

		scroller.scroll();

		assert.strictEqual( scroller.hasOpenedMetadata, true, 'We store hasOpenedMetadata flag for the session' );
	} );

	QUnit.test( 'localStorage is full', function ( assert ) {
		const $qf = $( '#qunit-fixture' );
		const localStorage = createLocalStorage( {
			getItem: this.sandbox.stub().returns( null ),
			setItem: this.sandbox.stub().throwsException( 'I am full' ),
			removeItem: this.sandbox.stub()
		} );
		const scroller = new MetadataPanelScroller( $qf, $( '<div>' ).appendTo( $qf ), localStorage );

		this.sandbox.stub( $.fn, 'scrollTop', () => 10 );

		scroller.attach();

		scroller.scroll();

		assert.strictEqual( scroller.hasOpenedMetadata, true, 'We store hasOpenedMetadata flag for the session' );

		scroller.scroll();

		assert.true( localStorage.store.setItem.calledOnce, 'localStorage only written once' );

		scroller.unattach();
	} );

	/**
	 * We need to set up a proxy on the jQuery scrollTop function and the jQuery.scrollTo plugin,
	 * that will let us pretend that the document really scrolled and that will return values
	 * as if the scroll happened.
	 *
	 * @param {sinon.sandbox} sandbox
	 * @param {MetadataPanelScroller} scroller
	 */
	function stubScrollFunctions( sandbox, scroller ) {
		let memorizedScrollTop = 0;

		sandbox.stub( $.fn, 'scrollTop', function ( scrollTop ) {
			if ( scrollTop !== undefined ) {
				memorizedScrollTop = scrollTop;
				scroller.scroll();
				return this;
			} else {
				return memorizedScrollTop;
			}
		} );
		sandbox.stub( $.fn, 'animate', function ( props ) {
			if ( 'scrollTop' in props ) {
				memorizedScrollTop = props.scrollTop;
				scroller.scroll();
			}
			return this;
		} );
	}

	QUnit.test( 'Metadata scrolling', function ( assert ) {
		const $window = $( window );
		const $qf = $( '#qunit-fixture' );
		const $container = $( '<div>' ).css( 'height', 100 ).appendTo( $qf );
		const $aboveFold = $( '<div>' ).css( 'height', 50 ).appendTo( $container );
		const fakeLocalStorage = createLocalStorage( {
			getItem: this.sandbox.stub().returns( null ),
			setItem: function () {},
			removeItem: function () {}
		} );
		const scroller = new MetadataPanelScroller( $container, $aboveFold, fakeLocalStorage );
		const keydown = $.Event( 'keydown' );

		stubScrollFunctions( this.sandbox, scroller );

		this.sandbox.stub( fakeLocalStorage.store, 'setItem' );

		// First phase of the test: up and down arrows

		scroller.hasAnimatedMetadata = false;

		scroller.attach();

		assert.strictEqual( $window.scrollTop(), 0, 'scrollTop should be set to 0' );

		assert.strictEqual( fakeLocalStorage.store.setItem.called, false, 'The metadata hasn\'t been open yet, no entry in localStorage' );

		keydown.which = 38; // Up arrow
		scroller.keydown( keydown );

		assert.strictEqual( fakeLocalStorage.store.setItem.calledWithExactly( 'mmv.hasOpenedMetadata', '1' ), true, 'localStorage knows that the metadata has been open' );

		keydown.which = 40; // Down arrow
		scroller.keydown( keydown );

		assert.strictEqual( $window.scrollTop(), 0,
			'scrollTop should be set to 0 after pressing down arrow' );

		// Unattach lightbox from document
		scroller.unattach();

		// Second phase of the test: scroll memory

		scroller.attach();

		// To make sure that the details are out of view, the lightbox is supposed to scroll to the top when open
		assert.strictEqual( $window.scrollTop(), 0, 'Page scrollTop should be set to 0' );

		// Scroll down to check that the scrollTop memory doesn't affect prev/next (bug 59861)
		$window.scrollTop( 20 );
		this.clock.tick( 100 );

		// This extra attach() call simulates the effect of prev/next seen in bug 59861
		scroller.attach();

		// The lightbox was already open at this point, the scrollTop should be left untouched
		assert.strictEqual( $window.scrollTop(), 20, 'Page scrollTop should be set to 20' );

		scroller.unattach();
	} );
}() );
