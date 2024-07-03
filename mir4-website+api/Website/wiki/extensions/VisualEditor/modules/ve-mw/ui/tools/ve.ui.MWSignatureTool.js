/*!
 * VisualEditor MediaWiki UserInterface signature tool class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface signature tool. This defines the menu button and its action.
 *
 * @class
 * @extends ve.ui.MWTransclusionDialogTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWSignatureTool = function VeUiMWSignatureTool() {
	// Parent constructor
	ve.ui.MWSignatureTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWSignatureTool, ve.ui.MWTransclusionDialogTool );

ve.ui.MWSignatureTool.static.name = 'mwSignature';
ve.ui.MWSignatureTool.static.group = 'object';
ve.ui.MWSignatureTool.static.icon = 'signature';
ve.ui.MWSignatureTool.static.title =
	OO.ui.deferMsg( 'visualeditor-mwsignature-tool' );
ve.ui.MWSignatureTool.static.modelClasses = [ ve.dm.MWSignatureNode ];
// Link the tool to the command defined below
ve.ui.MWSignatureTool.static.commandName = 'mwSignature';

ve.ui.toolFactory.register( ve.ui.MWSignatureTool );

// Commands and sequences are only registered on supported namespaces.
// On other namespaces the tool is still shown, but disabled.
if ( mw.Title.wantSignaturesNamespace( mw.config.get( 'wgNamespaceNumber' ) ) ) {
	// Command to insert signature node.
	ve.ui.commandRegistry.register(
		new ve.ui.Command( 'mwSignature', 'content', 'insert', {
			args: [
				[
					{ type: 'mwSignature' },
					{ type: '/mwSignature' }
				],
				// annotate
				false,
				// collapseToEnd
				true
			],
			supportedSelections: [ 'linear' ]
		} )
	);
	ve.ui.sequenceRegistry.register(
		new ve.ui.Sequence( 'wikitextSignature', 'mwSignature', '~~~~', 4 )
	);
	ve.ui.commandHelpRegistry.register( 'insert', 'mwSignature', {
		sequences: [ 'wikitextSignature' ],
		label: OO.ui.deferMsg( 'visualeditor-mwsignature-tool' )
	} );
	if ( mw.libs.ve.isWikitextAvailable ) {
		// Ensure wikitextCommandRegistry has finished loading
		mw.loader.using( 'ext.visualEditor.mwwikitext' ).then( function () {
			ve.ui.wikitextCommandRegistry.register(
				new ve.ui.Command( 'mwSignature', 'content', 'insert', {
					args: [ '~~~~', false, true /* collaseToEnd */ ],
					supportedSelections: [ 'linear' ]
				} )
			);
		} );
	}
} else {
	// No-op command that is never executable
	ve.ui.commandRegistry.register(
		new ve.ui.Command( 'mwSignature', 'content', 'insert', {
			args: [ [] ],
			supportedSelections: []
		} )
	);
	// Wikitext insertion warning
	ve.ui.sequenceRegistry.register(
		new ve.ui.Sequence( 'wikitextSignature', 'mwWikitextWarning', '~~~' )
	);
}
