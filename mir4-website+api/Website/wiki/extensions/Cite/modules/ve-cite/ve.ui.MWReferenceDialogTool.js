'use strict';

/*!
 * VisualEditor MediaWiki Reference dialog tool classes.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * MediaWiki UserInterface reference tool.
 *
 * @constructor
 * @extends ve.ui.FragmentWindowTool
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWReferenceDialogTool = function VeUiMWReferenceDialogTool() {
	ve.ui.MWReferenceDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWReferenceDialogTool, ve.ui.FragmentWindowTool );
ve.ui.MWReferenceDialogTool.static.name = 'reference';
ve.ui.MWReferenceDialogTool.static.group = 'object';
ve.ui.MWReferenceDialogTool.static.icon = 'reference';
if ( mw.config.get( 'wgCiteVisualEditorOtherGroup' ) ) {
	ve.ui.MWReferenceDialogTool.static.title = OO.ui.deferMsg(
		'cite-ve-othergroup-item',
		OO.ui.msg( 'cite-ve-dialogbutton-reference-tooltip' )
	);
} else {
	ve.ui.MWReferenceDialogTool.static.title = OO.ui.deferMsg(
		'cite-ve-dialogbutton-reference-tooltip'
	);
}
ve.ui.MWReferenceDialogTool.static.modelClasses = [ ve.dm.MWReferenceNode ];
ve.ui.MWReferenceDialogTool.static.commandName = 'reference';
ve.ui.MWReferenceDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWReferenceDialogTool );

/**
 * MediaWiki UserInterface use existing reference tool.
 *
 * @constructor
 * @extends ve.ui.WindowTool
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWUseExistingReferenceDialogTool = function VeUiMWUseExistingReferenceDialogTool() {
	ve.ui.MWUseExistingReferenceDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWUseExistingReferenceDialogTool, ve.ui.WindowTool );
ve.ui.MWUseExistingReferenceDialogTool.static.name = 'reference/existing';
ve.ui.MWUseExistingReferenceDialogTool.static.group = 'object';
ve.ui.MWUseExistingReferenceDialogTool.static.icon = 'referenceExisting';
if ( mw.config.get( 'wgCiteVisualEditorOtherGroup' ) ) {
	ve.ui.MWUseExistingReferenceDialogTool.static.title = OO.ui.deferMsg(
		'cite-ve-othergroup-item',
		OO.ui.msg( 'cite-ve-dialog-reference-useexisting-tool' )
	);
} else {
	ve.ui.MWUseExistingReferenceDialogTool.static.title = OO.ui.deferMsg(
		'cite-ve-dialog-reference-useexisting-tool'
	);
}
ve.ui.MWUseExistingReferenceDialogTool.static.commandName = 'reference/existing';
ve.ui.MWUseExistingReferenceDialogTool.static.autoAddToGroup = false;
ve.ui.MWUseExistingReferenceDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWUseExistingReferenceDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'reference', 'window', 'open',
		{ args: [ 'reference' ], supportedSelections: [ 'linear' ] }
	)
);

/* If Citoid is installed these will be overridden */
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextRef', 'reference', '<ref', 4 )
);

ve.ui.triggerRegistry.register(
	'reference', { mac: new ve.ui.Trigger( 'cmd+shift+k' ), pc: new ve.ui.Trigger( 'ctrl+shift+k' ) }
);

ve.ui.commandHelpRegistry.register( 'insert', 'ref', {
	trigger: 'reference',
	sequences: [ 'wikitextRef' ],
	label: OO.ui.deferMsg( 'cite-ve-dialog-reference-title' )
} );

ve.ui.mwWikitextTransferRegistry.register( 'reference', /<ref[^>]*>/ );

ve.ui.HelpCompletionAction.static.toolGroups.cite = { mergeWith: 'insert' };

/**
 * MediaWiki UserInterface references list tool.
 *
 * @constructor
 * @extends ve.ui.FragmentWindowTool
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWReferencesListDialogTool = function VeUiMWReferencesListDialogTool() {
	ve.ui.MWReferencesListDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWReferencesListDialogTool, ve.ui.FragmentWindowTool );
ve.ui.MWReferencesListDialogTool.static.name = 'referencesList';
ve.ui.MWReferencesListDialogTool.static.group = 'object';
ve.ui.MWReferencesListDialogTool.static.icon = 'references';
ve.ui.MWReferencesListDialogTool.static.title =
	OO.ui.deferMsg( 'cite-ve-dialogbutton-referenceslist-tooltip' );
ve.ui.MWReferencesListDialogTool.static.modelClasses = [ ve.dm.MWReferencesListNode ];
ve.ui.MWReferencesListDialogTool.static.commandName = 'referencesList';
ve.ui.toolFactory.register( ve.ui.MWReferencesListDialogTool );
