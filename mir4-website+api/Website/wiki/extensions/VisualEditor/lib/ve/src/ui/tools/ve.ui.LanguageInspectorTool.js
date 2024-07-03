/*!
 * VisualEditor UserInterface language tool class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface language tool.
 *
 * @class
 * @extends ve.ui.FragmentInspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.LanguageInspectorTool = function VeUiLanguageInspectorTool() {
	ve.ui.LanguageInspectorTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.LanguageInspectorTool, ve.ui.FragmentInspectorTool );
ve.ui.LanguageInspectorTool.static.name = 'language';
ve.ui.LanguageInspectorTool.static.group = 'textStyle';
ve.ui.LanguageInspectorTool.static.icon = 'language';
ve.ui.LanguageInspectorTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-language-tooltip' );
ve.ui.LanguageInspectorTool.static.modelClasses = [ ve.dm.LanguageAnnotation ];
ve.ui.LanguageInspectorTool.static.commandName = 'language';
ve.ui.toolFactory.register( ve.ui.LanguageInspectorTool );
