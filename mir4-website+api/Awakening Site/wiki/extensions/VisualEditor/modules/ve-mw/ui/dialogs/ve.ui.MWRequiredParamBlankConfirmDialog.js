/*!
 * VisualEditor user interface MWRequiredParamBlankConfirmDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for letting the user confirm that they really want to insert a template/citation with blank required parameters.
 *
 * @class
 * @extends OO.ui.MessageDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWRequiredParamBlankConfirmDialog = function VeUiMWRequiredParamBlankConfirmDialog( config ) {
	// Parent constructor
	ve.ui.MWRequiredParamBlankConfirmDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWRequiredParamBlankConfirmDialog, OO.ui.MessageDialog );

/* Static Properties */

ve.ui.MWRequiredParamBlankConfirmDialog.static.name = 'requiredparamblankconfirm';

ve.ui.MWRequiredParamBlankConfirmDialog.static.size = 'medium';

ve.ui.MWRequiredParamBlankConfirmDialog.static.actions = [
	{
		action: 'ok',
		label: OO.ui.deferMsg( 'visualeditor-dialog-transclusion-required-parameter-dialog-ok' ),
		flags: [ 'primary', 'progressive' ]
	},
	{
		action: 'cancel',
		label: OO.ui.deferMsg( 'visualeditor-dialog-transclusion-required-parameter-dialog-cancel' ),
		flags: [ 'safe', 'back' ]
	}
];

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWRequiredParamBlankConfirmDialog );
