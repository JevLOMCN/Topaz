// Adapted from ve.ui.MWWikitextDataTransferHandlerFactory
function importRegistry( parent, child ) {
	var name;
	// Copy existing items
	for ( name in parent.registry ) {
		child.register( parent.registry[ name ] );
	}
	// Copy any new items when they're added
	parent.on( 'register', function ( n, data ) {
		child.register( data );
	} );
}

// Create new registries so that we can override the behavior for signatures
// without affecting normal VisualEditor.
var commandRegistry = new ve.ui.CommandRegistry();
importRegistry( ve.ui.commandRegistry, commandRegistry );
var sequenceRegistry = new ve.ui.SequenceRegistry();
importRegistry( ve.ui.sequenceRegistry, sequenceRegistry );

var wikitextCommandRegistry = new ve.ui.MWWikitextCommandRegistry( commandRegistry );
importRegistry( ve.ui.wikitextCommandRegistry, wikitextCommandRegistry );
var wikitextSequenceRegistry = new ve.ui.SequenceRegistry();
importRegistry( ve.ui.wikitextSequenceRegistry, wikitextSequenceRegistry );

// Disable find-and-replace (T263570)
commandRegistry.unregister( 'findAndReplace' );
commandRegistry.unregister( 'findNext' );
commandRegistry.unregister( 'findPrevious' );
wikitextCommandRegistry.unregister( 'findAndReplace' );
wikitextCommandRegistry.unregister( 'findNext' );
wikitextCommandRegistry.unregister( 'findPrevious' );

// Command to insert signature node. Unlike normal VisualEditor, we want to select
// the node (collapseToEnd=false), because we want to show its context menu.
commandRegistry.unregister( 'mwSignature' );
commandRegistry.register(
	new ve.ui.Command( 'dtMwSignature', 'content', 'insert', {
		args: [
			[
				{ type: 'dtMwSignature' },
				{ type: '/dtMwSignature' }
			],
			// annotate
			false,
			// collapseToEnd
			false
		],
		supportedSelections: [ 'linear' ]
	} )
);
// Unlike normal VisualEditor, this is registered regardless of the namespace.
sequenceRegistry.unregister( 'wikitextSignature' );
sequenceRegistry.register(
	new ve.ui.Sequence( 'dtWikitextSignature', 'dtMwSignature', '~~~~', 4 )
);

// TODO: Show a warning when typing ~~~~ in wikitext mode?

// Show wikitext warnings for disabled sequences (disabled via excludeCommands):
var sequenceRegistryForReplyTool = new ve.ui.SequenceRegistry();
importRegistry( sequenceRegistry, sequenceRegistryForReplyTool );

// insertTable
sequenceRegistryForReplyTool.unregister( 'wikitextTable' );
sequenceRegistryForReplyTool.register(
	new ve.ui.Sequence( 'wikitextTableWarning', 'mwWikitextWarning', '{|' )
);

// transclusionFromSequence
sequenceRegistryForReplyTool.unregister( 'wikitextTemplate' );
sequenceRegistryForReplyTool.register(
	new ve.ui.Sequence( 'wikitextTemplateWarning', 'mwWikitextWarning', '{{' )
);

// blockquoteWrap - note, this one applies to `sequenceRegistry` as well
sequenceRegistry.unregister( 'wikitextDescription' );
sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextDescriptionWarning', 'mwWikitextWarning', [ { type: 'paragraph' }, ':' ] )
);

// heading1-6
// This sequence doesn't usually have a command as we don't know what
// heading level is required, but for warnings this doesn't matter.
sequenceRegistryForReplyTool.unregister( 'wikitextHeading' );
sequenceRegistryForReplyTool.register(
	new ve.ui.Sequence( 'wikitextHeadingWarning', 'mwWikitextWarning', [ { type: 'paragraph' }, '=', '=' ] )
);

// horizontal rule
sequenceRegistryForReplyTool.unregister( 'horizontalRule' );
sequenceRegistryForReplyTool.register(
	new ve.ui.Sequence( 'horizontalRuleWarning', 'mwWikitextWarning', [ { type: 'paragraph' }, '-', '-', '-', '-' ] )
);

module.exports = {
	commandRegistry: commandRegistry,
	sequenceRegistry: sequenceRegistry,
	sequenceRegistryForReplyTool: sequenceRegistryForReplyTool,
	wikitextCommandRegistry: wikitextCommandRegistry,
	wikitextSequenceRegistry: wikitextSequenceRegistry
};
