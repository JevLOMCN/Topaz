/*!
 * VisualEditor UserInterface HelpCompletionTool class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface help completion tool.
 *
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.HelpCompletionTool = function VeUiHelpCompletionTool() {
	// Parent constructor
	ve.ui.HelpCompletionTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.HelpCompletionTool, ve.ui.Tool );

/* Static Properties */

ve.ui.HelpCompletionTool.static.name = 'openHelpCompletionsTrigger';

ve.ui.HelpCompletionTool.static.group = 'help';

ve.ui.HelpCompletionTool.static.icon = 'search';

ve.ui.HelpCompletionTool.static.title =
	OO.ui.deferMsg( 'visualeditor-toolbar-search-help-label' );

ve.ui.HelpCompletionTool.static.autoAddToCatchall = false;

ve.ui.HelpCompletionTool.static.commandName = 'openHelpCompletionsTrigger';

/* Registration */

ve.ui.toolFactory.register( ve.ui.HelpCompletionTool );
