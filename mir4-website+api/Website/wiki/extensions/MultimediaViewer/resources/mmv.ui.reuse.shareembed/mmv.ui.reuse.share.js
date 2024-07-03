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

const { getMediaHash } = require( 'mmv.head' );
const Tab = require( './mmv.ui.reuse.tab.js' );

( function () {

	/**
	 * Represents the file reuse dialog and link to open it.
	 */
	class Share extends Tab {
		/**
		 * @param {jQuery} $container
		 */
		constructor( $container ) {
			super( $container );
			this.init();
		}

		init() {
			this.$pane.addClass( 'mw-mmv-share-pane' )
				.appendTo( this.$container );

			this.pageInput = new mw.widgets.CopyTextLayout( {
				help: mw.message( 'multimediaviewer-share-explanation' ).text(),
				helpInline: true,
				align: 'top',
				textInput: {
					placeholder: mw.message( 'multimediaviewer-reuse-loading-placeholder' ).text()
				},
				button: {
					label: '',
					title: mw.msg( 'multimediaviewer-reuse-copy-share' )
				}
			} );

			this.$pageLink = $( '<a>' )
				.addClass( 'mw-mmv-share-page-link' )
				.prop( 'alt', mw.message( 'multimediaviewer-link-to-page' ).text() )
				.prop( 'target', '_blank' )
				.text( '\u00A0' )
				.appendTo( this.$pane );

			this.pageInput.$element.appendTo( this.$pane );

			this.$pane.appendTo( this.$container );
		}

		/**
		 * Shows the pane.
		 */
		show() {
			super.show();
			this.select();
		}

		/**
		 * @inheritdoc
		 * @param {Image} image
		 */
		set( image ) {
			const url = image.descriptionUrl + getMediaHash( image.title );

			this.pageInput.textInput.setValue( url );

			this.select();

			this.$pageLink.prop( 'href', url );
		}

		/**
		 * @inheritdoc
		 */
		empty() {
			this.pageInput.textInput.setValue( '' );
			this.$pageLink.prop( 'href', null );
		}

		/**
		 * Selects the text in the readonly textbox.
		 */
		select() {
			this.pageInput.selectText();
		}
	}

	module.exports = Share;
}() );
