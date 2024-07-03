/*!
 * VisualEditor UserInterface ClearAnnotationTool class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface clear all annotations tool.
 *
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.ClearAnnotationTool = function VeUiClearAnnotationTool() {
	// Parent constructor
	ve.ui.ClearAnnotationTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.ClearAnnotationTool, ve.ui.Tool );

/* Static Properties */

ve.ui.ClearAnnotationTool.static.name = 'clear';

ve.ui.ClearAnnotationTool.static.group = 'textStyle';

ve.ui.ClearAnnotationTool.static.icon = 'cancel';

ve.ui.ClearAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-clearbutton-tooltip' );

ve.ui.ClearAnnotationTool.static.commandName = 'clear';

/* Registration */

ve.ui.toolFactory.register( ve.ui.ClearAnnotationTool );
