/*!
 * VisualEditor CommandHelpRegistry class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Command help registry.
 *
 * @class
 * @extends OO.Registry
 * @constructor
 */
ve.ui.CommandHelpRegistry = function VeUiCommandHelpRegistry() {
	// Parent constructor
	OO.Registry.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.CommandHelpRegistry, OO.Registry );

/* Methods */

/**
 * Register a command for display in the dialog.
 *
 * @static
 * @param {string} groupName Dialog-category in which to display this
 * @param {string} commandHelpName Name of the command help item.
 * @param {Object} details Details about the command
 * @param {Function|string} details.label Label describing the command. String or deferred message function.
 * @param {string} [details.trigger] Symbolic name of trigger this for this command
 * @param {string[]} [details.shortcuts] Keyboard shortcuts if this is not a real trigger (e.g. copy/paste)
 * @param {string[]} [details.sequences] Symbolic names of sequences, if this is a sequence, not a trigger
 */
ve.ui.CommandHelpRegistry.prototype.register = function ( groupName, commandHelpName, details ) {
	var existingCommand = this.registry[ commandHelpName ];
	if ( existingCommand ) {
		if ( details.sequences ) {
			details = ve.copy( details );
			details.sequences = ( existingCommand.sequences || [] ).concat( details.sequences );
		}
		details = ve.extendObject( existingCommand, details );
	}

	var platform = ve.getSystemPlatform(),
		platformKey = platform === 'mac' ? 'mac' : 'pc';

	if ( details.shortcuts ) {
		for ( var i = 0; i < details.shortcuts.length; i++ ) {
			var shortcut = details.shortcuts[ i ];
			if ( ve.isPlainObject( shortcut ) ) {
				details.shortcuts[ i ] = shortcut[ platformKey ];
			}
		}
	}

	details.group = groupName;

	OO.Registry.prototype.register.call( this, commandHelpName, details );
};

/**
 * Get data for a given group of commands.
 *
 * @param {string} groupName Group name
 * @return {Object} Commands associated with the group
 */
ve.ui.CommandHelpRegistry.prototype.lookupByGroup = function ( groupName ) {
	var matches = {};
	for ( var commandHelpName in this.registry ) {
		if ( groupName === this.registry[ commandHelpName ].group ) {
			matches[ commandHelpName ] = this.registry[ commandHelpName ];
		}
	}
	return matches;
};

/* Initialization */

ve.ui.commandHelpRegistry = new ve.ui.CommandHelpRegistry();

/* Registrations */

