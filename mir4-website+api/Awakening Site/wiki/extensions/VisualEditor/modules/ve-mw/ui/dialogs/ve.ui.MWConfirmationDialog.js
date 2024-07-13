/*!
 * VisualEditor user interface MWConfirmationDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for displaying a confirmation.
 *
 * This class exists to override the static MessageDialog actions.
 *
 * @class
 * @extends OO.ui.MessageDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWConfirmationDialog = function VeUiMWConfirmationDialog( config ) {
	// Parent constructor
	ve.ui.MWConfirmationDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWConfirmationDialog, OO.ui.MessageDialog );

/* Static properties */

ve.ui.MWConfirmationDialog.static.name = 'confirmation';

ve.ui.MWConfirmationDialog.static.size = 'small';

/* Static methods */

/**
 * Open a confirmation dialog
 *
 * @static
 * @param {string} prompt message key to show as dialog content
 * @param {Function} successCmd callback if continue action is chosen
 */
ve.ui.MWConfirmationDialog.static.confirm = function ( prompt, successCmd ) {
	var windowManager = new OO.ui.WindowManager();
	$( OO.ui.getTeleportTarget() ).append( windowManager.$element );
	var dialog = new ve.ui.MWConfirmationDialog();
	windowManager.addWindows( [ dialog ] );
	windowManager.openWindow( dialog, {
		// Messages that can be used here:
		// * visualeditor-dialog-transclusion-back-confirmation-prompt
		// * visualeditor-dialog-transclusion-close-confirmation-prompt
		message: mw.message( prompt ).text()
	} ).closed.then( function ( data ) {
		if ( data && data.action === 'accept' ) {
			successCmd();
		}
	} );
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWConfirmationDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	data = ve.extendObject( {
		actions: [
			{
				action: 'reject',
				label: OO.ui.deferMsg( 'visualeditor-dialog-transclusion-confirmation-reject' ),
				flags: 'safe'
			},
			{
				action: 'accept',
				label: OO.ui.deferMsg( 'visualeditor-dialog-transclusion-confirmation-discard' ),
				flags: 'destructive'
			}
		]
	}, data );

	return ve.ui.MWConfirmationDialog.super.prototype.getSetupProcess.call( this, data );
};

/**
 * @inheritdoc
 */
ve.ui.MWConfirmationDialog.prototype.getReadyProcess = function ( data ) {
	// "normal" destructive actions don't get focus by default
	this.getActions().get( { actions: 'accept' } )[ 0 ].focus();

	return ve.ui.MWConfirmationDialog.super.prototype.getReadyProcess.call( this, data );
};
