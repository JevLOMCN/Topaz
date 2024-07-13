/*!
 * VisualEditor UserInterface WindowTool class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface dialog tool.
 *
 * @abstract
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.WindowTool = function VeUiWindowTool() {
	// Parent constructor
	ve.ui.WindowTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.WindowTool, ve.ui.Tool );

/* Static Properties */

/**
 * Name of the associated windows, if there is more than one possible value, or if it can't be
 * deduced from the tool's command.
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.ui.WindowTool.static.associatedWindows = null;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.WindowTool.prototype.onUpdateState = function ( fragment, contextDirection, activeDialogs ) {
	var myWindowNames = [];

	// Parent method
	ve.ui.WindowTool.super.prototype.onUpdateState.apply( this, arguments );

	if ( this.constructor.static.associatedWindows !== null ) {
		myWindowNames = this.constructor.static.associatedWindows;
	} else {
		var command = this.getCommand();
		if ( command && command.getAction() === 'window' ) {
			myWindowNames = [ command.getArgs()[ 0 ] ];
		}
	}

	// Show the tool as active if any of its associated windows is open
	this.setActive( $( activeDialogs ).filter( myWindowNames ).length !== 0 );
};

// Deprecated alias
ve.ui.DialogTool = ve.ui.WindowTool;

/**
 * Command help tool.
 *
 * @class
 * @extends ve.ui.WindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.CommandHelpDialogTool = function VeUiCommandHelpDialogTool() {
	ve.ui.CommandHelpDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.CommandHelpDialogTool, ve.ui.WindowTool );
ve.ui.CommandHelpDialogTool.static.name = 'commandHelp';
ve.ui.CommandHelpDialogTool.static.group = 'help';
ve.ui.CommandHelpDialogTool.static.icon = 'keyboard';
ve.ui.CommandHelpDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-dialog-command-help-title' );
ve.ui.CommandHelpDialogTool.static.autoAddToCatchall = false;
ve.ui.CommandHelpDialogTool.static.commandName = 'commandHelp';
ve.ui.toolFactory.register( ve.ui.CommandHelpDialogTool );
