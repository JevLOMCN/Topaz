/*!
 * VisualEditor UserInterface CheckListTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface list tool.
 *
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.CheckListTool = function VeUiCheckListTool() {
	// Parent constructor
	ve.ui.CheckListTool.super.apply( this, arguments );

	// Properties
	this.method = null;
};

/* Inheritance */

OO.inheritClass( ve.ui.CheckListTool, ve.ui.Tool );

/* Static Properties */

ve.ui.CheckListTool.static.deactivateOnSelect = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.CheckListTool.prototype.onUpdateState = function ( fragment ) {
	// Parent method
	ve.ui.CheckListTool.super.prototype.onUpdateState.apply( this, arguments );

	var isMatching = fragment.hasMatchingAncestor( 'checkList' );
	this.setActive( isMatching );
};

ve.ui.CheckListTool.static.name = 'checkList';
ve.ui.CheckListTool.static.group = 'structure';
ve.ui.CheckListTool.static.icon = 'checkAll';
ve.ui.CheckListTool.static.title =
	OO.ui.deferMsg( 'visualeditor-listbutton-check-tooltip' );
ve.ui.CheckListTool.static.commandName = 'checkList';

/* Registration */

ve.ui.toolFactory.register( ve.ui.CheckListTool );

/* Command */

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'checkList', 'list', 'toggle',
		{ args: [ null, false, 'checkList' ], supportedSelections: [ 'linear' ] }
	)
);

/* Command help */

ve.ui.commandHelpRegistry.register( 'formatting', 'listCheckList', {
	sequences: [ 'checkList' ], label: OO.ui.deferMsg( 'visualeditor-listbutton-check-tooltip' )
} );

/* Sequence */

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'checkList', 'checkList', [ { type: 'paragraph' }, '[', ']', ' ' ], 3 )
);
