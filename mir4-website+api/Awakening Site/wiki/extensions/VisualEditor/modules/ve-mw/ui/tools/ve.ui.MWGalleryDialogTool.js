/*!
 * VisualEditor MediaWiki UserInterface gallery tool class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface gallery tool.
 *
 * @class
 * @extends ve.ui.FragmentWindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWGalleryDialogTool = function VeUiMWGalleryDialogTool() {
	ve.ui.MWGalleryDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWGalleryDialogTool, ve.ui.FragmentWindowTool );
ve.ui.MWGalleryDialogTool.static.name = 'gallery';
ve.ui.MWGalleryDialogTool.static.group = 'object';
ve.ui.MWGalleryDialogTool.static.icon = 'imageGallery';
ve.ui.MWGalleryDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-mwgallerydialog-title' );
ve.ui.MWGalleryDialogTool.static.modelClasses = [ ve.dm.MWGalleryNode ];
ve.ui.MWGalleryDialogTool.static.commandName = 'gallery';
ve.ui.toolFactory.register( ve.ui.MWGalleryDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'gallery', 'window', 'open',
		{ args: [ 'gallery' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextGallery', 'gallery', '<gallery', 8 )
);
