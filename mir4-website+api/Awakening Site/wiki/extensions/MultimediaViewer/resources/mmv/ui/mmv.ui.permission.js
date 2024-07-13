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

const { HtmlUtils } = require( 'mmv.bootstrap' );
const UiElement = require( './mmv.ui.js' );
const MetadataPanelScroller = require( './mmv.ui.metadataPanelScroller.js' );

( function () {

	/**
	 * A box to display additional terms or remarks from the image author.
	 * (Typically comes from the Permission field of the {{Information}} template.)
	 * It has two states: when closed, it just shows some text, when open, it shows the HTML
	 * block supplied by the author in its full beauty.
	 */
	class Permission extends UiElement {
		/**
		 * @param {jQuery} $container
		 * @param {MetadataPanelScroller} scroller
		 */
		constructor( $container, scroller ) {
			super( $container );

			/** @property {HtmlUtils} htmlUtils - */
			this.htmlUtils = new HtmlUtils();

			/**
			 * Contains everything else.
			 *
			 * @property {jQuery}
			 */
			this.$box = $( '<div>' )
				.addClass( 'mw-mmv-permission-box mw-mmv-info-box empty' )
				.appendTo( this.$container );

			/**
			 * Box title
			 *
			 * @property {jQuery}
			 */
			this.$title = $( '<h3>' )
				.text( mw.message( 'multimediaviewer-permission-title' ).text() )
				.appendTo( this.$box );

			/**
			 * Plain-text version of the author's message
			 * This is just the text parsed out from the original markup, it might not make much sense
			 * (e.g. if the original is a HTML table)
			 *
			 * @property {jQuery}
			 */
			this.$text = $( '<div>' )
				.addClass( 'mw-mmv-permission-text' )
				.appendTo( this.$box )
				.on( 'click', '.mw-mmv-permission-text-viewmore', ( e ) => {
					e.preventDefault();
					this.grow();
					this.scroller.toggle( 'up' );
				} );

			/**
			 * A helper element to fade off text
			 *
			 * @property {jQuery}
			 */
			this.$fader = $( '<div>' )
				.addClass( 'mw-mmv-permission-text-fader' )
				.append(
					$( '<a>' )
						.addClass( 'mw-mmv-permission-text-viewmore' )
						.prop( 'href', '#' )
						.text( mw.message( 'multimediaviewer-permission-viewmore' ).text() )
				);

			/**
			 * Original (HTML) version of the author's message
			 * This can be scary sometimes (huge tables, black text on dark purple background etc).
			 *
			 * @property {jQuery}
			 */
			this.$html = $( '<div>' )
				.addClass( 'mw-mmv-permission-html' )
				.appendTo( this.$box );

			/**
			 * "Close" button (does not actually close the box, just makes it smaller).
			 *
			 * @property {jQuery}
			 */
			this.$close = $( '<button>' )
				.addClass( 'mw-mmv-permission-close' )
				.on( 'click', () => {
					this.shrink();
				} )
				.appendTo( this.$box );

			/**
			 * Panel scroller from the metadata panel object.
			 *
			 * @property {MetadataPanelScroller}
			 */
			this.scroller = scroller;
		}

		/**
		 * Clear everything
		 */
		empty() {
			this.$box.addClass( 'empty' );
			this.$text.empty();
			this.$html.empty();
		}

		/**
		 * Set permission text/html
		 *
		 * @param {string} permission the text or HTML code written by the image author
		 */
		set( permission ) {
			this.$box.removeClass( 'empty' );

			this.$text.html( this.htmlUtils.htmlToTextWithLinks( permission ) );
			this.$text.append( this.$fader );

			this.$html.html( permission );
		}

		/**
		 * @event Permission#mmv-permission-grow
		 */

		/**
		 * Enlarge the box, show HTML instead of text.
		 *
		 * @fires Permission#mmv-permission-grow
		 */
		grow() {
			this.$box.addClass( 'full-size' );
			this.$container.trigger( 'mmv-permission-grow' );
		}

		/**
		 * @event Permission#mmv-permission-shrink
		 */

		/**
		 * Limit the size of the box, show text only.
		 *
		 * @fires Permission#mmv-permission-shrink
		 */
		shrink() {
			this.$box.removeClass( 'full-size' );
			this.$container.trigger( 'mmv-permission-shrink' );
		}

		/**
		 * Returns whether the box is full-size.
		 *
		 * @return {boolean}
		 */
		isFullSize() {
			return this.$box.hasClass( 'full-size' );
		}
	}

	module.exports = Permission;
}() );
