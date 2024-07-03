/*!
 * VisualEditor UserInterface MWMathDialogTool class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * MediaWiki UserInterface math tool.
 *
 * @class
 * @extends ve.ui.FragmentWindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMathDialogTool = function VeUiMWMathDialogTool( toolGroup, config ) {
	ve.ui.MWMathDialogTool.super.call( this, toolGroup, config );
};
OO.inheritClass( ve.ui.MWMathDialogTool, ve.ui.FragmentWindowTool );
ve.ui.MWMathDialogTool.static.name = 'math';
ve.ui.MWMathDialogTool.static.group = 'object';
ve.ui.MWMathDialogTool.static.icon = 'mathematics';
ve.ui.MWMathDialogTool.static.title = OO.ui.deferMsg(
	'math-visualeditor-mwmathdialog-title' );
ve.ui.MWMathDialogTool.static.modelClasses = [ ve.dm.MWMathNode ];
ve.ui.MWMathDialogTool.static.commandName = 'mathDialog';
ve.ui.toolFactory.register( ve.ui.MWMathDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'mathDialog', 'window', 'open',
		{ args: [ 'mathDialog' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'mathInspector', 'window', 'open',
		{ args: [ 'mathInspector' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextMath', 'mathDialog', '<math', 5 )
);

ve.ui.commandHelpRegistry.register( 'insert', 'mathDialog', {
	sequences: [ 'wikitextMath' ],
	label: OO.ui.deferMsg( 'math-visualeditor-mwmathdialog-title' )
} );
