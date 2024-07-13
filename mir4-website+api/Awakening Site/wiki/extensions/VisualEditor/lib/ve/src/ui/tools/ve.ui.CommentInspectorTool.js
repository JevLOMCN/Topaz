/*!
 * VisualEditor UserInterface CommentInspectorTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface comment tool.
 *
 * @class
 * @extends ve.ui.FragmentInspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.CommentInspectorTool = function VeUiCommentInspectorTool() {
	ve.ui.CommentInspectorTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.CommentInspectorTool, ve.ui.FragmentInspectorTool );
ve.ui.CommentInspectorTool.static.name = 'comment';
ve.ui.CommentInspectorTool.static.group = 'meta';
ve.ui.CommentInspectorTool.static.icon = 'notice';
ve.ui.CommentInspectorTool.static.title =
	OO.ui.deferMsg( 'visualeditor-commentinspector-tooltip' );
ve.ui.CommentInspectorTool.static.modelClasses = [ ve.dm.CommentNode ];
ve.ui.CommentInspectorTool.static.commandName = 'comment';
ve.ui.CommentInspectorTool.static.deactivateOnSelect = true;
ve.ui.toolFactory.register( ve.ui.CommentInspectorTool );
