/*!
 * VisualEditor UserInterface ToolbarDialogTool class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface toolbar dialog tool.
 *
 * @abstract
 * @class
 * @extends ve.ui.WindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.ToolbarDialogTool = function VeUiToolbarDialogTool() {
	// Parent constructor
	ve.ui.ToolbarDialogTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.ToolbarDialogTool, ve.ui.WindowTool );

/* Static Properties */

ve.ui.ToolbarDialogTool.static.deactivateOnSelect = false;

/**
 * Find and replace tool.
 *
 * @class
 * @extends ve.ui.ToolbarDialogTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.FindAndReplaceTool = function VeUiFindAndReplaceTool() {
	ve.ui.FindAndReplaceTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.FindAndReplaceTool, ve.ui.ToolbarDialogTool );
ve.ui.FindAndReplaceTool.static.name = 'findAndReplace';
ve.ui.FindAndReplaceTool.static.group = 'utility';
ve.ui.FindAndReplaceTool.static.icon = 'articleSearch';
ve.ui.FindAndReplaceTool.static.title =
	OO.ui.deferMsg( 'visualeditor-find-and-replace-title' );
ve.ui.FindAndReplaceTool.static.autoAddToCatchall = false;
ve.ui.FindAndReplaceTool.static.commandName = 'findAndReplace';
ve.ui.toolFactory.register( ve.ui.FindAndReplaceTool );

/**
 * Special character tool.
 *
 * @class
 * @extends ve.ui.ToolbarDialogTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.SpecialCharacterDialogTool = function VeUiSpecialCharacterDialogTool() {
	ve.ui.SpecialCharacterDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.SpecialCharacterDialogTool, ve.ui.ToolbarDialogTool );
ve.ui.SpecialCharacterDialogTool.static.name = 'specialCharacter';
ve.ui.SpecialCharacterDialogTool.static.group = 'dialog';
ve.ui.SpecialCharacterDialogTool.static.icon = 'specialCharacter';
ve.ui.SpecialCharacterDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-specialcharacter-button-tooltip' );
ve.ui.SpecialCharacterDialogTool.static.autoAddToCatchall = false;
ve.ui.SpecialCharacterDialogTool.static.autoAddToGroup = false;
ve.ui.SpecialCharacterDialogTool.static.commandName = 'specialCharacter';
ve.ui.toolFactory.register( ve.ui.SpecialCharacterDialogTool );
