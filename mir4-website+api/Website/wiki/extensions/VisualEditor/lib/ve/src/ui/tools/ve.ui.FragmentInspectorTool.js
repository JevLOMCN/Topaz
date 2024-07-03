/*!
 * VisualEditor UserInterface FragmentInspectorTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface fragment inspector tool.
 *
 * @abstract
 * @class
 * @extends ve.ui.FragmentWindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.FragmentInspectorTool = function VeUiFragmentInspectorTool() {
	// Parent constructor
	ve.ui.FragmentInspectorTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.FragmentInspectorTool, ve.ui.FragmentWindowTool );

/* Static Properties */

ve.ui.FragmentInspectorTool.static.makesEmbeddableContextItem = false;

/* Methods */

// Deprecated alias
ve.ui.InspectorTool = ve.ui.FragmentInspectorTool;
