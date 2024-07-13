/*!
 * VisualEditor UserInterface CommandHelpDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for listing all command keyboard shortcuts.
 *
 * @class
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.CommandHelpDialog = function VeUiCommandHelpDialog( config ) {
	// Parent constructor
	ve.ui.CommandHelpDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.CommandHelpDialog, OO.ui.ProcessDialog );

/* Static Properties */

ve.ui.CommandHelpDialog.static.name = 'commandHelp';

ve.ui.CommandHelpDialog.static.size = 'larger';

ve.ui.CommandHelpDialog.static.title =
	OO.ui.deferMsg( 'visualeditor-dialog-command-help-title' );

ve.ui.CommandHelpDialog.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-done' ),
		flags: [ 'safe', 'close' ]
	}
];

ve.ui.CommandHelpDialog.static.commandGroups = {
	textStyle: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-text-style' ),
		promote: [ 'bold', 'italic', 'link' ],
		demote: [ 'clear' ]
	},
	clipboard: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-clipboard' )
	},
	formatting: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-formatting' ),
		promote: [ 'paragraph', 'pre', 'blockquote' ]
	},
	history: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-history' ),
		promote: [ 'undo', 'redo' ]
	},
	dialog: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-dialog' )
	},
	other: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-other' ),
		promote: [ 'findAndReplace', 'findNext', 'findPrevious' ],
		demote: [ 'commandHelp' ]
	},
	insert: {
		title: OO.ui.deferMsg( 'visualeditor-shortcuts-insert' )
	}
};

ve.ui.CommandHelpDialog.static.commandGroupsOrder = [
	'textStyle', 'clipboard',
	'formatting', 'history',
	'dialog', 'other', 'insert'
];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.CommandHelpDialog.prototype.getBodyHeight = function () {
	return Math.round( this.contentLayout.$element[ 0 ].scrollHeight );
};

/**
 * @inheritdoc
 */
ve.ui.CommandHelpDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.CommandHelpDialog.super.prototype.initialize.call( this );

	this.contentLayout = new OO.ui.PanelLayout( {
		scrollable: true,
		padded: true,
		expanded: false
	} );
	this.$container = $( '<div>' ).addClass( 've-ui-commandHelpDialog-container' );

	this.contentLayout.$element.append( this.$container );
	this.$body.append( this.contentLayout.$element );
};

/**
 * @inheritdoc
 */
