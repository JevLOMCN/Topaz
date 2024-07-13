/*!
 * VisualEditor UserInterface MWSyntaxHighlightInspectorTool class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface syntax highlight tool.
 *
 * @class
 * @extends ve.ui.FragmentInspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWSyntaxHighlightInspectorTool = function VeUiMWSyntaxHighlightInspectorTool() {
	ve.ui.MWSyntaxHighlightInspectorTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWSyntaxHighlightInspectorTool, ve.ui.FragmentInspectorTool );
ve.ui.MWSyntaxHighlightInspectorTool.static.name = 'syntaxhighlightInspector';
ve.ui.MWSyntaxHighlightInspectorTool.static.group = 'object';
ve.ui.MWSyntaxHighlightInspectorTool.static.icon = 'markup';
ve.ui.MWSyntaxHighlightInspectorTool.static.title = OO.ui.deferMsg(
	'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-title' );
ve.ui.MWSyntaxHighlightInspectorTool.static.modelClasses = [ ve.dm.MWInlineSyntaxHighlightNode ];
ve.ui.MWSyntaxHighlightInspectorTool.static.commandName = 'syntaxhighlightInspector';
ve.ui.MWSyntaxHighlightInspectorTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWSyntaxHighlightInspectorTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'syntaxhighlightInspector', 'window', 'open',
		{ args: [ 'syntaxhighlightInspector' ], supportedSelections: [ 'linear' ] }
	)
);
