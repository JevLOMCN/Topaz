/*!
 * VisualEditor UserInterface MediaWiki MobileLinkInspectorTool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * UserInterface mobile link tool
 *
 * @class
 * @extends ve.ui.MWLinkInspectorTool
 *
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMobileLinkInspectorTool = function VeUiMwMobileLinkInspectorTool() {
	ve.ui.MWMobileLinkInspectorTool.super.apply( this, arguments );
};

OO.inheritClass( ve.ui.MWMobileLinkInspectorTool, ve.ui.MWLinkInspectorTool );

ve.ui.MWMobileLinkInspectorTool.static.displayBothIconAndLabel = true;

ve.ui.toolFactory.register( ve.ui.MWMobileLinkInspectorTool );
