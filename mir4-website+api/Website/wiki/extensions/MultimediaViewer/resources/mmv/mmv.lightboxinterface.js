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

const { Config } = require( 'mmv.bootstrap' );
const Canvas = require( './ui/mmv.ui.canvas.js' );
const CanvasButtons = require( './ui/mmv.ui.canvasButtons.js' );
const DownloadDialog = require( './ui/mmv.ui.download.dialog.js' );
const MetadataPanel = require( './ui/mmv.ui.metadataPanel.js' );
const OptionsDialog = require( './ui/mmv.ui.viewingOptions.js' );
const ReuseDialog = require( './ui/mmv.ui.reuse.dialog.js' );
const ThumbnailWidthCalculator = require( './mmv.ThumbnailWidthCalculator.js' );
const UiElement = require( './ui/mmv.ui.js' );

( function () {

	/**
	 * Represents the main interface of the lightbox
	 */
	class LightboxInterface extends UiElement {

		constructor() {
			const $wrapper = $( '<div>' )
				.addClass( 'mw-mmv-wrapper' );
			super( $wrapper );
			this.$wrapper = $wrapper;

			this.localStorage = mw.storage;

			/** @property {Config} config - */
			this.config = new Config(
				mw.config.get( 'wgMultimediaViewer', {} ),
				mw.config,
				mw.user,
				new mw.Api(),
				this.localStorage
			);

			/**
			 * @property {ThumbnailWidthCalculator}
			 * @private
			 */
			this.thumbnailWidthCalculator = new ThumbnailWidthCalculator();
			// SVG filter, needed to achieve blur in Firefox
			// eslint-disable-next-line no-jquery/no-parse-html-literal
			this.$filter = $( '<svg><filter id="gaussian-blur"><fegaussianblur stdDeviation="3"></filter></svg>' );

			this.$main = $( '<div>' )
				.addClass( 'mw-mmv-main' );

			// I blame CSS for this
			this.$innerWrapper = $( '<div>' )
				.addClass( 'mw-mmv-image-inner-wrapper' );

			this.$imageWrapper = $( '<div>' )
				.addClass( 'mw-mmv-image-wrapper' )
				.append( this.$innerWrapper );

			this.$preDiv = $( '<div>' )
				// The overlay has no-invert, so the interface overlaid
				// on it must also have no-invert
				.addClass( 'mw-mmv-pre-image mw-no-invert' );

			this.$postDiv = $( '<div>' )
				.addClass( 'mw-mmv-post-image' );

			this.$aboveFold = $( '<div>' )
				.addClass( 'mw-mmv-above-fold' );

			this.$main.append(
				this.$preDiv,
				this.$imageWrapper,
				this.$postDiv,
				this.$filter
			);

			this.$wrapper.append(
				this.$main
			);

			this.setupCanvasButtons();

			this.panel = new MetadataPanel( this.$postDiv, this.$aboveFold, this.localStorage, this.config );
			this.buttons = new CanvasButtons( this.$preDiv, this.$closeButton, this.$fullscreenButton );
			this.canvas = new Canvas( this.$innerWrapper, this.$imageWrapper, this.$wrapper );

			this.fileReuse = new ReuseDialog( this.$innerWrapper, this.buttons.$reuse, this.config );
			this.downloadDialog = new DownloadDialog( this.$innerWrapper, this.buttons.$download, this.config );
			this.optionsDialog = new OptionsDialog( this.$innerWrapper, this.buttons.$options, this.config );
		}

		/**
		 * Sets up the file reuse data in the DOM
		 *
		 * @param {Image} image
		 * @param {Repo} repo
		 * @param {string} caption
		 * @param {string} alt
		 */
		setFileReuseData( image, repo, caption, alt ) {
			this.buttons.set( image );
			this.fileReuse.set( image, repo, caption, alt );
			this.downloadDialog.set( image, repo );
		}

		/**
		 * Empties the interface.
		 */
		empty() {
			this.panel.empty();

			this.canvas.empty();

			this.buttons.empty();

			this.$main.addClass( 'metadata-panel-is-closed' )
				.removeClass( 'metadata-panel-is-open' );
		}

		/**
		 * Opens the lightbox.
		 */
		open() {
			this.empty();
			this.attach();
		}

		/**
		 * Attaches the interface to the DOM.
		 *
		 * @param {string} [parentId] parent id where we want to attach the UI. Defaults to document
		 *  element, override is mainly used for testing.
		 */
		attach( parentId ) {
			// Advanced description needs to be below the fold when the lightbox opens
			// regardless of what the scroll value was prior to opening the lightbox
			// If the lightbox is already attached, it means we're doing prev/next, and
			// we should avoid scrolling the panel
			if ( !this.attached ) {
				$( window ).scrollTop( 0 );
			}

			// Make sure that the metadata is going to be at the bottom when it appears
			// 83 is the height of the top metadata area. Which can't be measured by
			// reading the DOM at this point of the execution, unfortunately
			this.$postDiv.css( 'top', `${ $( window ).height() - 83 }px` );

			// Re-appending the same content can have nasty side-effects
			// Such as the browser leaving fullscreen mode if the fullscreened element is part of it
			if ( this.currentlyAttached ) {
				return;
			}

			this.handleEvent( 'keyup', ( e ) => {
				if ( e.keyCode === 27 && !( e.altKey || e.ctrlKey || e.shiftKey || e.metaKey ) ) {
					// Escape button pressed
					this.unattach();
				}
			} );

			this.handleEvent( 'fullscreenchange.lip', () => {
				this.fullscreenChange();
			} );

			this.handleEvent( 'keydown', ( e ) => {
				this.keydown( e );
			} );

			// mousemove generates a ton of events, which is why we throttle it
			this.handleEvent( 'mousemove.lip', mw.util.throttle( ( e ) => {
				this.mousemove( e );
			}, 250 ) );

			this.handleEvent( 'mmv-faded-out', ( e ) => {
				this.fadedOut( e );
			} );
			this.handleEvent( 'mmv-fade-stopped', ( e ) => {
				this.fadeStopped( e );
			} );

			this.buttons.connect( this, {
				next: [ 'emit', 'next' ],
				prev: [ 'emit', 'prev' ]
			} );

			const $parent = $( parentId || document.body );

			// Clean up fullscreen data left attached to the DOM
			this.$main.removeClass( 'jq-fullscreened' );
			this.isFullscreen = false;

			$parent
				.append(
					this.$wrapper
				);
			this.currentlyAttached = true;

			this.panel.attach();

			this.canvas.attach();

			// cross-communication between panel and canvas, sort of
			this.$postDiv.on( 'mmv-metadata-open.lip', () => {
				this.$main.addClass( 'metadata-panel-is-open' )
					.removeClass( 'metadata-panel-is-closed' );
			} ).on( 'mmv-metadata-close.lip', () => {
				this.$main.removeClass( 'metadata-panel-is-open' )
					.addClass( 'metadata-panel-is-closed' );
			} );
			this.$wrapper.on( 'mmv-panel-close-area-click.lip', () => {
				this.panel.scroller.toggle( 'down' );
			} );

			// Buttons fading might not had been reset properly after a hard fullscreen exit
			// This needs to happen after the parent attach() because the buttons need to be attached
			// to the DOM for $.fn.stop() to work
			this.buttons.stopFade();
			this.buttons.attach();

			this.fileReuse.attach();
			this.downloadDialog.attach();
			this.optionsDialog.attach();

			// Reset the cursor fading
			this.fadeStopped();

			this.attached = true;
		}

		/**
		 * Detaches the interface from the DOM.
		 *
		 * @fires MultimediaViewer#mmv-close
		 */
		unattach() {
			// We trigger this event on the document because unattach() can run
			// when the interface is unattached
			// We're calling this before cleaning up (below) the DOM, as that
			// appears to have an impact on automatic scroll restoration (which
			// might happen as a result of this being closed) in FF
			$( document ).trigger( $.Event( 'mmv-close' ) )
				.off( 'fullscreenchange.lip' );

			// Has to happen first so that the scroller can freeze with visible elements
			this.panel.unattach();

			this.$wrapper.detach();

			this.currentlyAttached = false;

			this.buttons.unattach();

			this.$postDiv.off( '.lip' );
			this.$wrapper.off( 'mmv-panel-close-area-click.lip' );

			this.fileReuse.unattach();
			this.fileReuse.closeDialog();

			this.downloadDialog.unattach();
			this.downloadDialog.closeDialog();

			this.optionsDialog.unattach();
			this.optionsDialog.closeDialog();

			// Canvas listens for events from dialogs, so should be unattached at the end
			this.canvas.unattach();

			this.clearEvents();

			this.buttons.disconnect( this, {
				next: [ 'emit', 'next' ],
				prev: [ 'emit', 'prev' ]
			} );

			this.attached = false;
		}

		/**
		 * Exits fullscreen mode.
		 */
		exitFullscreen() {
			this.fullscreenButtonJustPressed = true;
			if ( this.$main.get( 0 ) === document.fullscreenElement ) {
				if ( document.exitFullscreen ) {
					document.exitFullscreen();
				}
			}
			this.isFullscreen = false;
			this.$main.removeClass( 'jq-fullscreened' );
		}

		/**
		 * Enters fullscreen mode.
		 */
		enterFullscreen() {
			const el = this.$main.get( 0 );
			if ( el.requestFullscreen ) {
				el.requestFullscreen();
			}
			this.isFullscreen = true;
			this.$main.addClass( 'jq-fullscreened' );
		}

		/**
		 * Setup for canvas navigation buttons
		 */
		setupCanvasButtons() {
			this.$closeButton = $( '<button>' )
				.text( ' ' )
				.addClass( 'mw-mmv-close' )
				.prop( 'title', mw.message( 'multimediaviewer-close-popup-text' ).text() )
				.on( 'click', () => {
					this.unattach();
				} );

			this.$fullscreenButton = $( '<button>' )
				.text( ' ' )
				.addClass( 'mw-mmv-fullscreen' )
				.prop( 'title', mw.message( 'multimediaviewer-fullscreen-popup-text' ).text() )
				.on( 'click', ( e ) => {
					if ( this.isFullscreen ) {
						this.exitFullscreen();

						// mousemove is throttled and the mouse coordinates only
						// register every 250ms, so there is a chance that we moved
						// our mouse over one of the buttons but it didn't register,
						// and a fadeOut is triggered; when we're coming back from
						// fullscreen, we'll want to make sure the mouse data is
						// current so that the fadeOut behavior will not trigger
						this.mousePosition = { x: e.pageX, y: e.pageY };
						this.buttons.revealAndFade( this.mousePosition );
					} else {
						this.enterFullscreen();
					}
				} );
		}

		/**
		 * Handle a fullscreen change event.
		 */
		fullscreenChange() {
			// eslint-disable-next-line compat/compat
			this.isFullscreen = !!document.fullscreenElement;
			if ( this.isFullscreen ) {
				this.$fullscreenButton
					.prop( 'title', mw.message( 'multimediaviewer-defullscreen-popup-text' ).text() )
					.attr( 'alt', mw.message( 'multimediaviewer-defullscreen-popup-text' ).text() );
			} else {
				this.$fullscreenButton
					.prop( 'title', mw.message( 'multimediaviewer-fullscreen-popup-text' ).text() )
					.attr( 'alt', mw.message( 'multimediaviewer-fullscreen-popup-text' ).text() );
			}

			if ( !this.fullscreenButtonJustPressed && !this.isFullscreen ) {
				// Close the interface all the way if the user pressed 'esc'
				this.unattach();
			} else if ( this.fullscreenButtonJustPressed ) {
				this.fullscreenButtonJustPressed = false;
			}

			// Fullscreen change events can happen after unattach(), in which
			// case we shouldn't do anything UI-related
			if ( !this.currentlyAttached ) {
				return;
			}

			if ( this.isFullscreen ) {
				// When entering fullscreen without a mousemove, the browser
				// still thinks that the cursor is where it was prior to entering
				// fullscreen. I.e. on top of the fullscreen button
				// Thus, we purposefully reset the saved position, so that
				// the fade out really takes place (otherwise it's cancelled
				// by updateControls which is called a few times when fullscreen opens)
				this.mousePosition = { x: 0, y: 0 };
				this.buttons.fadeOut();
			}

			// Some browsers only send resize events before toggling fullscreen, but not once the toggling is done
			// This makes sure that the UI is properly resized after a fullscreen change
			this.$main.trigger( $.Event( 'mmv-resize-end' ) );
		}

		/**
		 * Handles keydown events on the document
		 *
		 * @param {jQuery.Event} e The jQuery keypress event object
		 */
		keydown( e ) {
			const isRtl = $( document.body ).hasClass( 'rtl' );

			if ( e.altKey || e.shiftKey || e.ctrlKey || e.metaKey ) {
				return;
			}
			if ( e.key === 'ArrowLeft' ) {
				this.emit( isRtl ? 'next' : 'prev' );
				e.preventDefault();
			} else if ( e.key === 'ArrowRight' ) {
				this.emit( isRtl ? 'prev' : 'next' );
				e.preventDefault();
			} else if ( e.key === 'Home' ) {
				this.emit( 'first' );
				e.preventDefault();
			} else if ( e.key === 'End' ) {
				this.emit( 'last' );
				e.preventDefault();
			}
		}

		/**
		 * Handles mousemove events on the document
		 *
		 * @param {jQuery.Event} e The mousemove event object
		 */
		mousemove( e ) {
			// T77869 ignore fake mousemove events triggered by Chrome
			if (
				e &&
				e.originalEvent &&
				e.originalEvent.movementX === 0 &&
				e.originalEvent.movementY === 0
			) {
				return;
			}

			if ( e ) {
				// Saving the mouse position is useful whenever we need to
				// run LIP.mousemove manually, such as when going to the next/prev
				// element
				this.mousePosition = { x: e.pageX, y: e.pageY };
			}

			if ( this.isFullscreen ) {
				this.buttons.revealAndFade( this.mousePosition );
			}
		}

		/**
		 * Called when the buttons have completely faded out and disappeared
		 */
		fadedOut() {
			this.$main.addClass( 'cursor-hidden' );
		}

		/**
		 * Called when the buttons have stopped fading and are back into view
		 */
		fadeStopped() {
			this.$main.removeClass( 'cursor-hidden' );
		}

		/**
		 * Updates the next and prev buttons
		 *
		 * @param {boolean} showPrevButton Whether the prev button should be revealed or not
		 * @param {boolean} showNextButton Whether the next button should be revealed or not
		 */
		updateControls( showPrevButton, showNextButton ) {
			const prevNextTop = `${ ( this.$imageWrapper.height() / 2 ) - 60 }px`;

			if ( this.isFullscreen ) {
				this.$postDiv.css( 'top', '' );
			} else {
				this.$postDiv.css( 'top', this.$imageWrapper.height() );
			}

			this.buttons.setOffset( prevNextTop );
			this.buttons.toggle( showPrevButton, showNextButton );
		}
	}

	module.exports = LightboxInterface;
}() );
