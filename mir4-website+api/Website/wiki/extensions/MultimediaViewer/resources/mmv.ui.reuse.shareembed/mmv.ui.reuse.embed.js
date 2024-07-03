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

const { EmbedFileFormatter, Utils } = require( 'mmv.ui.ondemandshareddependencies' );
const Tab = require( './mmv.ui.reuse.tab.js' );

( function () {

	/**
	 * UI component that provides the user html/wikitext snippets needed to share
	 * and/or embed a media asset.
	 */
	class Embed extends Tab {
		/**
		 * @param {jQuery} $container
		 */
		constructor( $container ) {
			super( $container );

			/**
			 * Formatter converting image data into formats needed for output
			 *
			 * @property {EmbedFileFormatter}
			 */
			this.formatter = new EmbedFileFormatter();

			/** @property {Utils} utils - */
			this.utils = new Utils();

			/**
			 * Indicates whether or not the default option has been reset for both size menus.
			 *
			 * @property {boolean}
			 */
			this.isSizeMenuDefaultReset = false;

			this.$pane.addClass( 'mw-mmv-embed-pane' );

			this.$pane.appendTo( this.$container );

			this.createSnippetTextAreas( this.$pane );

			this.createSnippetSelectionButtons( this.$pane );
			this.createSizePulldownMenus( this.$pane );

			/**
			 * Currently selected embed snippet.
			 *
			 * @property {mw.widgets.CopyTextLayout}
			 */
			this.currentMainEmbedText = mw.user.isAnon() ? this.embedTextHtml : this.embedTextWikitext;

			/**
			 * Default item for the html size menu.
			 *
			 * @property {OO.ui.MenuOptionWidget}
			 */
			this.defaultHtmlItem = this.embedSizeSwitchHtml.getMenu().findSelectedItem();

			/**
			 * Default item for the wikitext size menu.
			 *
			 * @property {OO.ui.MenuOptionWidget}
			 */
			this.defaultWikitextItem = this.embedSizeSwitchWikitext.getMenu().findSelectedItem();

			/**
			 * Currently selected size menu.
			 *
			 * @property {OO.ui.MenuSelectWidget}
			 */
			this.currentSizeMenu = mw.user.isAnon() ? this.embedSizeSwitchHtml.getMenu() : this.embedSizeSwitchWikitext.getMenu();

			/**
			 * Current default item.
			 *
			 * @property {OO.ui.MenuOptionWidget}
			 */
			this.currentDefaultItem = mw.user.isAnon() ? this.defaultHtmlItem : this.defaultWikitextItem;
		}

		/**
		 * Creates text areas for html and wikitext snippets.
		 *
		 * @param {jQuery} $container
		 */
		createSnippetTextAreas( $container ) {
			this.embedTextHtml = new mw.widgets.CopyTextLayout( {
				help: mw.message( 'multimediaviewer-embed-explanation' ).text(),
				helpInline: true,
				align: 'top',
				multiline: true,
				textInput: {
					placeholder: mw.message( 'multimediaviewer-reuse-loading-placeholder' ).text(),
					autosize: true,
					maxRows: 5
				},
				button: {
					title: mw.msg( 'multimediaviewer-reuse-copy-embed' )
				}
			} );

			this.embedTextWikitext = new mw.widgets.CopyTextLayout( {
				help: mw.message( 'multimediaviewer-embed-explanation' ).text(),
				helpInline: true,
				align: 'top',
				multiline: true,
				textInput: {
					// The following classes are used here:
					// * mw-editfont-monospace
					// * mw-editfont-sans-serif
					// * mw-editfont-serif
					classes: [ `mw-editfont-${ mw.user.options.get( 'editfont' ) }` ],
					placeholder: mw.message( 'multimediaviewer-reuse-loading-placeholder' ).text(),
					autosize: true,
					maxRows: 5
				},
				button: {
					title: mw.msg( 'multimediaviewer-reuse-copy-embed' )
				}
			} );

			$container.append(
				this.embedTextHtml.$element,
				this.embedTextWikitext.$element
			);
		}

		/**
		 * Creates snippet selection buttons.
		 *
		 * @param {jQuery} $container
		 */
		createSnippetSelectionButtons( $container ) {
			this.embedSwitch = new OO.ui.ButtonSelectWidget( {
				classes: [ 'mw-mmv-embed-select' ]
			} );

			const wikitextButtonOption = new OO.ui.ButtonOptionWidget( {
				data: 'wikitext',
				label: mw.message( 'multimediaviewer-embed-wt' ).text()
			} );
			const htmlButtonOption = new OO.ui.ButtonOptionWidget( {
				data: 'html',
				label: mw.message( 'multimediaviewer-embed-html' ).text()
			} );

			this.embedSwitch.addItems( [
				wikitextButtonOption,
				htmlButtonOption
			] );

			$( '<p>' )
				.append( this.embedSwitch.$element )
				.appendTo( $container );

			// Logged-out defaults to 'html', logged-in to 'wikitext'
			this.embedSwitch.selectItem( mw.user.isAnon() ? htmlButtonOption : wikitextButtonOption );
		}

		/**
		 * Creates pulldown menus to select file sizes.
		 *
		 * @param {jQuery} $container
		 */
		createSizePulldownMenus( $container ) {
			// Wikitext sizes pulldown menu
			this.embedSizeSwitchWikitext = this.utils.createPulldownMenu(
				[ 'default', 'small', 'medium', 'large' ],
				[],
				'default'
			);

			// Html sizes pulldown menu
			this.embedSizeSwitchHtml = this.utils.createPulldownMenu(
				[ 'small', 'medium', 'large', 'original' ],
				[],
				'original'
			);

			this.embedSizeSwitchHtmlLayout = new OO.ui.FieldLayout( this.embedSizeSwitchHtml, { align: 'top' } );
			this.embedSizeSwitchWikitextLayout = new OO.ui.FieldLayout( this.embedSizeSwitchWikitext, { align: 'top' } );

			$container.append(
				this.embedSizeSwitchHtmlLayout.$element,
				this.embedSizeSwitchWikitextLayout.$element
			);
		}

		/**
		 * Registers listeners.
		 */
		attach() {
			// Register handler for switching between wikitext/html snippets
			this.embedSwitch.on( 'select', this.handleTypeSwitch.bind( this ) );

			this.handleTypeSwitch( this.embedSwitch.findSelectedItem() );

			// Register handlers for switching between file sizes
			this.embedSizeSwitchHtml.getMenu().on( 'choose', this.handleSizeSwitch.bind( this ) );
			this.embedSizeSwitchWikitext.getMenu().on( 'choose', this.handleSizeSwitch.bind( this ) );
		}

		/**
		 * Clears listeners.
		 */
		unattach() {
			super.unattach();

			this.embedSwitch.off( 'select' );
			this.embedSizeSwitchHtml.getMenu().off( 'choose' );
			this.embedSizeSwitchWikitext.getMenu().off( 'choose' );
		}

		/**
		 * Handles size menu change events.
		 *
		 * @param {OO.ui.MenuOptionWidget} item
		 */
		handleSizeSwitch( item ) {
			const value = item.getData();

			this.changeSize( value.width, value.height );
		}

		/**
		 * Handles snippet type switch.
		 *
		 * @param {OO.ui.MenuOptionWidget} item
		 */
		handleTypeSwitch( item ) {
			const value = item.getData();

			if ( value === 'html' ) {
				this.currentMainEmbedText = this.embedTextHtml;
				this.embedSizeSwitchWikitext.getMenu().toggle( false );

				this.currentSizeMenu = this.embedSizeSwitchHtml.getMenu();
				this.currentDefaultItem = this.defaultHtmlItem;
			} else if ( value === 'wikitext' ) {
				this.currentMainEmbedText = this.embedTextWikitext;
				this.embedSizeSwitchHtml.getMenu().toggle( false );

				this.currentSizeMenu = this.embedSizeSwitchWikitext.getMenu();
				this.currentDefaultItem = this.defaultWikitextItem;
			}

			this.embedTextHtml.toggle( value === 'html' );
			this.embedSizeSwitchHtmlLayout.toggle( value === 'html' );

			this.embedTextWikitext.toggle( value === 'wikitext' );
			this.embedSizeSwitchWikitextLayout.toggle( value === 'wikitext' );

			// Reset current selection to default when switching the first time
			if ( !this.isSizeMenuDefaultReset ) {
				this.resetCurrentSizeMenuToDefault();
				this.isSizeMenuDefaultReset = true;
			}

			this.select();
		}

		/**
		 * Reset current menu selection to default item.
		 */
		resetCurrentSizeMenuToDefault() {
			this.currentSizeMenu.chooseItem( this.currentDefaultItem );
			// Force select logic to update the selected item bar, otherwise we end up
			// with the wrong label. This is implementation dependent and maybe it should
			// be done via a to flag to OO.ui.SelectWidget.prototype.chooseItem()?
			this.currentSizeMenu.emit( 'select', this.currentDefaultItem );
		}

		/**
		 * Changes the size, takes different actions based on which sort of
		 * embed is currently chosen.
		 *
		 * @param {number} width New width to set
		 * @param {number} height New height to set
		 */
		changeSize( width, height ) {
			const currentItem = this.embedSwitch.findSelectedItem();

			if ( currentItem === null ) {
				return;
			}

			switch ( currentItem.getData() ) {
				case 'html':
					this.updateEmbedHtml( {}, width, height );
					break;
				case 'wikitext':
					this.updateEmbedWikitext( width );
					break;
			}

			this.select();
		}

		/**
		 * Sets the HTML embed text.
		 *
		 * Assumes that the set() method has already been called to update this.embedFileInfo
		 *
		 * @param {Thumbnail} thumbnail (can be just an empty object)
		 * @param {number} width New width to set
		 * @param {number} height New height to set
		 */
		updateEmbedHtml( thumbnail, width, height ) {
			if ( !this.embedFileInfo ) {
				return;
			}

			let src = thumbnail.url || this.embedFileInfo.imageInfo.url;

			// If the image dimension requested are "large", use the current image url
			if ( width > Embed.LARGE_IMAGE_WIDTH_THRESHOLD || height > Embed.LARGE_IMAGE_HEIGHT_THRESHOLD ) {
				src = this.embedFileInfo.imageInfo.url;
			}

			this.embedTextHtml.textInput.setValue(
				this.formatter.getThumbnailHtml( this.embedFileInfo, src, width, height )
			);
		}

		/**
		 * Updates the wikitext embed text with a new value for width.
		 *
		 * Assumes that the set method has already been called.
		 *
		 * @param {number} width
		 */
		updateEmbedWikitext( width ) {
			if ( !this.embedFileInfo ) {
				return;
			}

			this.embedTextWikitext.textInput.setValue(
				this.formatter.getThumbnailWikitextFromEmbedFileInfo( this.embedFileInfo, width )
			);
		}

		/**
		 * Shows the pane.
		 */
		show() {
			super.show();

			// Force update size on multiline inputs, as they may have be
			// calculated while not visible.
			this.currentMainEmbedText.textInput.valCache = null;
			this.currentMainEmbedText.textInput.adjustSize();

			this.select();
		}

		/**
		 * Gets size options for html and wikitext snippets.
		 *
		 * @param {number} width
		 * @param {number} height
		 * @return {Object}
		 * @return {Object} return.html Collection of possible image sizes for html snippets
		 * @return {Object} return.wikitext Collection of possible image sizes for wikitext snippets
		 */
		getSizeOptions( width, height ) {
			const sizes = {};

			sizes.html = this.utils.getPossibleImageSizesForHtml( width, height );
			sizes.wikitext = this.getPossibleImageSizesForWikitext( width, height );

			return sizes;
		}

		/**
		 * Sets the data on the element.
		 *
		 * @param {Image} image
		 * @param {Repo} repo
		 * @param {string} [caption]
		 * @param {string} [alt]
		 */
		set( image, repo, caption, alt ) {
			const htmlSizeSwitch = this.embedSizeSwitchHtml.getMenu();
			const htmlSizeOptions = htmlSizeSwitch.getItems();
			const wikitextSizeSwitch = this.embedSizeSwitchWikitext.getMenu();
			const wikitextSizeOptions = wikitextSizeSwitch.getItems();
			const sizes = this.getSizeOptions( image.width, image.height );

			this.embedFileInfo = { imageInfo: image, repoInfo: repo };
			if ( caption ) {
				this.embedFileInfo.caption = caption;
			}
			if ( alt ) {
				this.embedFileInfo.alt = alt;
			}

			this.utils.updateMenuOptions( sizes.html, htmlSizeOptions );
			this.utils.updateMenuOptions( sizes.wikitext, wikitextSizeOptions );

			// Reset defaults
			this.isSizeMenuDefaultReset = false;
			this.resetCurrentSizeMenuToDefault();

			this.utils.getThumbnailUrlPromise( this.LARGE_IMAGE_WIDTH_THRESHOLD )
				.done( ( thumbnail ) => {
					this.updateEmbedHtml( thumbnail );
					this.select();
				} );
		}

		/**
		 * @inheritdoc
		 */
		empty() {
			this.embedTextHtml.textInput.setValue( '' );
			this.embedTextWikitext.textInput.setValue( '' );

			this.embedSizeSwitchHtml.getMenu().toggle( false );
			this.embedSizeSwitchWikitext.getMenu().toggle( false );
		}

		/**
		 * Selects the text in the current textbox by triggering a focus event.
		 */
		select() {
			this.currentMainEmbedText.selectText();
		}

		/**
		 * Calculates possible image sizes for wikitext snippets. It returns up to
		 * three possible snippet frame sizes (small, medium, large).
		 *
		 * @param {number} width
		 * @param {number} height
		 * @return {Object}
		 * @return {Object} return.small
		 * @return {Object} return.medium
		 * @return {Object} return.large
		 */
		getPossibleImageSizesForWikitext( width, height ) {
			const buckets = {
				small: 300,
				medium: 400,
				large: 500
			};
			const sizes = {};
			const widthToHeight = height / width;

			for ( const bucketName in buckets ) {
				const bucketWidth = buckets[ bucketName ];

				if ( width > bucketWidth ) {
					sizes[ bucketName ] = {
						width: bucketWidth,
						height: Math.round( bucketWidth * widthToHeight )
					};
				}
			}

			sizes.default = { width: null, height: null };

			return sizes;
		}
	}

	/**
	 * @property {number} Width threshold at which an image is to be considered "large"
	 * @static
	 */
	Embed.LARGE_IMAGE_WIDTH_THRESHOLD = 1200;

	/**
	 * @property {number} Height threshold at which an image is to be considered "large"
	 * @static
	 */
	Embed.LARGE_IMAGE_HEIGHT_THRESHOLD = 900;

	module.exports = Embed;
}() );
