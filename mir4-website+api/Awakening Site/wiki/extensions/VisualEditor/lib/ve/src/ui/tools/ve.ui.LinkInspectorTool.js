/*!
 * VisualEditor UserInterface LinkInspectorTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface link tool.
 *
 * @class
 * @extends ve.ui.FragmentInspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.LinkInspectorTool = function VeUiLinkInspectorTool() {
	ve.ui.LinkInspectorTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.LinkInspectorTool, ve.ui.FragmentInspectorTool );
ve.ui.LinkInspectorTool.static.name = 'link';
ve.ui.LinkInspectorTool.static.group = 'meta';
ve.ui.LinkInspectorTool.static.icon = 'link';
ve.ui.LinkInspectorTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-link-tooltip' );
ve.ui.LinkInspectorTool.static.modelClasses = [ ve.dm.LinkAnnotation ];
ve.ui.LinkInspectorTool.static.commandName = 'link';

ve.ui.LinkInspectorTool.prototype.getSelectedModels = function ( fragment ) {
	var selection = fragment && fragment.getSelection();

	// Ask the CE surface about selected models, so it can give the right
	// answer about links based on the CE selection.
	if ( selection instanceof ve.dm.LinearSelection ) {
		var surfaceView = this.toolbar.getSurface().getView();
		if ( selection.equals( surfaceView.getModel().getSelection() ) ) {
			return surfaceView.getSelectedModels();
		}
	}

	return ve.ui.LinkInspectorTool.super.prototype.getSelectedModels.apply( this, arguments );
};

ve.ui.toolFactory.register( ve.ui.LinkInspectorTool );
