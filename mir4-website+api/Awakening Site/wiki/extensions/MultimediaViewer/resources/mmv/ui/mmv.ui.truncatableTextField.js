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

const { HtmlUtils } = require( 'mmv.bootstrap' );
const UiElement = require( './mmv.ui.js' );

( function () {

	/**
	 * Represents any text field that might need to be truncated to be readable. Text will be adjusted to
	 * fit into its container.
	 *
	 * More specifically, TruncatableTextField should be invoked with a fixed-height container as the first
	 * parameter and a flexible-width content (which gets its size from the text inside it) as the second
	 * one. The container gets overflow: hidden, and the content is placed inside it; if the content
	 * overflows the container, TruncatableTextField will cycle through a set of styles and apply to the
	 * container the first one that makes the content not overflow anymore. If none of the styles do that,
	 * the last one is applied anyway.
	 *
	 * The list of styles can be customized; by default, they set progressively smaller font size, and the
	 * last one adds an ellipsis to the end. (An ellipsis element is automatically appended to the end of
	 * the container to help with this, but it is hidden unless made visible by one of the styles.)
	 *
	 * grow() and shrink() can be used to show full text (by making the container flexible-height) and hiding
	 * them again; TruncatableTextField will not call them automatically (the caller class should e.g. set up
	 * a click handler on the ellipsis).
	 *
	 * repaint() should be called after layout changes to keep the truncation accurate.
	 */
	class TruncatableTextField extends UiElement {
		/**
		 * @param {jQuery} $container The container for the element.
		 * @param {jQuery} $element The element where we should put the text.
		 * @param {Object} [options]
		 * @param {string[]} [options.styles] a list of styles to try if the text does not fit into the container.
		 *  Will stop at the first which makes the text fit; the last one will be used even if it does not make
		 *  the text fit.
		 */
		constructor( $container, $element, options ) {
			super( $container );

			/** @property {jQuery} $element The DOM element that holds text for this element. */
			this.$element = $element;

			/** @property {Object} options - */
			this.options = Object.assign( {
				styles: [ 'mw-mmv-ttf-small', 'mw-mmv-ttf-smaller', 'mw-mmv-ttf-smallest' ]
			}, options );

			/** @property {boolean} expanded true if the text is long enough to be truncated but the full text is shown */
			this.expanded = false;

			/** @property {jQuery} ellipsis the element which marks that the text was truncated */
			this.$ellipsis = null;

			/** @property {string} normalTitle title attribute to show when the text is not truncated */
			this.normalTitle = null;

			/** @property {string} truncatedTitle title attribute to show when the text is not truncated */
			this.truncatedTitle = null;

			/** @property {HtmlUtils} htmlUtils Our HTML utility instance. */
			this.htmlUtils = new HtmlUtils();

			this.init();
		}

		/**
		 * Initializes the DOM.
		 *
		 * @private
		 */
		init() {
			this.$ellipsis = $( '<span>' )
				.text( '…' )
				.hide()
				.addClass( 'mw-mmv-ttf-ellipsis' );

			this.$container
				.addClass( 'mw-mmv-ttf-container empty' )
				.append( this.$element, this.$ellipsis );
		}

		attach() {
			$( window ).on( 'resize.mmv-ttf', mw.util.debounce( this.repaint.bind( this ), 100 ) );
		}

		unattach() {
			$( window ).off( 'resize.mmv-ttf' );
		}

		/**
		 * Sets the string for the element.
		 *
		 * @param {string} value Warning - unsafe HTML is allowed here.
		 */
		set( value ) {
			this.$element.empty().append( this.htmlUtils.htmlToTextWithTags( value ) );
			this.changeStyle();
			this.$container.toggleClass( 'empty', !value );
			this.$ellipsis.hide();
			this.shrink();
		}

		empty() {
			this.$element.empty();
			// eslint-disable-next-line mediawiki/class-doc
			this.$container
				.removeClass( this.options.styles.concat( [ 'mw-mmv-ttf-untruncated', 'mw-mmv-ttf-truncated' ] ) )
				.addClass( 'empty' );
			this.$ellipsis.hide();
			this.setTitle( '', '' );
			this.expanded = false;
		}

		/**
		 * Recalculate truncation after layout changes (such as resize)
		 */
		repaint() {
			this.changeStyle();
			this.$ellipsis.hide();
			this.shrink();
		}

		/**
		 * Allows setting different titles for fully visible and for truncated text.
		 *
		 * @param {string} normal
		 * @param {string} truncated
		 */
		setTitle( normal, truncated ) {
			this.normalTitle = normal;
			this.truncatedTitle = truncated;
			this.updateTitle();
		}

		/**
		 * Selects the right title to use (for full or for truncated version). The title can be set with setTitle().
		 */
		updateTitle() {
			const $elementsWithTitle = this.$element.add( this.$ellipsis );
			$elementsWithTitle.attr( 'original-title', this.isTruncated() ? this.truncatedTitle : this.normalTitle );
		}

		/**
		 * Returns true if the text is long enough that it needs to be truncated.
		 *
		 * @return {boolean}
		 */
		isTruncatable() {
			// height calculation logic does not work for expanded state since the container expands
			// to envelop the element, but we never go into expanded state for non-truncatable elements anyway
			return this.$container.height() < this.$element.height() || this.expanded;
		}

		/**
		 * Returns true if the text is truncated at the moment.
		 *
		 * @return {boolean}
		 */
		isTruncated() {
			return this.isTruncatable() && !this.expanded;
		}

		/**
		 * Makes the container fixed-width, clipping the text.
		 * This will only add a .mw-mmv-ttf-truncated class; it's the caller's responsibility to define the fixed
		 * height for that class.
		 */
		shrink() {
			if ( this.isTruncatable() ) {
				this.expanded = false;
				this.$container.addClass( 'mw-mmv-ttf-truncated' ).removeClass( 'mw-mmv-ttf-untruncated' );
				this.$ellipsis.show();
				this.updateTitle();
			}
		}

		/**
		 * Makes the container flexible-width, thereby restoring the full text.
		 */
		grow() {
			if ( this.isTruncatable() ) {
				this.expanded = true;
				this.$container.removeClass( 'mw-mmv-ttf-truncated' ).addClass( 'mw-mmv-ttf-untruncated' );
				this.$ellipsis.hide();
				this.updateTitle();
			}
		}

		/**
		 * Changes the element style if a certain length is reached.
		 */
		changeStyle() {
			let newClass = 'mw-mmv-ttf-normal';

			// eslint-disable-next-line mediawiki/class-doc
			this.$container
				.removeClass( this.options.styles.concat( [ 'mw-mmv-ttf-untruncated', 'mw-mmv-ttf-truncated' ] ) )
				.addClass( newClass );
			this.expanded = false;

			for ( const v of this.options.styles ) {
				if ( !this.isTruncatable() ) {
					break;
				}

				const oldClass = newClass;
				newClass = v;
				// eslint-disable-next-line mediawiki/class-doc
				this.$container.removeClass( oldClass ).addClass( newClass );
			}
		}
	}

	module.exports = TruncatableTextField;
}() );
