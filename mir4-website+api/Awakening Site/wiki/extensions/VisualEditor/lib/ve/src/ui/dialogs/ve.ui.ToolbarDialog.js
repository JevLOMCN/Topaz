/*!
 * VisualEditor UserInterface ToolbarDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Toolbar dialog.
 *
 * @class
 * @abstract
 * @extends OO.ui.Dialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.ToolbarDialog = function VeUiToolbarDialog( config ) {
	// Parent constructor
	ve.ui.ToolbarDialog.super.call( this, config );

	// Properties
	this.disabled = false;

	// Pre-initialization
	// This class needs to exist before setup to constrain the height
	// of the dialog when it first loads.
	this.$element.addClass( 've-ui-toolbarDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.ToolbarDialog, OO.ui.Dialog );

/* Static Properties */

ve.ui.ToolbarDialog.static.size = 'full';

ve.ui.ToolbarDialog.static.padded = true;

/**
 * Toolbar position, either 'above', 'side' (right in LTR), 'below' or 'inline'
 * For 'inline' the caller will be manually positioning the dialog.
 *
 * @static
 * @type {string} Toolbar position
 */
ve.ui.ToolbarDialog.static.position = 'above';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.ToolbarDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.ToolbarDialog.super.prototype.initialize.call( this );

	this.$content.addClass( 've-ui-toolbarDialog-content' );
	// The following classes are used here:
	// * ve-ui-toolbarDialog-position-above
	// * ve-ui-toolbarDialog-position-side
	// * ve-ui-toolbarDialog-position-below
	// * ve-ui-toolbarDialog-position-inline
	this.$element.addClass( 've-ui-toolbarDialog-position-' + this.constructor.static.position );
	if ( this.constructor.static.padded ) {
		this.$element.addClass( 've-ui-toolbarDialog-padded' );
	}
	// Invisible title for accessibility
	this.title.setInvisibleLabel( true );
	this.$element.prepend( this.title.$element );
};

/**
 * Set the disabled state of the toolbar dialog
 *
 * @param {boolean} disabled Disable the dialog
 */
ve.ui.ToolbarDialog.prototype.setDisabled = function ( disabled ) {
	this.$content.addClass( 've-ui-toolbarDialog-content' );
	if ( disabled !== this.disabled ) {
		this.disabled = disabled;
		this.$body.toggleClass( 've-ui-toolbarDialog-disabled', this.disabled );
	}
};

/**
 * @inheritdoc
 */
ve.ui.ToolbarDialog.prototype.getTeardownProcess = function ( data ) {
	ve.track( 'activity.' + this.constructor.static.name, { action: 'dialog-' + ( data && data.action || 'abort' ) } );
	return ve.ui.ToolbarDialog.super.prototype.getTeardownProcess.apply( this, arguments );
};
