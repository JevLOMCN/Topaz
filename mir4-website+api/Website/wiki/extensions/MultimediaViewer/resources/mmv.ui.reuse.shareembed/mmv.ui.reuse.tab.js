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

const { UiElement } = require( 'mmv' );

( function () {

	/**
	 * A tab in a pane component
	 */
	class Tab extends UiElement {
		/**
		 * @param {jQuery} $container
		 */
		constructor( $container ) {
			super( $container );

			/**
			 * Container for the tab.
			 *
			 * @property {jQuery}
			 */
			this.$pane = $( '<div>' ).addClass( 'mw-mmv-reuse-pane' );

		}

		/**
		 * Shows the pane.
		 */
		show() {
			this.$pane.addClass( 'active' );
		}

		/**
		 * Hides the pane.
		 */
		hide() {
			this.$pane.removeClass( 'active' );
		}
	}

	module.exports = Tab;
}() );
