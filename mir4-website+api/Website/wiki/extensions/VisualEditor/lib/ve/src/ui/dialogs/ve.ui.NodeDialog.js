/*!
 * VisualEditor user interface NodeDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for working with a node.
 *
 * @class
 * @extends ve.ui.FragmentDialog
 * @mixins ve.ui.NodeWindow
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.NodeDialog = function VeUiNodeDialog( config ) {
	// Parent constructor
	ve.ui.NodeDialog.super.call( this, config );

	// Mixin constructor
	ve.ui.NodeWindow.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.NodeDialog, ve.ui.FragmentDialog );

OO.mixinClass( ve.ui.NodeDialog, ve.ui.NodeWindow );

/**
 * @inheritdoc
 */
ve.ui.NodeDialog.prototype.initialize = function ( data ) {
	// Parent method
	ve.ui.NodeDialog.super.prototype.initialize.call( this, data );

	// Initialization
	this.$content.addClass( 've-ui-nodeDialog' );
};

/**
 * @inheritdoc
 */
ve.ui.NodeDialog.prototype.getSetupProcess = function ( data ) {
	// Parent method
	var process = ve.ui.NodeDialog.super.prototype.getSetupProcess.call( this, data );
	// Mixin method
	return ve.ui.NodeWindow.prototype.getSetupProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.NodeDialog.prototype.getTeardownProcess = function ( data ) {
	// Parent method
	var process = ve.ui.NodeDialog.super.prototype.getTeardownProcess.call( this, data );
	// Mixin method
	return ve.ui.NodeWindow.prototype.getTeardownProcess.call( this, data, process );
};