ve.ui.CommandHelpDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.CommandHelpDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var dialog = this,
				surface = data.surface,
				target = surface.getTarget(),
				sequenceRegistry = surface.sequenceRegistry,
				commandRegistry = surface.commandRegistry,
				availableCommands = surface.getCommands()
					.concat( target.constructor.static.documentCommands )
					.concat( target.constructor.static.targetCommands ),
				commandGroups = this.constructor.static.commandGroups,
				commandGroupsOrder = this.constructor.static.commandGroupsOrder;

			this.$container.empty();

			commandGroupsOrder.forEach( function ( groupName ) {
				var hasCommand = false;
				var commandGroup = commandGroups[ groupName ];
				var commands = dialog.constructor.static.sortedCommandsFromGroup( groupName, commandGroup.promote, commandGroup.demote );
				var $list = $( '<dl>' ).addClass( 've-ui-commandHelpDialog-list' );
				commands.forEach( function ( command ) {
					var triggerList;
					if ( command.trigger ) {
						if (
							!command.ignoreCommand && (
								availableCommands.indexOf( command.trigger ) === -1 ||
								!commandRegistry.lookup( command.trigger )
							)
						) {
							// Trigger is specified by unavailable command
							return;
						}
						triggerList = ve.ui.triggerRegistry.lookup( command.trigger );
					} else {
						triggerList = [];
						if ( command.shortcuts ) {
							if (
								command.checkCommand && (
									availableCommands.indexOf( command.checkCommand ) === -1 ||
									!commandRegistry.lookup( command.checkCommand )
								)
							) {
								// 'checkCommand' is not available
								return;
							}
							triggerList = command.shortcuts.map( function ( shortcut ) {
								return new ve.ui.Trigger( shortcut, true );
							} );
						}
					}

					var hasShortcut = false;

					var $shortcut = $( '<dt>' );
					triggerList.forEach( function ( trigger ) {
						// Append an array of jQuery collections from buildKeyNode
						// eslint-disable-next-line no-jquery/no-append-html
						$shortcut.append( $( '<kbd>' ).addClass( 've-ui-commandHelpDialog-shortcut' ).append(
							trigger.getMessage( true ).map( dialog.constructor.static.buildKeyNode )
						).find( 'kbd + kbd' ).before( '+' ).end() );
						hasShortcut = true;
					} );
					if ( command.sequences ) {
						command.sequences.forEach( function ( sequenceName ) {
							var sequence = sequenceRegistry.lookup( sequenceName );
							if ( sequence ) {
								// Append an array of jQuery collections from buildKeyNode
								// eslint-disable-next-line no-jquery/no-append-html
								$shortcut.append( $( '<kbd>' ).addClass( 've-ui-commandHelpDialog-sequence' )
									.attr( 'data-label', ve.msg( 'visualeditor-shortcuts-sequence-notice' ) )
									.append(
										sequence.getMessage( true ).map( dialog.constructor.static.buildKeyNode )
									)
								);
								hasShortcut = true;
							}
						} );
					}
					if ( hasShortcut ) {
						$list.append(
							$shortcut,
							$( '<dd>' ).text( OO.ui.resolveMsg( command.label ) )
						);
						hasCommand = true;
					}
				} );
				if ( hasCommand ) {
					dialog.$container.append(
						$( '<div>' )
							.addClass( 've-ui-commandHelpDialog-section' )
							.append(
								$( '<h3>' ).text( OO.ui.resolveMsg( commandGroup.title ) ),
								$list
							)
					);
				}
			} );
		}, this );
};

/* Static methods */

/**
 * Wrap a key (as provided by a Trigger) in a node, for display
 *
 * @static
 * @param {string} key Key to display
 * @return {jQuery} A kbd wrapping the key text
 */
ve.ui.CommandHelpDialog.static.buildKeyNode = function ( key ) {
	var $key = $( '<kbd>' );
	if ( key === ' ' ) {
		// Might need to expand this if other keys show up, but currently things like
		// the tab-character only come from Triggers and are pre-localized there into
		// "tab" anyway.
		key = ( new ve.ui.Trigger( 'space' ) ).getMessage();
		$key.addClass( 've-ui-commandHelpDialog-specialKey' );
	}
	return $key.text( key );
};

/**
 * Extract a properly sorted list of commands from a command-group
 *
 * @static
 * @param {string} groupName The dialog-category in which to display this
 * @param {string[]} [promote] Commands which should be displayed first
 * @param {string[]} [demote] Commands which should be displayed last
 * @return {Object[]} List of commands in order
 */
ve.ui.CommandHelpDialog.static.sortedCommandsFromGroup = function ( groupName, promote, demote ) {
	var commands = ve.ui.commandHelpRegistry.lookupByGroup( groupName ),
		keys = Object.keys( commands ),
		used = {},
		auto = [],
		promoted = [],
		demoted = [];
	keys.sort();

	if ( promote ) {
		promote.forEach( function ( name ) {
			if ( !commands[ name ] ) {
				return;
			}
			promoted.push( commands[ name ] );
			used[ name ] = true;
		} );
	}
	if ( demote ) {
		demote.forEach( function ( name ) {
			if ( used[ name ] || !commands[ name ] ) {
				return;
			}
			demoted.push( commands[ name ] );
			used[ name ] = true;
		} );
	}
	keys.forEach( function ( name ) {
		if ( used[ name ] ) {
			return;
		}
		auto.push( commands[ name ] );
	} );
	return promoted.concat( auto, demoted );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.CommandHelpDialog );
