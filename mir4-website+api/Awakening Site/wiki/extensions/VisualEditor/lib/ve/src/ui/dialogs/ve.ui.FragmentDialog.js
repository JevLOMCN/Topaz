/*!
 * VisualEditor UserInterface FragmentDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for working with fragments of content.
 *
 * @class
 * @abstract
 * @extends OO.ui.ProcessDialog
 * @mixins ve.ui.FragmentWindow
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.FragmentDialog = function VeUiFragmentDialog( config ) {
	// Parent constructor
	ve.ui.FragmentDialog.super.call( this, config );

	// Mixin constructor
	ve.ui.FragmentWindow.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.FragmentDialog, OO.ui.ProcessDialog );

OO.mixinClass( ve.ui.FragmentDialog, ve.ui.FragmentWindow );

/* Static Properties */

ve.ui.FragmentDialog.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: [ 'safe', 'close' ],
		modes: [ 'readonly', 'edit', 'insert' ]
	},
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-apply' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'edit'
	},
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-insert' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'insert'
	}
	// The message visualeditor-dialog-action-goback is also available
	// but currently only used in ve-mw.
];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.FragmentDialog.prototype.initialize = function ( data ) {
	// Parent method
	ve.ui.FragmentDialog.super.prototype.initialize.call( this, data );

	this.tabIndexScope = new ve.ui.TabIndexScope( {
		root: this.$content
	} );
};

/**
 * @inheritdoc OO.ui.Dialog
 */
ve.ui.FragmentDialog.prototype.getActionWidgetConfig = function ( config ) {
	// Mixin method
	config = ve.ui.FragmentWindow.prototype.getActionWidgetConfig.call( this, config );
	// Parent method
	return ve.ui.FragmentDialog.super.prototype.getActionWidgetConfig.call( this, config );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentDialog.prototype.getSetupProcess = function ( data ) {
	// Parent method
	var process = ve.ui.FragmentDialog.super.prototype.getSetupProcess.call( this, data );
	// Mixin method
	return ve.ui.FragmentWindow.prototype.getSetupProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentDialog.prototype.getTeardownProcess = function ( data ) {
	// Parent method
	var process = ve.ui.FragmentDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			if ( this.selectFragmentOnClose ) {
				this.fragment.select();
			}
		}, this );
	// Mixin method
	return ve.ui.FragmentWindow.prototype.getTeardownProcess.call( this, data, process );
};
