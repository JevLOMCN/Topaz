/*!
 * VisualEditor MediaWiki media dialog tool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface media edit tool.
 *
 * @class
 * @extends ve.ui.FragmentWindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMediaDialogTool = function VeUiMWMediaDialogTool() {
	ve.ui.MWMediaDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWMediaDialogTool, ve.ui.FragmentWindowTool );
ve.ui.MWMediaDialogTool.static.name = 'media';
ve.ui.MWMediaDialogTool.static.group = 'object';
ve.ui.MWMediaDialogTool.static.icon = 'image';
ve.ui.MWMediaDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-dialogbutton-media-tooltip' );
ve.ui.MWMediaDialogTool.static.modelClasses = [ ve.dm.MWBlockImageNode, ve.dm.MWInlineImageNode ];
ve.ui.MWMediaDialogTool.static.commandName = 'media';
ve.ui.MWMediaDialogTool.static.autoAddToCatchall = false;
ve.ui.MWMediaDialogTool.static.autoAddToGroup = false;
ve.ui.toolFactory.register( ve.ui.MWMediaDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'media', 'window', 'open',
		{ args: [ 'media' ], supportedSelections: [ 'linear' ] }
	)
);
