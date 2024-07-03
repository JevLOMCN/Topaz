/*!
 * VisualEditor user interface MWWelcomeDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

mw.libs.ve = mw.libs.ve || {};
/**
 * Dialog for welcoming new users.
 *
 * @class
 * @extends OO.ui.MessageDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
mw.libs.ve.WelcomeDialog = function VeInitWelcomeDialog( config ) {
	// Parent constructor
	mw.libs.ve.WelcomeDialog.super.call( this, config );

	this.$element
		.addClass( 've-init-mw-welcomeDialog' );
};

/* Inheritance */

OO.inheritClass( mw.libs.ve.WelcomeDialog, OO.ui.MessageDialog );

/* Static Properties */

mw.libs.ve.WelcomeDialog.static.name = 'welcome';

mw.libs.ve.WelcomeDialog.static.size = 'medium';

mw.libs.ve.WelcomeDialog.static.actions = [
	{
		action: 'switch-wte',
		label: OO.ui.deferMsg( 'visualeditor-welcomedialog-switch' ),
		modes: [ 'visual' ]
	},
	{
		action: 'switch-ve',
		label: OO.ui.deferMsg( 'visualeditor-welcomedialog-switch-ve' ),
		modes: [ 'source' ]
	},
	{
		action: 'accept',
		label: OO.ui.deferMsg( 'visualeditor-welcomedialog-action' ),
		flags: [ 'progressive', 'primary' ],
		modes: [ 'visual', 'source', 'noswitch' ]
	}
];

/**
 * @inheritdoc
 */
mw.libs.ve.WelcomeDialog.prototype.getSetupProcess = function ( data ) {
	// Provide default title and message
	data = $.extend( {
		title: mw.msg( 'visualeditor-welcomedialog-title', mw.user, mw.config.get( 'wgSiteName' ) ),
		message: $( '<span>' )
			.addClass( 've-init-mw-welcomeDialog-content' )
			.append(
				$( document.createTextNode( mw.msg( 'visualeditor-welcomedialog-content' ) ) ),
				$( '<br>' ),
				$( document.createTextNode( mw.msg( 'visualeditor-welcomedialog-content-thanks' ) ) )
			)
	}, data );

	return mw.libs.ve.WelcomeDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.switchable = data.switchable;
			this.editor = data.editor;

			this.actions.setMode( this.switchable ? this.editor : 'noswitch' );
		}, this );
};

/**
 * @inheritdoc
 */
mw.libs.ve.WelcomeDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'switch-wte' ) {
		return new OO.ui.Process( function () {
			this.close( { action: 'switch-wte' } );
		}, this );
	}

	// Parent method
	return mw.libs.ve.WelcomeDialog.super.prototype.getActionProcess.call( this, action );
};
