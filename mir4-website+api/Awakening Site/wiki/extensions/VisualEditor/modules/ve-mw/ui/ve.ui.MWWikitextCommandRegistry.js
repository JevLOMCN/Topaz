/*!
 * VisualEditor Wikitext command registry
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Command registry.
 *
 * @class
 * @extends ve.ui.CommandRegistry
 * @constructor
 * @param {ve.ui.CommandRegistry} fallbackRegistry Fallback registry
 */
ve.ui.MWWikitextCommandRegistry = function VeUiMwWikitextCommandRegistry( fallbackRegistry ) {
	// Parent constructor
	ve.ui.MWWikitextCommandRegistry.super.apply( this, arguments );

	this.fallbackRegistry = fallbackRegistry;
};

/* Inheritance */

OO.inheritClass( ve.ui.MWWikitextCommandRegistry, ve.ui.CommandRegistry );

/* Methods */

/**
 * Get data for a given symbolic name.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.Registry.html
 *
 * @param {string} name Symbolic name
 * @return {Mixed|undefined} Data associated with symbolic name
 */
ve.ui.MWWikitextCommandRegistry.prototype.lookup = function ( name ) {
	// Parent method
	var data = ve.ui.MWWikitextCommandRegistry.super.prototype.lookup.call( this, name );
	if ( data !== undefined ) {
		return data;
	}
	// Fall through to original command registry if not overridden.
	return this.fallbackRegistry.lookup( name );
};

/**
 * @inheritdoc
 */
ve.ui.MWWikitextCommandRegistry.prototype.getNames = function () {
	return OO.simpleArrayUnion(
		// Parent method
		ve.ui.MWWikitextCommandRegistry.super.prototype.getNames.call( this ),
		this.fallbackRegistry.getNames()
	);
};

/* Initialization */

ve.ui.wikitextCommandRegistry = new ve.ui.MWWikitextCommandRegistry( ve.ui.commandRegistry );

