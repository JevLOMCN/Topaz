/*!
 * VisualEditor CommandRegistry class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Command registry.
 *
 * @class
 * @extends OO.Registry
 * @constructor
 */
ve.ui.CommandRegistry = function VeUiCommandRegistry() {
	// Parent constructor
	ve.ui.CommandRegistry.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.CommandRegistry, OO.Registry );

/* Methods */

/**
 * Register a command with the factory.
 *
 * @param {ve.ui.Command} command
 * @throws {Error} If command is not an instance of ve.ui.Command
 */
ve.ui.CommandRegistry.prototype.register = function ( command ) {
	// Validate arguments
	if ( !( command instanceof ve.ui.Command ) ) {
		throw new Error(
			'command must be an instance of ve.ui.Command, cannot be a ' + typeof command
		);
	}

	// Parent method
	ve.ui.CommandRegistry.super.prototype.register.call( this, command.getName(), command );
};

/**
 * Returns the primary command for for node.
 *
 * @param {ve.ce.FocusableNode} node Node to get command for
 * @return {ve.ui.Command}
 */
ve.ui.CommandRegistry.prototype.getCommandForNode = function ( node ) {
	return this.lookup( node.constructor.static.primaryCommandName );
};

/**
 * Returns the delete command for for node.
 *
 * @param {ve.ce.FocusableNode} node Node to get command for
 * @return {ve.ui.Command}
 */
ve.ui.CommandRegistry.prototype.getDeleteCommandForNode = function ( node ) {
	return this.lookup( node.constructor.static.deleteCommandName );
};

/**
 * Get a list of registered command names.
 *
 * @return {string[]}
 */
ve.ui.CommandRegistry.prototype.getNames = function () {
	return Object.keys( this.registry );
};

/* Initialization */

ve.ui.commandRegistry = new ve.ui.CommandRegistry();

