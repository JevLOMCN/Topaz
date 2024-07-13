/*!
 * VisualEditor UserInterface MWSyntaxHighlightDialogTool class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface syntax highlight tool.
 *
 * @class
 * @extends ve.ui.FragmentWindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWSyntaxHighlightDialogTool = function VeUiMWSyntaxHighlightDialogTool() {
	ve.ui.MWSyntaxHighlightDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWSyntaxHighlightDialogTool, ve.ui.FragmentWindowTool );
ve.ui.MWSyntaxHighlightDialogTool.static.name = 'syntaxhighlightDialog';
ve.ui.MWSyntaxHighlightDialogTool.static.group = 'object';
ve.ui.MWSyntaxHighlightDialogTool.static.icon = 'markup';
ve.ui.MWSyntaxHighlightDialogTool.static.title = OO.ui.deferMsg(
	'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-title' );
ve.ui.MWSyntaxHighlightDialogTool.static.modelClasses = [ ve.dm.MWBlockSyntaxHighlightNode ];
ve.ui.MWSyntaxHighlightDialogTool.static.commandName = 'syntaxhighlightDialog';
ve.ui.toolFactory.register( ve.ui.MWSyntaxHighlightDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'syntaxhighlightDialog', 'window', 'open',
		{ args: [ 'syntaxhighlightDialog' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	// Don't wait for the user to type out the full <syntaxhighlight> tag
	new ve.ui.Sequence( 'wikitextSyntax', 'syntaxhighlightDialog', '<syntax', 7 )
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextSource', 'syntaxhighlightDialog', '<source', 7 )
);

ve.ui.commandHelpRegistry.register( 'insert', 'syntax', {
	sequences: [ 'wikitextSyntax', 'wikitextSource' ],
	label: OO.ui.deferMsg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-title' )
} );
