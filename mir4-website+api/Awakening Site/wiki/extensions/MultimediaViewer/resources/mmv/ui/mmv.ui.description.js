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
	 * Description element in the UI.
	 */
	class Description extends UiElement {
		constructor( $container ) {
			super( $container );

			/** @property {HtmlUtils} htmlUtils - */
			this.htmlUtils = new HtmlUtils();

			this.$imageDescDiv = $( '<div>' )
				.addClass( 'mw-mmv-image-desc-div empty' )
				.appendTo( this.$container );

			this.$imageDesc = $( '<p>' )
				.addClass( 'mw-mmv-image-desc' )
				.appendTo( this.$imageDescDiv );
		}

		/**
		 * Sets data on the element.
		 * This complements MetadataPanel.setTitle() - information shown there will not be shown here.
		 *
		 * @param {string|null} description The text of the description
		 * @param {string|null} caption The text of the caption
		 */
		set( description, caption ) {
			if ( caption && description ) { // panel header shows the caption - show description here
				this.$imageDesc.html( this.htmlUtils.htmlToTextWithTags( description ) );
				this.$imageDescDiv.removeClass( 'empty' );
			} else { // either there is no description or the paner header already shows it - nothing to do here
				this.empty();
			}
		}

		/**
		 * @inheritdoc
		 */
		empty() {
			this.$imageDesc.empty();
			this.$imageDescDiv.addClass( 'empty' );
		}
	}

	module.exports = Description;
}() );
