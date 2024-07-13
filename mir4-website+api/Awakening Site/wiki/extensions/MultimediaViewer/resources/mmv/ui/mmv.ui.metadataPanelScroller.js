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

const UiElement = require( './mmv.ui.js' );

( function () {

	/**
	 * Handles scrolling behavior of the metadata panel.
	 */
	class MetadataPanelScroller extends UiElement {
		/**
		 * @param {jQuery} $container The container for the panel (.mw-mmv-post-image).
		 * @param {jQuery} $aboveFold The control bar element (.mw-mmv-above-fold).
		 * @param {mw.SafeStorage} localStorage the localStorage object, for dependency injection
		 */
		constructor( $container, $aboveFold, localStorage ) {
			super( $container );

			this.$aboveFold = $aboveFold;

			/** @property {mw.SafeStorage} localStorage */
			this.localStorage = localStorage;

			/** @property {boolean} panelWasOpen state flag which will be used to detect open <-> closed transitions */
			this.panelWasOpen = null;

			/**
			 * Whether this user has ever opened the metadata panel.
			 * Based on a localstorage flag; will be set to true if the client does not support localstorage.
			 *
			 * @type {boolean}
			 */
			this.hasOpenedMetadata = undefined;

			/**
			 * Whether we've already fired an animation for the metadata div in this lightbox session.
			 *
			 * @property {boolean}
			 * @private
			 */
			this.hasAnimatedMetadata = false;

			this.initialize();
		}

		attach() {
			this.handleEvent( 'keydown', ( e ) => {
				this.keydown( e );
			} );

			$( window ).on( 'scroll.mmvp', mw.util.throttle( () => {
				this.scroll();
			}, 250 ) );

			this.$container.on( 'mmv-metadata-open', () => {
				if ( !this.hasOpenedMetadata && this.localStorage.store ) {
					this.hasOpenedMetadata = true;
					this.localStorage.set( 'mmv.hasOpenedMetadata', '1' );
				}
			} );

			// reset animation flag when the viewer is reopened
			this.hasAnimatedMetadata = false;
		}

		unattach() {
			this.clearEvents();
			$( window ).off( 'scroll.mmvp' );
			this.$container.off( 'mmv-metadata-open' );
		}

		empty() {
			// need to remove this to avoid animating again when reopening lightbox on same page
			this.$container.removeClass( 'invite' );

			this.panelWasOpen = this.panelIsOpen();
		}

		/**
		 * Returns scroll top position when the panel is fully open.
		 * (In other words, the height of the area that is outside the screen, in pixels.)
		 *
		 * @return {number}
		 */
		getScrollTopWhenOpen() {
			return this.$container.outerHeight() - parseInt( this.$aboveFold.css( 'min-height' ), 10 ) -
				parseInt( this.$aboveFold.css( 'padding-bottom' ), 10 );
		}

		/**
		 * Makes sure the panel does not contract when it is emptied and thus keeps its position as much as possible.
		 * This should be called when switching images, before the panel is emptied, and should be undone with
		 * unfreezeHeight after the panel has been populated with the new metadata.
		 */
		freezeHeight() {
			// TODO: Store visibility in model
			// eslint-disable-next-line no-jquery/no-sizzle
			if ( !this.$container.is( ':visible' ) ) {
				return;
			}

			const scrollTop = $( window ).scrollTop();
			const scrollTopWhenOpen = this.getScrollTopWhenOpen();

			this.panelWasFullyOpen = ( scrollTop === scrollTopWhenOpen );
			this.$container.css( 'min-height', this.$container.height() );
		}

		unfreezeHeight() {
			// TODO: Store visibility in model
			// eslint-disable-next-line no-jquery/no-sizzle
			if ( !this.$container.is( ':visible' ) ) {
				return;
			}

			this.$container.css( 'min-height', '' );
			if ( this.panelWasFullyOpen ) {
				$( window ).scrollTop( this.getScrollTopWhenOpen() );
			}
		}

		initialize() {
			const value = this.localStorage.get( 'mmv.hasOpenedMetadata' );

			// localStorage will only store strings; if values `null`, `false` or
			// `0` are set, they'll come out as `"null"`, `"false"` or `"0"`, so we
			// can be certain that an actual null is a failure to locate the item,
			// and false is an issue with localStorage itself
			if ( value !== false ) {
				this.hasOpenedMetadata = value !== null;
			} else {
				// if there was an issue with localStorage, treat it as opened
				this.hasOpenedMetadata = true;
			}
		}

		/**
		 * Animates the metadata area when the viewer is first opened.
		 */
		animateMetadataOnce() {
			if ( !this.hasOpenedMetadata && !this.hasAnimatedMetadata ) {
				this.hasAnimatedMetadata = true;
				this.$container.addClass( 'invite' );
			}
		}

		/**
		 * Toggles the metadata div being totally visible.
		 *
		 * @param {string} [forceDirection] 'up' or 'down' makes the panel move on that direction (and is a noop
		 *  if the panel is already at the upmost/bottommost position); without the parameter, the panel position
		 *  is toggled. (Partially open counts as open.)
		 * @return {jQuery.Promise} A promise which resolves after the animation has finished.
		 */
		toggle( forceDirection ) {
			const scrollTopWhenOpen = this.getScrollTopWhenOpen();
			const scrollTopWhenClosed = 0;
			const scrollTop = $( window ).scrollTop();
			const panelIsOpen = scrollTop > scrollTopWhenClosed;
			const direction = forceDirection || ( panelIsOpen ? 'down' : 'up' );
			let scrollTopTarget = ( direction === 'up' ) ? scrollTopWhenOpen : scrollTopWhenClosed;

			// don't log / animate if the panel is already in the end position
			if ( scrollTopTarget === scrollTop ) {
				return $.Deferred().resolve().promise();
			} else {
				if ( direction === 'up' && !panelIsOpen ) {
					// FIXME nasty. This is not really an event but a command sent to the metadata panel;
					// child UI elements should not send commands to their parents. However, there is no way
					// to calculate the target scrollTop accurately without revealing the text, and the event
					// which does that (metadata-open) is only triggered later in the process, when the panel
					// actually scrolled, so we cannot use it here without risking triggering it multiple times.
					this.$container.trigger( 'mmv-metadata-reveal-truncated-text' );
					scrollTopTarget = this.getScrollTopWhenOpen();
				}
				// eslint-disable-next-line no-jquery/no-global-selector
				return $( 'html, body' ).animate( { scrollTop: scrollTopTarget }, 'fast' ).promise();
			}
		}

		/**
		 * Handles keydown events for this element.
		 *
		 * @param {jQuery.Event} e Key down event
		 */
		keydown( e ) {
			if ( e.altKey || e.shiftKey || e.ctrlKey || e.metaKey ) {
				return;
			}
			switch ( e.which ) {
				case 40: // Down arrow

				// fall through
				case 38: // Up arrow
					this.toggle();
					e.preventDefault();
					break;
			}
		}

		/**
		 * Returns whether the metadata panel is open. (Partially open is considered to be open.)
		 *
		 * @return {boolean}
		 */
		panelIsOpen() {
			return $( window ).scrollTop() > 0;
		}

		/**
		 * @event MetadataPanelScroller#mmv-metadata-open
		 */

		/**
		 * @event MetadataPanelScroller#mmv-metadata-close
		 */

		/**
		 * Receives the window's scroll events and and turns them into business logic events
		 *
		 * @fires MetadataPanelScroller#mmv-metadata-open
		 * @fires MetadataPanelScroller#mmv-metadata-close
		 */
		scroll() {
			const panelIsOpen = this.panelIsOpen();

			if ( panelIsOpen && !this.panelWasOpen ) { // just opened
				this.$container.trigger( 'mmv-metadata-open' );
			} else if ( !panelIsOpen && this.panelWasOpen ) { // just closed
				this.$container.trigger( 'mmv-metadata-close' );
			}
			this.panelWasOpen = panelIsOpen;
		}
	}

	module.exports = MetadataPanelScroller;
}() );
