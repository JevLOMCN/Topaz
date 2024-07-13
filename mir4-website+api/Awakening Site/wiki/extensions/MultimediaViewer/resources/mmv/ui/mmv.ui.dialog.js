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
	 * Represents a dialog and the link to open it.
	 */
	class Dialog extends UiElement {
		/**
		 * @param {jQuery} $container the element to which the dialog will be appended
		 * @param {jQuery} $openButton the button which opens the dialog. Only used for positioning.
		 * @param {Config} config
		 */
		constructor( $container, $openButton, config ) {
			super( $container );

			/** @property {boolean} isOpen Whether or not the dialog is open. */
			this.isOpen = false;

			/**
			 * @property {string[]} loadDependencies Dependencies to load before showing the dialog.
			 */
			this.loadDependencies = [];

			/**
			 * @property {string} eventPrefix Prefix specific to the class to be applied to events.
			 */
			this.eventPrefix = '';
			/** @property {Config} config - */
			this.config = config;

			/** @property {jQuery} $openButton The click target which opens the dialog. */
			this.$openButton = $openButton;

			/** @property {jQuery} $dialog The main dialog container */
			this.$dialog = $( '<div>' )
				.addClass( 'mw-mmv-dialog' );

			/**
			 * @property {jQuery} $downArrow Tip of the dialog pointing to $openButton. Called
			 * downArrow for historical reasons although it does not point down anymore.
			 */
			this.$downArrow = $( '<div>' )
				.addClass( 'mw-mmv-dialog-down-arrow' )
				.appendTo( this.$dialog );

			this.initWarning();

			this.$dialog.appendTo( this.$container );
		}

		/**
		 * Creates the DOM element that setWarning()/clearWarning() will operate on.
		 *
		 * @private
		 */
		initWarning() {
			this.$warning = $( '<div>' )
				.addClass( 'mw-mmv-dialog-warning' )
				.hide()
				.on( 'click', ( e ) => {
					// prevent other click handlers such as the download CTA from intercepting clicks at the warning
					e.stopPropagation();
				} )
				.appendTo( this.$dialog );
		}

		/**
		 * Handles click on link that opens/closes the dialog.
		 *
		 * @param {jQuery.Event} openEvent Event object for the mmv-$dialog-open event.
		 * @param {jQuery.Event} e Event object for the click event.
		 * @return {boolean} False to cancel the default event
		 */
		handleOpenCloseClick( openEvent, e ) {
			mw.loader.using( this.loadDependencies, () => {
				this.dependenciesLoaded = true;
				this.toggleDialog( e );
			}, ( error ) => {
				mw.log.error( 'mw.loader.using error when trying to load dialog dependencies', error );
			} );

			return false;
		}

		/**
		 * Toggles the open state on the dialog.
		 *
		 * @param {jQuery.Event} [e] Event object when the close action is caused by a user
		 *   action, as opposed to closing the window or something.
		 */
		toggleDialog( e ) {
			if ( this.isOpen ) {
				this.closeDialog( e );
			} else {
				this.openDialog();
			}
		}

		/**
		 * Opens a dialog.
		 */
		openDialog() {
			this.startListeningToOutsideClick();
			this.$dialog.show();
			this.isOpen = true;
			this.$openButton.addClass( 'mw-mmv-dialog-open' );
		}

		/**
		 * Closes a dialog.
		 */
		closeDialog() {
			this.stopListeningToOutsideClick();
			this.$dialog.hide();
			this.isOpen = false;
			this.$openButton.removeClass( 'mw-mmv-dialog-open' );
		}

		/**
		 * Sets up the event handler which closes the dialog when the user clicks outside.
		 */
		startListeningToOutsideClick() {
			this.outsideClickHandler = this.outsideClickHandler || ( ( e ) => {
				const $clickTarget = $( e.target );

				// Don't close the dialog if the click inside a dialog or on an navigation arrow
				if (
					$clickTarget.closest( this.$dialog ).length ||
					$clickTarget.closest( '.mw-mmv-next-image' ).length ||
					$clickTarget.closest( '.mw-mmv-prev-image' ).length ||
					e.which === 3
				) {
					return;
				}

				this.closeDialog();
				return false;
			} );
			$( document ).on( `click.mmv.${ this.eventPrefix }`, this.outsideClickHandler );
		}

		/**
		 * Removes the event handler set up by startListeningToOutsideClick().
		 */
		stopListeningToOutsideClick() {
			$( document ).off( `click.mmv.${ this.eventPrefix }`, this.outsideClickHandler );
		}

		/**
		 * Clears listeners.
		 */
		unattach() {
			super.unattach();

			this.stopListeningToOutsideClick();
		}

		/**
		 * @inheritdoc
		 */
		empty() {
			this.closeDialog();
			this.clearWarning();
		}

		/**
		 * Displays a warning ribbon.
		 *
		 * @param {string} content Content of the warning (can be HTML,
		 *   setWarning does no escaping).
		 */
		setWarning( content ) {
			this.$warning
				.empty()
				.append( content )
				.show();
			this.$dialog.addClass( 'mw-mmv-warning-visible' );
		}

		/**
		 * Removes the warning ribbon.
		 */
		clearWarning() {
			this.$warning.hide();
			this.$dialog.removeClass( 'mw-mmv-warning-visible' );
		}

		/**
		 * @param {Image} image
		 * @return {string[]}
		 */
		getImageWarnings( image ) {
			const warnings = [];

			if ( image.deletionReason ) {
				warnings.push( mw.message( 'multimediaviewer-reuse-warning-deletion' ).plain() );
				// Don't inform about other warnings (they may be the cause of the deletion)
				return warnings;
			}

			if ( !image.license || image.license.needsAttribution() && !image.author && !image.attribution ) {
				warnings.push( mw.message( 'multimediaviewer-reuse-warning-noattribution' ).plain() );
			}

			if ( image.license && !image.license.isFree() ) {
				warnings.push( mw.message( 'multimediaviewer-reuse-warning-nonfree' ).plain() );
			}

			return warnings;
		}

		/**
		 * @param {Image} image
		 */
		showImageWarnings( image ) {
			const warnings = this.getImageWarnings( image );

			if ( warnings.length > 0 ) {
				warnings.push( mw.message( 'multimediaviewer-reuse-warning-generic', image.descriptionUrl ).parse() );
				this.setWarning( warnings.join( '<br />' ) );
			} else {
				this.clearWarning();
			}
		}
	}

	module.exports = Dialog;
}() );