/* Registrations */

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'bold', 'annotation', 'toggle',
		{ args: [ 'textStyle/bold' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'italic', 'annotation', 'toggle',
		{ args: [ 'textStyle/italic' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'code', 'annotation', 'toggle',
		{ args: [ 'textStyle/code' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'strikethrough', 'annotation', 'toggle',
		{ args: [ 'textStyle/strikethrough' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'underline', 'annotation', 'toggle',
		{ args: [ 'textStyle/underline' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'subscript', 'annotation', 'toggle',
		{ args: [ 'textStyle/subscript' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'superscript', 'annotation', 'toggle',
		{ args: [ 'textStyle/superscript' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'big', 'annotation', 'toggle',
		{ args: [ 'textStyle/big' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'small', 'annotation', 'toggle',
		{ args: [ 'textStyle/small' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'link', 'window', 'open',
		{ args: [ 'link' ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'linkNoExpand', 'window', 'open',
		{ args: [ 'link', { noExpand: true } ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'specialCharacter', 'window', 'toggle',
		{ args: [ 'specialCharacter' ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'table', 'window', 'open',
		{ args: [ 'table' ], supportedSelections: [ 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'number', 'list', 'toggle',
		{ args: [ 'number' ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'bullet', 'list', 'toggle',
		{ args: [ 'bullet' ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'numberWrapOnce', 'list', 'wrapOnce',
		{ args: [ 'number', true ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'bulletWrapOnce', 'list', 'wrapOnce',
		{ args: [ 'bullet', true ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'commandHelp', 'window', 'open', { args: [ 'commandHelp' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'findAndReplace', 'window', 'open', { args: [ 'findAndReplace', null, 'findSelected' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'findNext', 'window', 'open', { args: [ 'findAndReplace', null, 'findNext' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'findPrevious', 'window', 'open', { args: [ 'findAndReplace', null, 'findPrevious' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'changeDirectionality', 'content', 'changeDirectionality'
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'language', 'window', 'open',
		{ args: [ 'language' ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'paragraph', 'format', 'convert',
		{ args: [ 'paragraph' ], supportedSelections: [ 'linear' ] }
	)
);
( function () {
	for ( var level = 1; level <= 6; level++ ) {
		ve.ui.commandRegistry.register(
			new ve.ui.Command(
				'heading' + level, 'format', 'convert',
				{ args: [ 'heading', { level: level } ], supportedSelections: [ 'linear' ] }
			)
		);
	}
}() );
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'preformatted', 'format', 'convert',
		{ args: [ 'preformatted' ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'blockquote', 'blockquote', 'toggle',
		{ supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'blockquoteWrap', 'blockquote', 'wrap',
		{ supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'autolinkUrl', 'link', 'autolinkUrl',
		{ supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'pasteSpecial', 'content', 'pasteSpecial',
		{ supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'selectAll', 'content', 'selectAll',
		{ supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'delete', 'content', 'remove',
		{ args: [ 'delete' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'backspace', 'content', 'remove',
		{ args: [ 'backspace' ], supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'submit', 'content', 'submit'
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'cancel', 'content', 'cancel'
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'focusContext', 'content', 'focusContext',
		{ supportedSelections: [ 'linear', 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'comment', 'window', 'open',
		{ args: [ 'comment' ], supportedSelections: [ 'linear' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'insertHorizontalRule', 'content', 'insert', {
			args: [
				[
					{ type: 'horizontalRule' },
					{ type: '/horizontalRule' },
					{ type: 'paragraph' }
					// omit /p to leave the cursor in the correct place,
					// fixupInsertion will balance the insertion
				],
				// annotate
				false,
				// collapseToEnd
				true
			],
			supportedSelections: [ 'linear' ]
		}
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'insertTable', 'table', 'create',
		{
			args: [ {
				header: true,
				rows: 3,
				cols: 4
			} ],
			supportedSelections: [ 'linear' ]
		}
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'deleteTable', 'table', 'delete',
		{ args: [ 'table' ], supportedSelections: [ 'linear', 'table' ] }
	)
);

( function () {
	var modes = [ 'row', 'col' ],
		sides = [ 'before', 'after' ],
		modeNames = { row: 'Row', col: 'Column' },
		sideNames = { before: 'Before', after: 'After' };

	modes.forEach( function ( mode ) {
		var modeName = modeNames[ mode ];

		sides.forEach( function ( side ) {
			var sideName = sideNames[ side ];

			ve.ui.commandRegistry.register(
				// Commands registered here:
				// * insertColumnBefore
				// * insertColumnAfter
				// * insertRowBefore
				// * insertRowAfter
				new ve.ui.Command(
					'insert' + modeName + sideName, 'table', 'insert',
					{ args: [ mode, side ], supportedSelections: [ 'table' ] }
				)
			);

			ve.ui.commandRegistry.register(
				// Commands registered here:
				// * moveColumnBefore
				// * moveColumnAfter
				// * moveRowBefore
				// * moveRowAfter
				new ve.ui.Command(
					'move' + modeName + sideName, 'table', 'moveRelative',
					{ args: [ mode, side ], supportedSelections: [ 'table' ] }
				)
			);
		} );

		// Commands registered here:
		// * deleteRow
		// * deleteColumn
		ve.ui.commandRegistry.register(
			new ve.ui.Command(
				'delete' + modeName, 'table', 'delete',
				{ args: [ mode ], supportedSelections: [ 'table' ] }
			)
		);
	} );
}() );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'tableCellHeader', 'table', 'changeCellStyle',
		{ args: [ 'header' ], supportedSelections: [ 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'tableCellData', 'table', 'changeCellStyle',
		{ args: [ 'data' ], supportedSelections: [ 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'enterTableCell', 'table', 'enterTableCell',
		{ supportedSelections: [ 'table' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'exitTableCell', 'table', 'exitTableCell',
		{ supportedSelections: [ 'linear' ] }
	)
);
