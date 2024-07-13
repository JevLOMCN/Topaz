/*!
 * VisualEditor UserInterface MWMobileSaveDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for saving MediaWiki pages in mobile.
 *
 * TODO: Currently this does no overriding so could be removed, but we may want
 * to customise the mobile save dialog in the near future.
 *
 * @class
 * @extends ve.ui.MWSaveDialog
 *
 * @constructor
 * @param {Object} [config] Config options
 */
ve.ui.MWMobileSaveDialog = function VeUiMwMobileSaveDialog() {
	// Parent constructor
	ve.ui.MWMobileSaveDialog.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwMobileSaveDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMobileSaveDialog, ve.ui.MWSaveDialog );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMobileSaveDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWMobileSaveDialog.super.prototype.initialize.call( this );

	this.$reviewVisualDiff.addClass( 'content' );
	this.previewPanel.$element.addClass( 'content' );

	mw.loader.using( 'mobile.startup' ).then( function ( req ) {
		var licenseMsg = req( 'mobile.startup' ).license();
		if ( licenseMsg ) {
			// eslint-disable-next-line no-jquery/no-html
			this.$license.html( licenseMsg );
		}
	} );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMobileSaveDialog );
