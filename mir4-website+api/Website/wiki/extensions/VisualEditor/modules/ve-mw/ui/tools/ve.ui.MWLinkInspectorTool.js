/*!
 * VisualEditor UserInterface MediaWiki LinkInspectorTool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * UserInterface link tool. Overrides link tool from core.
 *
 * Works for both link annotations and link nodes, and fires the 'link' command
 * which works for both as well.
 *
 * @class
 * @extends ve.ui.LinkInspectorTool
 *
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLinkInspectorTool = function VeUiMwLinkInspectorTool() {
	// Parent constructor
	ve.ui.MWLinkInspectorTool.super.apply( this, arguments );

	var educationPopup = new ve.ui.MWEducationPopupWidget( this.$link, {
		popupTitle: ve.msg( 'visualeditor-linkinspector-educationpopup-title' ),
		popupText: mw.message( 'visualeditor-linkinspector-educationpopup-text' ).parseDom(),
		popupImage: 'link',
		trackingName: 'link'
	} );

	this.$link.after( educationPopup.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLinkInspectorTool, ve.ui.LinkInspectorTool );

/* Static Properties */

ve.ui.MWLinkInspectorTool.static.modelClasses =
	ve.ui.MWLinkInspectorTool.super.static.modelClasses.concat( [
		ve.dm.MWNumberedExternalLinkNode,
		ve.dm.MWMagicLinkNode
	] );

ve.ui.MWLinkInspectorTool.static.associatedWindows = [ 'link', 'linkNode', 'linkMagicNode' ];

/* Registration */

ve.ui.toolFactory.register( ve.ui.MWLinkInspectorTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'link', 'link', 'open', { supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextLink', 'linkNoExpand', '[[', 2 )
);

ve.ui.commandHelpRegistry.register( 'textStyle', 'link', { sequences: [ 'wikitextLink' ] } );
