/*!
 * VisualEditor MediaWiki UserInterface transclusion tool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface transclusion tool.
 *
 * @class
 * @extends ve.ui.FragmentWindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWTransclusionDialogTool = function VeUiMWTransclusionDialogTool() {
	ve.ui.MWTransclusionDialogTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTransclusionDialogTool, ve.ui.FragmentWindowTool );

/* Static Properties */

ve.ui.MWTransclusionDialogTool.static.name = 'transclusion';

ve.ui.MWTransclusionDialogTool.static.group = 'object';

ve.ui.MWTransclusionDialogTool.static.icon = 'puzzle';

ve.ui.MWTransclusionDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-dialogbutton-template-tooltip' );

ve.ui.MWTransclusionDialogTool.static.modelClasses = [ ve.dm.MWTransclusionNode ];

ve.ui.MWTransclusionDialogTool.static.commandName = 'transclusion';

/**
 * Only display tool for single-template transclusions of these templates.
 *
 * @property {string|string[]|null}
 * @static
 * @inheritable
 */
ve.ui.MWTransclusionDialogTool.static.template = null;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialogTool.static.isCompatibleWith = function ( model ) {
	// Parent method
	var compatible = ve.ui.MWTransclusionDialogTool.super.static.isCompatibleWith.call( this, model );

	if ( compatible && this.template ) {
		return model.isSingleTemplate( this.template );
	}

	return compatible;
};

/* Registration */

ve.ui.toolFactory.register( ve.ui.MWTransclusionDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'transclusion', 'window', 'open',
		{ args: [ 'transclusion' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'transclusionFromSequence', 'window', 'open',
		{ args: [ 'transclusion', { cancelCommand: 'undo' } ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextTemplate', 'transclusionFromSequence', '{{', 2 )
);

ve.ui.commandHelpRegistry.register( 'insert', 'template', {
	sequences: [ 'wikitextTemplate' ],
	label: OO.ui.deferMsg( 'visualeditor-dialog-template-title' )
} );