// Text styles
ve.ui.commandHelpRegistry.register( 'textStyle', 'bold', { trigger: 'bold', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-bold-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'italic', { trigger: 'italic', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-italic-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'link', { trigger: 'link', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-link-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'superscript', { trigger: 'superscript', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-superscript-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'subscript', { trigger: 'subscript', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-subscript-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'underline', { trigger: 'underline', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-underline-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'code', { trigger: 'code', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-code-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'strikethrough', { trigger: 'strikethrough', label: OO.ui.deferMsg( 'visualeditor-annotationbutton-strikethrough-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'textStyle', 'clear', { trigger: 'clear', label: OO.ui.deferMsg( 'visualeditor-clearbutton-tooltip' ) } );

// Clipboard
ve.ui.commandHelpRegistry.register( 'clipboard', 'cut', {
	trigger: 'cut',
	ignoreCommand: true,
	label: OO.ui.deferMsg( 'visualeditor-clipboard-cut' )
} );
ve.ui.commandHelpRegistry.register( 'clipboard', 'copy', {
	trigger: 'copy',
	ignoreCommand: true,
	label: OO.ui.deferMsg( 'visualeditor-clipboard-copy' )
} );
ve.ui.commandHelpRegistry.register( 'clipboard', 'paste', {
	trigger: 'paste',
	ignoreCommand: true,
	label: OO.ui.deferMsg( 'visualeditor-clipboard-paste' )
} );
ve.ui.commandHelpRegistry.register( 'clipboard', 'pasteSpecial', { trigger: 'pasteSpecial', label: OO.ui.deferMsg( 'visualeditor-clipboard-paste-special' ) } );

// Dialog
ve.ui.commandHelpRegistry.register( 'dialog', 'dialogCancel', {
	shortcuts: [ 'escape' ],
	label: OO.ui.deferMsg( 'visualeditor-command-dialog-cancel' )
} );
ve.ui.commandHelpRegistry.register( 'dialog', 'dialogConfirm', {
	shortcuts: [ {
		mac: 'cmd+enter',
		pc: 'ctrl+enter'
	} ],
	label: OO.ui.deferMsg( 'visualeditor-command-dialog-confirm' )
} );
ve.ui.commandHelpRegistry.register( 'dialog', 'focusContext', {
	trigger: 'focusContext',
	label: OO.ui.deferMsg( 'visualeditor-command-dialog-focus-context' )
} );

// Formatting
ve.ui.commandHelpRegistry.register( 'formatting', 'paragraph', { trigger: 'paragraph', label: OO.ui.deferMsg( 'visualeditor-formatdropdown-format-paragraph' ) } );
ve.ui.commandHelpRegistry.register( 'formatting', 'heading', { shortcuts: [ 'ctrl+1-6' ], checkCommand: 'heading1', label: OO.ui.deferMsg( 'visualeditor-formatdropdown-format-heading-label' ) } );
ve.ui.commandHelpRegistry.register( 'formatting', 'pre', { trigger: 'preformatted', label: OO.ui.deferMsg( 'visualeditor-formatdropdown-format-preformatted' ) } );
ve.ui.commandHelpRegistry.register( 'formatting', 'blockquote', { trigger: 'blockquote', label: OO.ui.deferMsg( 'visualeditor-formatdropdown-format-blockquote' ) } );
ve.ui.commandHelpRegistry.register( 'formatting', 'indentIn', { trigger: 'indent', label: OO.ui.deferMsg( 'visualeditor-indentationbutton-indent-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'formatting', 'indentOut', { trigger: 'outdent', label: OO.ui.deferMsg( 'visualeditor-indentationbutton-outdent-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'formatting', 'listBullet', { sequences: [ 'bulletStar' ], label: OO.ui.deferMsg( 'visualeditor-listbutton-bullet-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'formatting', 'listNumber', { sequences: [ 'numberDot' ], label: OO.ui.deferMsg( 'visualeditor-listbutton-number-tooltip' ) } );

// History
ve.ui.commandHelpRegistry.register( 'history', 'undo', { trigger: 'undo', label: OO.ui.deferMsg( 'visualeditor-historybutton-undo-tooltip' ) } );
ve.ui.commandHelpRegistry.register( 'history', 'redo', { trigger: 'redo', label: OO.ui.deferMsg( 'visualeditor-historybutton-redo-tooltip' ) } );

// Other
ve.ui.commandHelpRegistry.register( 'other', 'findAndReplace', { trigger: 'findAndReplace', label: OO.ui.deferMsg( 'visualeditor-find-and-replace-title' ) } );
ve.ui.commandHelpRegistry.register( 'other', 'findNext', { trigger: 'findNext', label: OO.ui.deferMsg( 'visualeditor-find-and-replace-next-button' ) } );
ve.ui.commandHelpRegistry.register( 'other', 'findPrevious', { trigger: 'findPrevious', label: OO.ui.deferMsg( 'visualeditor-find-and-replace-previous-button' ) } );
ve.ui.commandHelpRegistry.register( 'other', 'selectAll', { trigger: 'selectAll', label: OO.ui.deferMsg( 'visualeditor-content-select-all' ) } );
ve.ui.commandHelpRegistry.register( 'other', 'changeDirectionality', { trigger: 'changeDirectionality', label: OO.ui.deferMsg( 'visualeditor-changedir' ) } );
ve.ui.commandHelpRegistry.register( 'other', 'commandHelp', { trigger: 'commandHelp', label: OO.ui.deferMsg( 'visualeditor-dialog-command-help-title' ) } );

// Insert
ve.ui.commandHelpRegistry.register( 'insert', 'horizontalRule', { sequences: [ 'horizontalRule' ], label: OO.ui.deferMsg( 'visualeditor-horizontalrule-tooltip' ) } );
