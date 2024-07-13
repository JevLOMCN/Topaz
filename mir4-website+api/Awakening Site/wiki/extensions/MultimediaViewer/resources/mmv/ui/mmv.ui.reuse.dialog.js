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
	 * Represents the file reuse dialog and the link to open it.
	 */
	class ReuseDialog extends Dialog {
		/**
		 * @param {jQuery} $container the element to which the dialog will be appended
		 * @param {jQuery} $openButton the button which opens the dialog. Only used for positioning.
		 * @param {Config} config
		 */
		constructor( $container, $openButton, config ) {
			super( $container, $openButton, config );

			/**
			 * @property {Object.<string, UiElement>} tabs List of tab ui objects.
			 */
			this.tabs = null;

			/**
			 * @property {Object.<string, OO.ui.MenuOptionWidget>} ooTabs List of tab OOUI objects.
			 */
			this.ooTabs = null;

			this.loadDependencies.push( 'mmv.ui.reuse.shareembed' );

			this.$dialog.addClass( 'mw-mmv-reuse-dialog' );

			this.eventPrefix = 'use-this-file';
		}

		// FIXME this should happen outside the dialog and the tabs, but we need to improve
		initTabs() {
			const makeTab = ( type ) => new OO.ui.MenuOptionWidget( {
				data: type,
				// The following messages are used here:
				// * multimediaviewer-embed-tab
				// * multimediaviewer-share-tab
				label: mw.message( `multimediaviewer-${ type }-tab` ).text()
			} );

			this.reuseTabs = new OO.ui.MenuSelectWidget( {
				autoHide: false,
				classes: [ 'mw-mmv-reuse-tabs' ]
			} );
			this.reuseTabs.$element.appendTo( this.$dialog );

			const { Embed, Share } = require( 'mmv.ui.reuse.shareembed' );
			this.tabs = {
				share: new Share( this.$dialog ),
				embed: new Embed( this.$dialog )
			};

			this.ooTabs = {
				share: makeTab( 'share' ),
				embed: makeTab( 'embed' )
			};

			this.reuseTabs.addItems( [
				this.ooTabs.share,
				this.ooTabs.embed
			] );

			// MenuSelectWidget has a nasty tendency to hide itself, maybe we're not using it right?
			this.reuseTabs.toggle( true );
			this.reuseTabs.toggle = () => { };

			this.selectedTab = this.getLastUsedTab();

			// In case nothing is saved in localStorage or it contains junk
			if ( !Object.prototype.hasOwnProperty.call( this.tabs, this.selectedTab ) ) {
				this.selectedTab = 'share';
			}

			this.reuseTabs.selectItem( this.ooTabs[ this.selectedTab ] );

			if ( this.dependenciesNeedToBeAttached ) {
				this.attachDependencies();
			}

			if ( this.tabsSetValues ) {
				// This is a delayed set() for the elements we've just created on demand
				this.tabs.share.set.apply( this.tabs.share, this.tabsSetValues.share );
				this.tabs.embed.set.apply( this.tabs.embed, this.tabsSetValues.embed );
				this.showImageWarnings( this.tabsSetValues.share[ 0 ] );
				this.tabsSetValues = undefined;
			}
		}

		toggleDialog() {
			if ( this.tabs === null ) {
				this.initTabs();
			}

			super.toggleDialog();
		}

		/**
		 * Handles tab selection.
		 *
		 * @param {OO.ui.MenuOptionWidget} option
		 */
		handleTabSelection( option ) {
			this.selectedTab = option.getData();

			for ( const tab in this.tabs ) {
				if ( tab === this.selectedTab ) {
					this.tabs[ tab ].show();
				} else {
					this.tabs[ tab ].hide();
				}
			}

			this.config.setInLocalStorage( 'mmv-lastUsedTab', this.selectedTab );
		}

		/**
		 * @return {string} Last used tab
		 */
		getLastUsedTab() {
			return this.config.getFromLocalStorage( 'mmv-lastUsedTab' );
		}

		/**
		 * Registers listeners.
		 */
		attach() {
			this.handleEvent( 'mmv-reuse-open', this.handleOpenCloseClick.bind( this ) );

			this.handleEvent( 'mmv-download-open', this.closeDialog.bind( this ) );
			this.handleEvent( 'mmv-options-open', this.closeDialog.bind( this ) );

			this.attachDependencies();
		}

		/**
		 * Registers listeners for dependencies loaded on demand
		 */
		attachDependencies() {
			if ( this.reuseTabs && this.tabs ) {
				// This is a delayed attach() for the elements we've just created on demand
				this.reuseTabs.on( 'select', this.handleTabSelection.bind( this ) );

				for ( const tab in this.tabs ) {
					this.tabs[ tab ].attach();
				}

				this.dependenciesNeedToBeAttached = false;
			} else {
				this.dependenciesNeedToBeAttached = true;
			}
		}

		/**
		 * Clears listeners.
		 */
		unattach() {
			super.unattach();

			if ( this.reuseTabs ) {
				this.reuseTabs.off( 'select' );
			}

			if ( this.tabs ) {
				for ( const tab in this.tabs ) {
					this.tabs[ tab ].unattach();
				}
			}
		}

		/**
		 * Sets data needed by contained tabs and makes dialog launch link visible.
		 *
		 * @param {Image} image
		 * @param {Repo} repo
		 * @param {string} caption
		 * @param {string} alt
		 */
		set( image, repo, caption, alt ) {
			if ( this.tabs !== null ) {
				this.tabs.share.set( image );
				this.tabs.embed.set( image, repo, caption, alt );
				this.tabs.embed.set( image, repo, caption );
				this.showImageWarnings( image );
			} else {
				this.tabsSetValues = {
					share: [ image ],
					embed: [ image, repo, caption, alt ]
				};
			}
		}

		/**
		 * @inheritdoc
		 */
		empty() {
			super.empty();

			for ( const tab in this.tabs ) {
				this.tabs[ tab ].empty();
			}
		}

		/**
		 * Fired when the dialog is opened.
		 *
		 * @event ReuseDialog#mmv-reuse-opened
		 */

		/**
		 * Opens a dialog with information about file reuse.
		 */
		openDialog() {
			super.openDialog();

			// move warnings after the tabs
			this.$warning.insertAfter( this.reuseTabs.$element );

			this.tabs[ this.selectedTab ].show();

			$( document ).trigger( 'mmv-reuse-opened' );
		}

		/**
		 * Fired when the dialog is closed.
		 *
		 * @event ReuseDialog#mmv-reuse-closed
		 */

		/**
		 * Closes the reuse dialog.
		 */
		closeDialog() {
			super.closeDialog();

			$( document ).trigger( 'mmv-reuse-closed' );
		}
	}

	module.exports = ReuseDialog;
}() );
