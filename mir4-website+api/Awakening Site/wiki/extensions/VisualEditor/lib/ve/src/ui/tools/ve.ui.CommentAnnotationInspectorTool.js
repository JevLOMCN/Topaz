/*!
 * VisualEditor UserInterface CommentAnnotation tool class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface CommentAnnotation tool.
 *
 * @class
 * @extends ve.ui.FragmentInspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.CommentAnnotationInspectorTool = function VeUiCommentAnnotationInspectorTool() {
	ve.ui.CommentAnnotationInspectorTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.CommentAnnotationInspectorTool, ve.ui.FragmentInspectorTool );
ve.ui.CommentAnnotationInspectorTool.static.name = 'commentAnnotation';
ve.ui.CommentAnnotationInspectorTool.static.group = 'meta';
ve.ui.CommentAnnotationInspectorTool.static.icon = 'speechBubble';
ve.ui.CommentAnnotationInspectorTool.static.title =
	OO.ui.deferMsg( 'visualeditor-commentinspector-tooltip' );
ve.ui.CommentAnnotationInspectorTool.static.modelClasses = [ ve.dm.CommentAnnotation ];
ve.ui.CommentAnnotationInspectorTool.static.commandName = 'commentAnnotation';
ve.ui.toolFactory.register( ve.ui.CommentAnnotationInspectorTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'commentAnnotation', 'window', 'open',
		{ args: [ 'commentAnnotation' ], supportedSelections: [ 'linear' ] }
	)
);
