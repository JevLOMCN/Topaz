/*!
 * VisualEditor user interface MWEditingTabDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

mw.libs.ve = mw.libs.ve || {};
/**
 * Dialog for allowing new users to change editing tab preferences.
 *
 * @class
 * @extends OO.ui.MessageDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
mw.libs.ve.EditingTabDialog = function MWLibsVEMWEditingTabDialog( config ) {
	// Parent constructor
	mw.libs.ve.EditingTabDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( mw.libs.ve.EditingTabDialog, OO.ui.MessageDialog );

/* Static Properties */

mw.libs.ve.EditingTabDialog.static.name = 'editingtab';

mw.libs.ve.EditingTabDialog.static.size = 'medium';

mw.libs.ve.EditingTabDialog.static.title = mw.msg( 'visualeditor-editingtabdialog-title' );

mw.libs.ve.EditingTabDialog.static.message = mw.msg( 'visualeditor-editingtabdialog-body' );

mw.libs.ve.EditingTabDialog.static.actions = [

	{
		action: 'prefer-wt',
		label: mw.msg( 'visualeditor-preference-tabs-prefer-wt' )
	},
	{
		action: 'prefer-ve',
		label: mw.msg( 'visualeditor-preference-tabs-prefer-ve' )
	},
	{
		action: 'multi-tab',
		label: mw.msg( 'visualeditor-preference-tabs-multi-tab' )
	},
	{
		label: mw.msg( 'visualeditor-editingtabdialog-ok' ),
		flags: [ 'progressive', 'primary' ]
	}
];

/* Methods */

/**
 * @inheritdoc
 */
mw.libs.ve.EditingTabDialog.prototype.getSetupProcess = function ( action ) {
	return mw.libs.ve.EditingTabDialog.super.prototype.getSetupProcess.call( this, action )
		.next( function () {
			// Same as ve.init.target.getLocalApi()
			new mw.Api().saveOption( 'visualeditor-hidetabdialog', 1 );
			mw.user.options.set( 'visualeditor-hidetabdialog', 1 );
		} );
};

/**
 * @inheritdoc
 */
mw.libs.ve.EditingTabDialog.prototype.getActionProcess = function ( action ) {
	var dialog = this;
	if ( action ) {
		return new OO.ui.Process( function () {
			var actionWidget = this.getActions().get( { actions: action } )[ 0 ];
			actionWidget.pushPending();
			dialog.pushPending();

			// Same as ve.init.target.getLocalApi()
			new mw.Api().saveOption( 'visualeditor-tabs', action ).done( function () {
				actionWidget.popPending();
				mw.user.options.set( 'visualeditor-tabs', action );
				dialog.close( { action: action } );
			} );
		}, this );
	} else {
		// Parent method
		return mw.libs.ve.EditingTabDialog.super.prototype.getActionProcess.call( this, action );
	}
};
