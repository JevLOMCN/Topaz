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

const Dialog = require( './mmv.ui.dialog.js' );

( function () {

	/**
	 * Represents the file download dialog and the link to open it.
	 */
	class DownloadDialog extends Dialog {
		/**
		 * @param {jQuery} $container the element to which the dialog will be appended
		 * @param {jQuery} $openButton the button which opens the dialog. Only used for positioning.
		 * @param {Config} config
		 */
		constructor( $container, $openButton, config ) {
			super( $container, $openButton, config );

			this.loadDependencies.push( 'mmv.ui.download.pane' );

			this.$dialog.addClass( 'mw-mmv-download-dialog' );

			this.eventPrefix = 'download';
		}

		/**
		 * Registers listeners.
		 */
		attach() {
			this.handleEvent( 'mmv-download-open', this.handleOpenCloseClick.bind( this ) );

			this.handleEvent( 'mmv-reuse-open', this.closeDialog.bind( this ) );
			this.handleEvent( 'mmv-options-open', this.closeDialog.bind( this ) );

			this.$container.on( 'mmv-download-cta-open', () => this.$warning.hide() );
			this.$container.on( 'mmv-download-cta-close', () => {
				if ( this.$dialog.hasClass( 'mw-mmv-warning-visible' ) ) {
					this.$warning.show();
				}
			} );
		}

		/**
		 * Clears listeners.
		 */
		unattach() {
			super.unattach();

			this.$container.off( 'mmv-download-cta-open mmv-download-cta-close' );
		}

		/**
		 * Sets data needed by contained tabs and makes dialog launch link visible.
		 *
		 * @param {Image} image
		 * @param {Repo} repo
		 */
		set( image, repo ) {
			if ( this.download ) {
				this.download.set( image, repo );
				this.showImageWarnings( image );
			} else {
				this.setValues = {
					image: image,
					repo: repo
				};
			}
		}

		/**
		 * Fired when the dialog is opened.
		 *
		 * @event DownloadDialog#mmv-download-opened
		 */

		/**
		 * Opens a dialog with information about file download.
		 */
		openDialog() {
			if ( !this.download ) {
				const DownloadPane = require( 'mmv.ui.download.pane' );
				this.download = new DownloadPane( this.$dialog );
				this.download.attach();
			}

			if ( this.setValues ) {
				this.download.set( this.setValues.image, this.setValues.repo );
				this.showImageWarnings( this.setValues.image );
				this.setValues = undefined;
			}

			super.openDialog();

			$( document ).trigger( 'mmv-download-opened' );
		}

		/**
		 * Fired when the dialog is closed.
		 *
		 * @event DownloadDialog#mmv-download-closed
		 */

		/**
		 * Closes the download dialog.
		 */
		closeDialog() {
			super.closeDialog();

			$( document ).trigger( 'mmv-download-closed' );
		}
	}

	module.exports = DownloadDialog;
}() );