/* Registrations */

ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'bold', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '\'\'\'', '\'\'\'', OO.ui.deferMsg( 'visualeditor-annotationbutton-bold-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'italic', 'mwWikitext', 'toggleWrapSelection',
		{
			args: [
				'\'\'', '\'\'',
				OO.ui.deferMsg( 'visualeditor-annotationbutton-italic-tooltip' ),
				/* expandOffsetsCallback */
				function ( textBefore, textAfter ) {
					var matches, lengthBefore, lengthAfter;
					if ( ( matches = textBefore.match( /('+)$/ ) ) ) {
						lengthBefore = -matches[ 1 ].length;
					}
					if ( ( matches = textAfter.match( /^('+)/ ) ) ) {
						lengthAfter = matches[ 1 ].length;
					}
					return lengthBefore || lengthAfter ? [ lengthBefore, lengthAfter ] : null;
				},
				/* unwrapOffsetsCallback */
				function ( text ) {
					/* Text is only italic if there are 2 or 5+ apostrophes */
					var matches = /^(''([^'].*|.*[^'])''|'{5,}([^'].*|.*[^'])'{5,})$/.test( text );

					return matches ? [ 2, 2 ] : null;
				}
			],
			supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'code', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<code>', '</code>', OO.ui.deferMsg( 'visualeditor-annotationbutton-code-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'strikethrough', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<s>', '</s>', OO.ui.deferMsg( 'visualeditor-annotationbutton-strikethrough-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'underline', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<u>', '</u>', OO.ui.deferMsg( 'visualeditor-annotationbutton-underline-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'subscript', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<sub>', '</sub>', OO.ui.deferMsg( 'visualeditor-annotationbutton-subscript-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'superscript', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<sup>', '</sup>', OO.ui.deferMsg( 'visualeditor-annotationbutton-superscript-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'big', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<big>', '</big>', OO.ui.deferMsg( 'visualeditor-annotationbutton-big-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'small', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<small>', '</small>', OO.ui.deferMsg( 'visualeditor-annotationbutton-small-tooltip' ) ], supportedSelections: [ 'linear' ] }
	)
);

( function () {

	function unformat( text ) {
		/* Use lazy .+? in the middle so whitespace is matched to wrappers */
		var headings;
		if ( ( headings = text.match( /^((={1,6})\s*).+?(\s*\2\s*)$/ ) ) ) {
			return [ headings[ 1 ].length, headings[ 3 ].length ];
		}
		var pre;
		if ( ( pre = text.match( /^ +/ ) ) ) {
			return [ pre[ 0 ].length, 0 ];
		}
	}

	var heading = '';
	for ( var i = 1; i <= 6; i++ ) {
		heading += '=';
		ve.ui.wikitextCommandRegistry.register(
			new ve.ui.Command(
				'heading' + i, 'mwWikitext', 'wrapLine',
				{
					args: [
						heading + ' ',
						' ' + heading,
						// The following messages can be used here:
						// * visualeditor-formatdropdown-format-heading1
						// * visualeditor-formatdropdown-format-heading2
						// * visualeditor-formatdropdown-format-heading3
						// * visualeditor-formatdropdown-format-heading4
						// * visualeditor-formatdropdown-format-heading5
						// * visualeditor-formatdropdown-format-heading6
						OO.ui.deferMsg( 'visualeditor-formatdropdown-format-heading' + i ),
						unformat
					],
					supportedSelections: [ 'linear' ]
				}
			)
		);
	}

	ve.ui.wikitextCommandRegistry.register(
		new ve.ui.Command(
			'paragraph', 'mwWikitext', 'wrapLine',
			{ args: [ '', '', '', unformat ], supportedSelections: [ 'linear' ] }
		)
	);
	ve.ui.wikitextCommandRegistry.register(
		new ve.ui.Command(
			'preformatted', 'mwWikitext', 'wrapLine',
			{ args: [ ' ', '', OO.ui.deferMsg( 'visualeditor-formatdropdown-format-preformatted' ), unformat ], supportedSelections: [ 'linear' ] }
		)
	);
	ve.ui.wikitextCommandRegistry.register(
		new ve.ui.Command(
			'blockquote', 'mwWikitext', 'toggleWrapSelection',
			{ args: [ '<blockquote>', '</blockquote>', OO.ui.deferMsg( 'visualeditor-formatdropdown-format-blockquote' ) ], supportedSelections: [ 'linear' ] }
		)
	);

	function unlist( keepType, text ) {
		var matches;
		if ( ( matches = text.match( /^[*#] */ ) ) && text.slice( 0, 1 ) !== keepType ) {
			return [ matches[ 0 ].length, 0 ];
		}
	}

	ve.ui.wikitextCommandRegistry.register(
		new ve.ui.Command(
			'number', 'mwWikitext', 'wrapLine',
			{ args: [ '# ', '', OO.ui.deferMsg( 'visualeditor-listbutton-number-tooltip' ), unlist.bind( this, '#' ) ], supportedSelections: [ 'linear' ] }
		)
	);
	ve.ui.wikitextCommandRegistry.register(
		new ve.ui.Command(
			'bullet', 'mwWikitext', 'wrapLine',
			{ args: [ '* ', '', OO.ui.deferMsg( 'visualeditor-listbutton-bullet-tooltip' ), unlist.bind( this, '*' ) ], supportedSelections: [ 'linear' ] }
		)
	);

}() );

ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'comment', 'mwWikitext', 'toggleWrapSelection',
		{ args: [ '<!-- ', ' -->', OO.ui.deferMsg( 'visualeditor-commentinspector-title' ) ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'insertTable', 'mwWikitext', 'toggleWrapSelection',
		{
			args: [
				[ { type: 'paragraph' } ].concat( '{| class="wikitable"'.split( '' ) ).concat( { type: '/paragraph' } ),
				[ { type: 'paragraph' } ].concat( '|}'.split( '' ) ).concat( { type: '/paragraph' } ),
				function () {
					return '' +
						'|+ ' + ve.msg( 'visualeditor-dialog-table-caption' ) +
						'\n' +
						'! ' + ve.msg( 'visualeditor-table-format-header' ) + ' !! ' + ve.msg( 'visualeditor-table-format-header' ) +
						'\n' +
						'|-' +
						'\n' +
						'| ' + ve.msg( 'visualeditor-table-format-data' ) + ' || ' + ve.msg( 'visualeditor-table-format-data' );
				}
			],
			supportedSelections: [ 'linear' ]
		}
	)
);

ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command( 'mwNonBreakingSpace', 'content', 'insert', {
		args: [
			'&nbsp;',
			// annotate
			true,
			// collapseToEnd
			true
		],
		supportedSelections: [ 'linear' ]
	} )
);
