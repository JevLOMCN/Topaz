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

const UiElement = require( './mmv.ui.js' );

( function () {

	/**
	 * Represents the buttons which are displayed over the image - next, previous, close
	 * and fullscreen.
	 */
	class CanvasButtons extends UiElement {
		/**
		 * @param {jQuery} $container The parent element we should put the buttons into.
		 * @param {jQuery} $closeButton The close button element from the parent class.
		 * @param {jQuery} $fullscreenButton The fullscreen button from the parent class.
		 * @fires MultimediaViewer#mmv-close
		 */
		constructor( $container, $closeButton, $fullscreenButton ) {
			super( $container );

			this.$close = $closeButton;
			this.$fullscreen = $fullscreenButton;

			this.$reuse = $( '<a>' )
				.attr( 'role', 'button' )
				.addClass( 'mw-mmv-reuse-button' )
				.text( '\u00A0' )
				.prop( 'title', mw.message( 'multimediaviewer-reuse-link' ).text() );

			this.$options = $( '<button>' )
				.text( ' ' )
				.prop( 'title', mw.message( 'multimediaviewer-options-tooltip' ).text() )
				.addClass( 'mw-mmv-options-button' );

			this.$download = $( '<a>' )
				.attr( 'role', 'button' )
				.addClass( 'mw-mmv-download-button' )
				.text( '\u00A0' )
				.prop( 'title', mw.message( 'multimediaviewer-download-link' ).text() );

			this.$next = $( '<button>' )
				.prop( 'title', mw.message( 'multimediaviewer-next-image-alt-text' ).text() )
				.addClass( 'mw-mmv-next-image disabled' )
				.text( '\u00A0' );

			this.$prev = $( '<button>' )
				.prop( 'title', mw.message( 'multimediaviewer-prev-image-alt-text' ).text() )
				.addClass( 'mw-mmv-prev-image disabled' )
				.text( '\u00A0' );

			this.$nav = this.$next
				.add( this.$prev );

			this.$buttons = this.$close
				.add( this.$download )
				.add( this.$reuse )
				.add( this.$fullscreen )
				.add( this.$options )
				.add( this.$next )
				.add( this.$prev );

			this.$buttons.appendTo( this.$container );

			$( document ).on( 'mmv-close', () => {
				this.$nav.addClass( 'disabled' );
			} );

			this.$close.on( 'click', () => {
				$container.trigger( $.Event( 'mmv-close' ) );
			} );

			this.$next.on( 'click', () => {
				this.emit( 'next' );
			} );

			this.$prev.on( 'click', () => {
				this.emit( 'prev' );
			} );
		}

		/**
		 * Sets the top offset for the navigation buttons.
		 *
		 * @param {number} offset
		 */
		setOffset( offset ) {
			this.$nav.css( {
				top: offset
			} );
		}

		/**
		 * Stops the fading animation of the buttons and cancel any opacity value
		 */
		stopFade() {
			this.$buttons
				.stop( true )
				.removeClass( 'hidden' )
				.css( 'opacity', '' );

			this.$container.trigger( $.Event( 'mmv-fade-stopped' ) );
		}

		/**
		 * Toggles buttons being disabled or not
		 *
		 * @param {boolean} showPrevButton
		 * @param {boolean} showNextButton
		 */
		toggle( showPrevButton, showNextButton ) {
			this.$next.toggleClass( 'disabled', !showPrevButton );
			this.$prev.toggleClass( 'disabled', !showNextButton );
		}

		/**
		 * Fades out the active buttons
		 */
		fadeOut() {
			// We don't use animation chaining because delay() can't be stop()ed
			this.buttonsFadeTimeout = setTimeout( () => {
				// FIXME: Use CSS transition
				// eslint-disable-next-line no-jquery/no-animate
				this.$buttons.not( '.disabled' ).animate( { opacity: 0 }, 1000, 'swing',
					() => {
						this.$buttons.addClass( 'hidden' );
						this.$container.trigger( $.Event( 'mmv-faded-out' ) );
					} );
			}, 1500 );
		}

		/**
		 * Checks if any active buttons are currently hovered, given a position
		 *
		 * @param {number} x The horizontal coordinate of the position
		 * @param {number} y The vertical coordinate of the position
		 * @return {boolean}
		 */
		isAnyActiveButtonHovered( x, y ) {
			// We don't use mouseenter/mouseleave events because content is subject
			// to change underneath the cursor, eg. when entering fullscreen or
			// when going prev/next (the button can disappear when reaching ends)
			let hovered = false;

			this.$buttons.not( '.disabled' ).each( ( idx, e ) => {
				const $e = $( e );
				const offset = $e.offset();

				if ( y >= offset.top &&
					// using css( 'height' ) & css( 'width' ) instead of .height()
					// and .width() since those don't include padding, and as a
					// result can return a smaller size than is actually the button
					y <= offset.top + parseInt( $e.css( 'height' ) ) &&
					x >= offset.left &&
					x <= offset.left + parseInt( $e.css( 'width' ) ) ) {
					hovered = true;
				}
			} );

			return hovered;
		}

		/**
		 * Reveals all active buttons and schedule a fade out if needed
		 *
		 * @param {Object} [mousePosition] Mouse position containing 'x' and 'y' properties
		 */
		revealAndFade( mousePosition ) {
			if ( this.buttonsFadeTimeout ) {
				clearTimeout( this.buttonsFadeTimeout );
			}

			// Stop ongoing animations and make sure the buttons that need to be displayed are displayed
			this.stopFade();

			// mousePosition can be empty, for instance when we enter fullscreen and haven't
			// recorded a real mousemove event yet
			if ( !mousePosition ||
				!this.isAnyActiveButtonHovered( mousePosition.x, mousePosition.y ) ) {
				this.fadeOut();
			}
		}

		/**
		 * Registers listeners.
		 *
		 * @fires ReuseDialog#mmv-reuse-opened
		 * @fires ReuseDialog#mmv-reuse-closed
		 * @fires DownloadDialog#mmv-download-opened
		 * @fires DownloadDialog#mmv-download-closed
		 * @fires OptionsDialog#mmv-options-opened
		 * @fires OptionsDialog#mmv-options-closed
		 */
		attach() {
			this.$reuse.on( 'click.mmv-canvasButtons', ( e ) => {
				$( document ).trigger( 'mmv-reuse-open', e );
				return false;
			} );
			this.handleEvent( 'mmv-reuse-opened', () => this.$reuse.addClass( 'open' ) );
			this.handleEvent( 'mmv-reuse-closed', () => this.$reuse.removeClass( 'open' ) );

			this.$download.on( 'click.mmv-canvasButtons', ( e ) => {
				$( document ).trigger( 'mmv-download-open', e );
				return false;
			} );
			this.handleEvent( 'mmv-download-opened', () => this.$download.addClass( 'open' ) );
			this.handleEvent( 'mmv-download-closed', () => this.$download.removeClass( 'open' ) );

			this.$options.on( 'click.mmv-canvasButtons', ( e ) => {
				$( document ).trigger( 'mmv-options-open', e );
				e.stopPropagation();
			} );
			this.handleEvent( 'mmv-options-opened', () => this.$options.addClass( 'open' ) );
			this.handleEvent( 'mmv-options-closed', () => this.$options.removeClass( 'open' ) );

			this.$download
				.add( this.$reuse )
				.add( this.$options )
				.add( this.$close )
				.add( this.$fullscreen );
		}

		/**
		 * Removes all UI things from the DOM, or hides them
		 */
		unattach() {
			super.unattach();

			this.$download
				.add( this.$reuse )
				.add( this.$options )
				.add( this.$close )
				.add( this.$fullscreen )
				.off( 'click.mmv-canvasButtons' );
		}

		/**
		 * @param {Image} image
		 */
		set( image ) {
			this.$reuse.prop( 'href', image.descriptionUrl );
			this.$download.prop( 'href', image.url );
		}

		empty() {
			this.$reuse
				.removeClass( 'open' )
				.prop( 'href', null );
			this.$download
				.prop( 'href', null );
		}
	}

	module.exports = CanvasButtons;
}() );
