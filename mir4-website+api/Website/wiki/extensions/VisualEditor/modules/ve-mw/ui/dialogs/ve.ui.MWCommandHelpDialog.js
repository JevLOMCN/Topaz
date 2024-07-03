/*!
 * VisualEditor UserInterface MWCommandHelpDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog listing all command keyboard shortcuts.
 *
 * @class
 * @extends ve.ui.CommandHelpDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWCommandHelpDialog = function VeUiMWCommandHelpDialog( config ) {
	// Parent constructor
	ve.ui.MWCommandHelpDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWCommandHelpDialog, ve.ui.CommandHelpDialog );

/* Static properties */

ve.ui.MWCommandHelpDialog.static.commandGroups = ve.extendObject( {}, ve.ui.MWCommandHelpDialog.static.commandGroups, {
	insert: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-insert' ),
		promote: [ 'ref', 'template', 'table' ],
		demote: [ 'horizontalRule' ]
	}
} );

ve.ui.MWCommandHelpDialog.static.commandGroupsOrder = [
	'textStyle', 'clipboard', 'history', 'dialog',
	'formatting', 'insert',
	'other'
];

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWCommandHelpDialog );
