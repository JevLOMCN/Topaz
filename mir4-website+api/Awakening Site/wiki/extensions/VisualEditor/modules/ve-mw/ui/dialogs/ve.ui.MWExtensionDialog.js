/*!
 * VisualEditor UserInterface MWExtensionDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for editing generic MediaWiki extensions.
 *
 * @class
 * @abstract
 * @extends ve.ui.NodeDialog
 * @mixins ve.ui.MWExtensionWindow
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWExtensionDialog = function VeUiMWExtensionDialog() {
	// Parent constructor
	ve.ui.MWExtensionDialog.super.apply( this, arguments );

	// Mixin constructors
	ve.ui.MWExtensionWindow.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWExtensionDialog, ve.ui.NodeDialog );

OO.mixinClass( ve.ui.MWExtensionDialog, ve.ui.MWExtensionWindow );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWExtensionDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWExtensionDialog.super.prototype.initialize.call( this );

	// Mixin method
	ve.ui.MWExtensionWindow.prototype.initialize.call( this );

	// Initialization
	this.$element.addClass( 've-ui-mwExtensionDialog' );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	// Parent process
	var process = ve.ui.MWExtensionDialog.super.prototype.getSetupProcess.call( this, data );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getSetupProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionDialog.prototype.getReadyProcess = function ( data ) {
	data = data || {};
	// Parent process
	var process = ve.ui.MWExtensionDialog.super.prototype.getReadyProcess.call( this, data );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getReadyProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionDialog.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	// Parent process
	var process = ve.ui.MWExtensionDialog.super.prototype.getTeardownProcess.call( this, data );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getTeardownProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionDialog.prototype.getActionProcess = function ( action ) {
	// Parent process
	var process = ve.ui.MWExtensionDialog.super.prototype.getActionProcess.call( this, action );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getActionProcess.call( this, action, process ).next( function () {
		if ( action === 'done' ) {
			this.close( { action: 'done' } );
		}
	}, this );
};
