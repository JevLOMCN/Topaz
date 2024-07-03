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

( function () {
	let cachedRTL;

	/**
	 * Represents a UI element.
	 *
	 * @abstract
	 */
	class UiElement {
		/**
		 * @param {jQuery} $container
		 */
		constructor( $container ) {
			OO.EventEmitter.call( this );

			/** @property {jQuery} $container The element that contains the UI element. */
			this.$container = $container;

			/** @property {Object.<string, string[]>} eventsRegistered Events that this element has registered with the DOM. */
			this.eventsRegistered = {};

			/**
			 * @property {Object.<string, jQuery>} $inlineStyles a list of `<style>` elements in the head
			 *  which we use to manipulate pseudo-classes and pseudo-elements.
			 */
			this.$inlineStyles = [];

			/**
			 * Stores named timeouts. See setTimer().
			 *
			 * @private
			 * @property {Object.<string, {timeout: Object, handler: function(), delay: number}>}
			 */
			this.timers = {};
		}

		/**
		 * Checks whether the document is RTL. Assumes it doesn't change.
		 *
		 * @return {boolean}
		 */
		isRTL() {
			if ( cachedRTL === undefined ) {
				cachedRTL = $( document.body ).hasClass( 'rtl' );
			}

			return cachedRTL;
		}

		/**
		 * Sets the data for the element.
		 *
		 * @abstract
		 */
		set() { }

		/**
		 * Empties the element.
		 *
		 * @abstract
		 */
		empty() { }

		/**
		 * Registers listeners.
		 *
		 * @abstract
		 */
		attach() { }

		/**
		 * Clears listeners.
		 *
		 * @abstract
		 */
		unattach() {
			this.clearEvents();
		}

		/**
		 * Add event handler in a way that will be auto-cleared on lightbox close
		 *
		 * TODO: Unit tests
		 *
		 * @param {string} name Name of event, like 'keydown'
		 * @param {Function} handler Callback for the event
		 */
		handleEvent( name, handler ) {
			if ( this.eventsRegistered[ name ] === undefined ) {
				this.eventsRegistered[ name ] = [];
			}
			this.eventsRegistered[ name ].push( handler );
			$( document ).on( name, handler );
		}

		/**
		 * Remove all events that have been registered on this element.
		 *
		 * TODO: Unit tests
		 */
		clearEvents() {
			for ( const ev in this.eventsRegistered ) {
				const handlers = this.eventsRegistered[ ev ];
				while ( handlers.length > 0 ) {
					$( document ).off( ev, handlers.pop() );
				}
			}
		}

		/**
		 * Manipulate CSS directly. This is needed to set styles for pseudo-classes and pseudo-elements.
		 *
		 * @param {string} key some name to identify the style
		 * @param {string|null} style a CSS snippet (set to null to delete the given style)
		 */
		setInlineStyle( key, style ) {

			if ( !this.$inlineStyles ) {
				this.$inlineStyles = [];
			}

			if ( !this.$inlineStyles[ key ] ) {
				if ( !style ) {
					return;
				}

				this.$inlineStyles[ key ] = $( '<style>' ).attr( 'type', 'text/css' ).appendTo( 'head' );
			}

			this.$inlineStyles[ key ].html( style || '' );
		}

		/**
		 * Sets a timer. This is a shortcut to using the native setTimout and then storing
		 * the reference, with some small differences for convenience:
		 * - setting the same timer again clears the old one
		 * - callbacks have the element as their context
		 * Timers are local to the element.
		 * See also clearTimer() and resetTimer().
		 *
		 * @param {string} name
		 * @param {function()} callback
		 * @param {number} delay delay in milliseconds
		 */
		setTimer( name, callback, delay ) {
			this.clearTimer( name );
			this.timers[ name ] = {
				timeout: null,
				handler: callback,
				delay: delay
			};
			this.timers[ name ].timeout = setTimeout( () => {
				delete this.timers[ name ];
				callback.call( this );
			}, delay );
		}

		/**
		 * Clears a timer. See setTimer().
		 *
		 * @param {string} name
		 */
		clearTimer( name ) {
			if ( name in this.timers ) {
				clearTimeout( this.timers[ name ].timeout );
				delete this.timers[ name ];
			}
		}

		/**
		 * Resets a timer, so that its delay will be relative to when resetTimer() was called, not when
		 * the timer was created. Optionally changes the delay as well.
		 * Resetting a timer that does not exist or has already fired has no effect.
		 * See setTimer().
		 *
		 * @param {string} name
		 * @param {number} [delay] delay in milliseconds
		 */
		resetTimer( name, delay ) {
			if ( name in this.timers ) {
				if ( delay === undefined ) {
					delay = this.timers[ name ].delay;
				}
				this.setTimer( name, this.timers[ name ].handler, delay );
			}
		}

		/**
		 * Flips E (east) and W (west) directions in RTL documents.
		 *
		 * @param {string} keyword a keyword where the first 'e' or 'w' character means a direction (such as a
		 *  tipsy gravity parameter)
		 * @return {string}
		 */
		correctEW( keyword ) {
			if ( this.isRTL() ) {
				keyword = keyword.replace( /[ew]/i, ( dir ) => {
					if ( dir === 'e' ) {
						return 'w';
					} else if ( dir === 'E' ) {
						return 'W';
					} else if ( dir === 'w' ) {
						return 'e';
					} else if ( dir === 'W' ) {
						return 'E';
					}
				} );
			}
			return keyword;
		}
	}

	OO.mixinClass( UiElement, OO.EventEmitter );

	module.exports = UiElement;
}() );
