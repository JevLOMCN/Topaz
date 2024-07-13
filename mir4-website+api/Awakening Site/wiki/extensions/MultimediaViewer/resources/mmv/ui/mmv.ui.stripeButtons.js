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
	 * Class for buttons which are placed on the metadata stripe (the always visible part of the
	 * metadata panel).
	 */
	class StripeButtons extends UiElement {
		/**
		 * @param {jQuery} $container the title block (.mw-mmv-title-contain) which wraps the buttons and all
		 *  other title elements
		 */
		constructor( $container ) {
			super( $container );

			this.$buttonContainer = $( '<div>' )
				.addClass( 'mw-mmv-stripe-button-container' )
				.appendTo( $container );

			/**
			 * This holds the actual buttons.
			 *
			 * @property {Object.<string, jQuery>}
			 */
			this.buttons = {};

			this.initDescriptionPageButton();
		}

		/**
		 * Creates a button linking to the file description page.
		 *
		 * @protected
		 */
		initDescriptionPageButton() {
			this.buttons.$descriptionPage = $( '<a>' )
				.addClass( 'mw-mmv-stripe-button empty mw-mmv-description-page-button cdx-button cdx-button--weight-primary cdx-button--action-progressive cdx-button--size-large cdx-button--fake-button cdx-button--fake-button--enabled' )
				// elements are right-floated so we use prepend instead of append to keep the order
				.prependTo( this.$buttonContainer );
		}

		/**
		 * Runs code for each button, similarly to $.each.
		 *
		 * @protected
		 * @param {function(jQuery, string)} callback a function that will be called with each button
		 */
		eachButton( callback ) {
			for ( const buttonName in this.buttons ) {
				callback( this.buttons[ buttonName ], buttonName );
			}
		}

		/**
		 * @inheritdoc
		 * @param {Image} imageInfo
		 * @param {Repo} repoInfo
		 */
		set( imageInfo, repoInfo ) {
			this.eachButton( ( $button ) => {
				$button.removeClass( 'empty' );
			} );

			this.setDescriptionPageButton( imageInfo, repoInfo );
		}

		/**
		 * Updates the button linking to the file page.
		 *
		 * @protected
		 * @param {Image} imageInfo
		 * @param {Repo} repoInfo
		 */
		setDescriptionPageButton( imageInfo, repoInfo ) {
			const $button = this.buttons.$descriptionPage;
			let isCommons = repoInfo.isCommons();
			let descriptionUrl = imageInfo.descriptionUrl;

			if ( repoInfo.isLocal === false && imageInfo.pageID ) {
				// The file has a local description page, override the description URL
				descriptionUrl = imageInfo.title.getUrl();
				isCommons = false;
			}

			$button.empty()
				.append( $( '<span>' ).addClass( 'cdx-button__icon' ) )
				.append( mw.message( 'multimediaviewer-repository-local' ).text() )
				.attr( 'href', descriptionUrl );

			$button.toggleClass( 'mw-mmv-repo-button-commons', isCommons );
		}

		/**
		 * @inheritdoc
		 */
		empty() {
			this.eachButton( ( $button ) => {
				$button.addClass( 'empty' );
			} );

			this.buttons.$descriptionPage.attr( { href: null, title: null, 'original-title': null } )
				.removeClass( 'mw-mmv-repo-button-commons' );
		}
	}

	module.exports = StripeButtons;
}() );
