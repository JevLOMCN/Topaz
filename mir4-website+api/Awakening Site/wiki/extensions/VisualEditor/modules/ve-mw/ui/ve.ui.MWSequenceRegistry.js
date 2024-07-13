/*!
 * VisualEditor MediaWiki SequenceRegistry registrations.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextItalic', 'mwWikitextWarning', '\'\'' )
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextNowiki', 'mwWikitextWarning', '<nowiki' )
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextHeading', 'heading2', [ { type: 'paragraph' }, '=', '=' ], 2 )
);
( function () {
	// As the user keeps pressing '=', reduce the heading level
	for ( var level = 3; level <= 6; level++ ) {
		ve.ui.sequenceRegistry.register(
			new ve.ui.Sequence(
				'wikitextHeadingLevel' + level, 'heading' + level,
				[ { type: 'mwHeading', attributes: { level: level - 1 } }, '=' ], 1
			)
		);
	}
}() );
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'numberHash', 'numberWrapOnce', [ { type: 'paragraph' }, '#', ' ' ], 2 )
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextDefinition', 'mwWikitextWarning', [ { type: 'paragraph' }, ';' ] )
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextDescription', 'blockquoteWrap', [ { type: 'paragraph' }, ':' ], 1 )
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextTable', 'insertTable', '{|', 2 )
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextComment', 'comment', '<!--', 4 )
);

/* Help registrations */

ve.ui.commandHelpRegistry.register( 'formatting', 'heading2', {
	sequences: [ 'wikitextHeading' ],
	label: OO.ui.deferMsg( 'visualeditor-formatdropdown-format-heading2' )
} );
ve.ui.commandHelpRegistry.register( 'formatting', 'listNumber', { sequences: [ 'numberHash' ] } );
ve.ui.commandHelpRegistry.register( 'formatting', 'blockquote', { sequences: [ 'wikitextDescription' ] } );
ve.ui.commandHelpRegistry.register( 'insert', 'table', {
	sequences: [ 'wikitextTable' ],
	label: OO.ui.deferMsg( 'visualeditor-table-insert-table' )
} );
ve.ui.commandHelpRegistry.register( 'insert', 'comment', {
	sequences: [ 'wikitextComment' ],
	label: OO.ui.deferMsg( 'visualeditor-commentinspector-title' )
} );
