/*!
 * VisualEditor UserInterface TriggerListener class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Trigger listener
 *
 * @class
 *
 * @constructor
 * @param {string[]} commands Commands to listen to triggers for
 * @param {ve.ui.CommandRegistry} commandRegistry Command registry to get commands from
 */
ve.TriggerListener = function VeTriggerListener( commands, commandRegistry ) {
	// Properties
	this.commands = commands;
	this.commandsByTrigger = {};
	this.triggers = {};

	for ( var i = this.commands.length - 1; i >= 0; i-- ) {
		var command = this.commands[ i ];
		var triggers = ve.ui.triggerRegistry.lookup( command );
		if ( triggers ) {
			for ( var j = triggers.length - 1; j >= 0; j-- ) {
				this.commandsByTrigger[ triggers[ j ].toString() ] = commandRegistry.lookup( command );
			}
			this.triggers[ command ] = triggers;
		}
	}
};

/* Inheritance */

OO.initClass( ve.TriggerListener );

/* Methods */

/**
 * Get list of commands.
 *
 * @return {string[]} Commands
 */
ve.TriggerListener.prototype.getCommands = function () {
	return this.commands;
};

/**
 * Get command associated with trigger string.
 *
 * @param {string} trigger
 * @return {ve.ui.Command|undefined}
 */
ve.TriggerListener.prototype.getCommandByTrigger = function ( trigger ) {
	return this.commandsByTrigger[ trigger ];
};

/**
 * Get triggers for a specified name.
 *
 * @param {string} name Trigger name
 * @return {ve.ui.Trigger[]|undefined}
 */
ve.TriggerListener.prototype.getTriggers = function ( name ) {
	return this.triggers[ name ];
};
