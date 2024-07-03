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

const { isMediaViewerEnabledOnClick } = require( 'mmv.head' );
const Dialog = require( './mmv.ui.dialog.js' );

( function () {

	/**
	 * Represents the viewing options dialog and the link to open it.
	 */
	class OptionsDialog extends Dialog {
		/**
		 * @param {jQuery} $container the element to which the dialog will be appended
		 * @param {jQuery} $openButton the button which opens the dialog. Only used for positioning.
		 * @param {Config} config
		 */
		constructor( $container, $openButton, config ) {
			super( $container, $openButton, config );

			this.$dialog.addClass( 'mw-mmv-options-dialog' );
			this.eventPrefix = 'options';

			this.initPanel();
		}

		attach() {
			this.handleEvent( 'mmv-options-open', this.handleOpenCloseClick.bind( this ) );

			this.handleEvent( 'mmv-reuse-open', this.closeDialog.bind( this ) );
			this.handleEvent( 'mmv-download-open', this.closeDialog.bind( this ) );
		}

		/**
		 * Initialises UI elements.
		 */
		initPanel() {
			this.initEnableConfirmation();
			this.initDisableConfirmation();
			this.initEnableDiv();
			this.initDisableDiv();
		}

		/**
		 * Initialises the enable confirmation pane.
		 */
		initEnableConfirmation() {
			this.createConfirmationPane(
				'mw-mmv-enable-confirmation',
				'$enableConfirmation',
				[
					mw.message( 'multimediaviewer-enable-confirmation-header' ).text(),
					mw.message( 'multimediaviewer-enable-confirmation-text', mw.config.get( 'wgSiteName' ) ).text()
				] );
		}

		/**
		 * Initialises the disable confirmation pane.
		 */
		initDisableConfirmation() {
			this.createConfirmationPane(
				'mw-mmv-disable-confirmation',
				'$disableConfirmation',
				[
					mw.message( 'multimediaviewer-disable-confirmation-header' ).text(),
					mw.message( 'multimediaviewer-disable-confirmation-text', mw.config.get( 'wgSiteName' ) ).text()
				] );
		}

		/**
		 * Initialises the enable action pane.
		 */
		initEnableDiv() {
			this.createActionPane(
				'mw-mmv-options-enable',
				'$enableDiv',
				mw.message( 'multimediaviewer-enable-submit-button' ).text(),
				[
					mw.message( 'multimediaviewer-enable-dialog-header' ).text(),
					mw.message( 'multimediaviewer-enable-text-header' ).text()
				], true );
		}

		/**
		 * Initialises the disable action pane.
		 */
		initDisableDiv() {
			this.createActionPane(
				'mw-mmv-options-disable',
				'$disableDiv',
				mw.message( 'multimediaviewer-option-submit-button' ).text(),
				[
					mw.message( 'multimediaviewer-options-dialog-header' ).text(),
					mw.message( 'multimediaviewer-options-text-header' ).text(),
					mw.message( 'multimediaviewer-options-text-body' ).text()
				], false );
		}

		/**
		 * Hides all of the divs.
		 */
		hideDivs() {
			this.$dialog.removeClass( 'mw-mmv-disable-confirmation-shown mw-mmv-enable-confirmation-shown mw-mmv-enable-div-shown' );

			this.$disableDiv
				.add( this.$disableConfirmation )
				.add( this.$enableDiv )
				.add( this.$enableConfirmation )
				.removeClass( 'mw-mmv-shown' );
		}

		/**
		 * Shows the confirmation div for the disable action.
		 */
		showDisableConfirmation() {
			this.hideDivs();
			this.$disableConfirmation.addClass( 'mw-mmv-shown' );
			this.$dialog.addClass( 'mw-mmv-disable-confirmation-shown' );
		}

		/**
		 * Shows the confirmation div for the enable action.
		 */
		showEnableConfirmation() {
			this.hideDivs();
			this.$enableConfirmation.addClass( 'mw-mmv-shown' );
			this.$dialog.addClass( 'mw-mmv-enable-confirmation-shown' );
		}

		/**
		 * Fired when the dialog is opened.
		 *
		 * @event OptionsDialog#mmv-options-opened
		 */

		/**
		 * Opens a dialog with information about file reuse.
		 */
		openDialog() {
			if ( isMediaViewerEnabledOnClick() ) {
				this.$disableDiv.addClass( 'mw-mmv-shown' );
			} else {
				this.$enableDiv.addClass( 'mw-mmv-shown' );
				this.$dialog.addClass( 'mw-mmv-enable-div-shown' );
			}

			super.openDialog();
			$( document ).trigger( 'mmv-options-opened' );
		}

		/**
		 * Fired when the dialog is closed.
		 *
		 * @event OptionsDialog#mmv-options-closed
		 */

		/**
		 * Closes the options dialog.
		 *
		 * @param {Event} [e] Event object when the close action is caused by a user
		 *   action, as opposed to closing the window or something.
		 */
		closeDialog( e ) {
			const wasConfirmation = this.$dialog.is( '.mw-mmv-disable-confirmation-shown' ) || this.$dialog.is( '.mw-mmv-enable-confirmation-shown' );

			super.closeDialog();
			$( document ).trigger( 'mmv-options-closed' );
			this.hideDivs();

			if ( e && $( e.target ).is( '.mw-mmv-options-button' ) && wasConfirmation ) {
				this.openDialog();
			}
		}

		/**
		 * Creates a confirmation pane.
		 *
		 * @param {string} divClass Class applied to main div.
		 * @param {string} propName Name of the property on this object to which we'll assign the div.
		 * @param {string} msgs See #addText
		 */
		createConfirmationPane( divClass, propName, msgs ) {
			const $div = $( '<div>' )
				// The following classes are used here:
				// * mw-mmv-enable-confirmation
				// * mw-mmv-disable-confirmation
				.addClass( divClass )
				.appendTo( this.$dialog );

			$( '<div>' )
				.text( '\u00A0' )
				.addClass( 'mw-mmv-confirmation-close' )
				.on( 'click', () => this.closeDialog() )
				.appendTo( $div );

			this.addText( $div, msgs );

			this[ propName ] = $div;
		}

		/**
		 * Creates an action pane.
		 *
		 * @param {string} divClass Class applied to main div.
		 * @param {string} propName Name of the property on this object to which we'll assign the div.
		 * @param {string} smsg Message for the submit button.
		 * @param {string} msgs See #addText
		 * @param {boolean} enabled Whether this dialog is an enable one.
		 */
		createActionPane( divClass, propName, smsg, msgs, enabled ) {
			const $div = $( '<div>' )
				// The following classes are used here:
				// * mw-mmv-options-enable
				// * mw-mmv-options-disable
				.addClass( divClass )
				.appendTo( this.$dialog );

			if ( enabled ) {
				$( '<div>' )
					.addClass( 'mw-mmv-options-enable-alert' )
					.text( mw.message( 'multimediaviewer-enable-alert' ).text() )
					.appendTo( $div );
			}

			this.addText( $div, msgs, true );
			this.addInfoLink( $div );
			this.makeButtons( $div, smsg, enabled );

			this[ propName ] = $div;
		}

		/**
		 * Creates buttons for the dialog.
		 *
		 * @param {jQuery} $container
		 * @param {string} smsg Message for the submit button.
		 * @param {boolean} enabled Whether the viewer is enabled after this dialog is submitted.
		 */
		makeButtons( $container, smsg, enabled ) {
			const $submitDiv = $( '<div>' )
				.addClass( 'mw-mmv-options-submit' )
				.appendTo( $container );

			this.makeSubmitButton(
				$submitDiv,
				smsg,
				enabled
			);

			this.makeCancelButton( $submitDiv );
		}

		/**
		 * Makes a submit button for one of the panels.
		 *
		 * @param {jQuery} $submitDiv The div for the buttons in the dialog.
		 * @param {string} msg The string to put in the button.
		 * @param {boolean} enabled Whether to turn the viewer on or off when this button is pressed.
		 * @return {jQuery} Submit button
		 */
		makeSubmitButton( $submitDiv, msg, enabled ) {
			return $( '<button>' )
				.addClass( 'mw-mmv-options-submit-button cdx-button cdx-button--action-progressive cdx-button--weight-primary' )
				.text( msg )
				.appendTo( $submitDiv )
				.on( 'click', () => {
					const $buttons = $( this ).closest( '.mw-mmv-options-submit' ).find( '.mw-mmv-options-submit-button, .mw-mmv-options-cancel-button' );
					$buttons.prop( 'disabled', true );

					this.config.setMediaViewerEnabledOnClick( enabled ).done( () => {
						if ( enabled ) {
							this.showEnableConfirmation();
						} else {
							this.showDisableConfirmation();
						}
					} ).always( () => {
						$buttons.prop( 'disabled', false );
					} );

					return false;
				} );
		}

		/**
		 * Makes a cancel button for one of the panels.
		 *
		 * @param {jQuery} $submitDiv The div for the buttons in the dialog.
		 * @return {jQuery} Cancel button
		 */
		makeCancelButton( $submitDiv ) {
			return $( '<button>' )
				.addClass( 'mw-mmv-options-cancel-button cdx-button cdx-button--weight-quiet' )
				.text( mw.message( 'multimediaviewer-option-cancel-button' ).text() )
				.appendTo( $submitDiv )
				.on( 'click', () => {
					this.closeDialog();
					return false;
				} );
		}

		/**
		 * Adds text to a dialog.
		 *
		 * @param {jQuery} $container
		 * @param {string[]} msgs The messages to be added.
		 * @param {boolean} icon Whether to display an icon next to the text or not
		 */
		addText( $container, msgs, icon ) {
			const $text = $( '<div>' )
				.addClass( 'mw-mmv-options-text' );

			const adders = [
				( msg ) => {
					$( '<h3>' )
						.text( msg )
						.addClass( 'mw-mmv-options-dialog-header' )
						.appendTo( $container );
				},

				( msg ) => {
					$( '<p>' )
						.text( msg )
						.addClass( 'mw-mmv-options-text-header' )
						.appendTo( $text );
				},

				( msg ) => {
					$( '<p>' )
						.text( msg )
						.addClass( 'mw-mmv-options-text-body' )
						.appendTo( $text );
				}
			];

			for ( let i = 0; i < msgs.length && i < adders.length; i++ ) {
				adders[ i ]( msgs[ i ] );
			}

			if ( icon ) {
				const $subContainer = $( '<div>' ).addClass( 'mw-mmv-options-subcontainer' );

				$( '<div>' )
					.text( '\u00A0' )
					.addClass( 'mw-mmv-options-icon' )
					.appendTo( $subContainer );

				$text.appendTo( $subContainer );
				$subContainer.appendTo( $container );
			} else {
				$text.appendTo( $container );
			}
		}

		/**
		 * Adds the info link to the panel.
		 *
		 * @param {jQuery} $div The panel to which we're adding the link.
		 */
		addInfoLink( $div ) {
			$( '<a>' )
				.addClass( 'mw-mmv-project-info-link' )
				.prop( 'href', mw.config.get( 'wgMultimediaViewer' ).helpLink )
				.text( mw.message( 'multimediaviewer-options-learn-more' ) )
				.appendTo( $div.find( '.mw-mmv-options-text' ) );
		}
	}

	module.exports = OptionsDialog;
	module.exports = OptionsDialog;
}() );
