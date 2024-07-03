/*!
 * VisualEditor UserInterface IndentationTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface indentation tool.
 *
 * @abstract
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.IndentationTool = function VeUiIndentationTool() {
	// Parent constructor
	ve.ui.IndentationTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.IndentationTool, ve.ui.Tool );

/**
 * UserInterface indent tool.
 *
 * @class
 * @extends ve.ui.IndentationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.IncreaseIndentationTool = function VeUiIncreaseIndentationTool() {
	ve.ui.IncreaseIndentationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.IncreaseIndentationTool, ve.ui.IndentationTool );
ve.ui.IncreaseIndentationTool.static.name = 'indent';
ve.ui.IncreaseIndentationTool.static.group = 'structure';
ve.ui.IncreaseIndentationTool.static.icon = 'indent';
ve.ui.IncreaseIndentationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-indentationbutton-indent-tooltip' );
ve.ui.IncreaseIndentationTool.static.commandName = 'indent';
ve.ui.toolFactory.register( ve.ui.IncreaseIndentationTool );

/**
 * UserInterface outdent tool.
 *
 * TODO: Consistency between increase/decrease, indent/outdent and indent/unindent.
 *
 * @class
 * @extends ve.ui.IndentationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.DecreaseIndentationTool = function VeUiDecreaseIndentationTool() {
	ve.ui.DecreaseIndentationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.DecreaseIndentationTool, ve.ui.IndentationTool );
ve.ui.DecreaseIndentationTool.static.name = 'outdent';
ve.ui.DecreaseIndentationTool.static.group = 'structure';
ve.ui.DecreaseIndentationTool.static.icon = 'outdent';
ve.ui.DecreaseIndentationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-indentationbutton-outdent-tooltip' );
ve.ui.DecreaseIndentationTool.static.commandName = 'outdent';
ve.ui.toolFactory.register( ve.ui.DecreaseIndentationTool );
