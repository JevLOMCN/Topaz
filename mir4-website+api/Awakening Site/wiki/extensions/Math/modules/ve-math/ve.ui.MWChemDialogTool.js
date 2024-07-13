/*!
 * VisualEditor UserInterface MWChemDialogTool class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * MediaWiki UserInterface chem tool.
 *
 * @class
 * @extends ve.ui.FragmentWindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWChemDialogTool = function VeUiMWChemDialogTool( toolGroup, config ) {
	ve.ui.MWChemDialogTool.super.call( this, toolGroup, config );
};
OO.inheritClass( ve.ui.MWChemDialogTool, ve.ui.FragmentWindowTool );
ve.ui.MWChemDialogTool.static.name = 'chem';
ve.ui.MWChemDialogTool.static.group = 'object';
ve.ui.MWChemDialogTool.static.icon = 'labFlask';
ve.ui.MWChemDialogTool.static.title = OO.ui.deferMsg(
	'math-visualeditor-mwchemdialog-title' );
ve.ui.MWChemDialogTool.static.modelClasses = [ ve.dm.MWChemNode ];
ve.ui.MWChemDialogTool.static.commandName = 'chemDialog';
ve.ui.toolFactory.register( ve.ui.MWChemDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'chemDialog', 'window', 'open',
		{ args: [ 'chemDialog' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'chemInspector', 'window', 'open',
		{ args: [ 'chemInspector' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextChem', 'chemDialog', '<chem', 5 )
);

ve.ui.commandHelpRegistry.register( 'insert', 'chemDialog', {
	sequences: [ 'wikitextChem' ],
	label: OO.ui.deferMsg( 'math-visualeditor-mwchemdialog-title' )
} );
