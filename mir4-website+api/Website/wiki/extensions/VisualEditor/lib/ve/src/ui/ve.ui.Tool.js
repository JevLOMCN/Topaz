/*!
 * VisualEditor UserInterface Tool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface tool.
 *
 * @class
 * @abstract
 * @extends OO.ui.Tool
 *
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.Tool = function VeUiTool() {
	// Parent constructor
	ve.ui.Tool.super.apply( this, arguments );

	// Disable initially
	this.setDisabled( true );
};

/* Inheritance */

OO.inheritClass( ve.ui.Tool, OO.ui.Tool );

/* Static Properties */

/**
 * Command to execute when tool is selected.
 *
 * @static
 * @property {string|null}
 * @inheritable
 */
ve.ui.Tool.static.commandName = null;

/**
 * Deactivate tool after it's been selected.
 *
 * Use this for tools which don't display as active when relevant content is selected, such as
 * insertion-only tools.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ui.Tool.static.deactivateOnSelect = true;

/**
 * If this tool is used to create a ve.ui.ToolContextItem, should that item be embeddable
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ui.Tool.static.makesEmbeddableContextItem = true;

/**
 * Icon to use when this tool is shown in a non-toolbar context
 *
 * @static
 * @property {string|null}
 * @inheritable
 */
ve.ui.Tool.static.fallbackIcon = null;

/**
 * Get the symbolic command name for this tool.
 *
 * @static
 * @return {string|null}
 */
ve.ui.Tool.static.getCommandName = function () {
	return this.commandName;
};

/**
 * Get the command for this tool in a given surface context
 *
 * @static
 * @param {ve.ui.Surface} surface
 * @return {ve.ui.Command|null|undefined} Undefined means command not found, null means no command set
 */
ve.ui.Tool.static.getCommand = function ( surface ) {
	var commandName = this.getCommandName();
	if ( commandName === null ) {
		return null;
	}
	return surface.commandRegistry.lookup( commandName );
};

/* Methods */

/**
 * Handle the toolbar state being updated.
 *
 * @param {ve.dm.SurfaceFragment|null} fragment Surface fragment
 * @param {Object|null} direction Context direction with 'inline' & 'block' properties
 */
ve.ui.Tool.prototype.onUpdateState = function ( fragment ) {
	var command = this.getCommand();
	if ( command !== null ) {
		this.setDisabled(
			!command || !fragment || !command.isExecutable( fragment ) ||
			// Show command as disabled if a selection is required and the surface
			// is read-only. Don't do this in Command as some are actually allowed
			// to run, e.g. selectAll
			// TODO: Add an allowedInReadOnly flag to some commands
			( command.supportedSelections && fragment.getSurface() && fragment.getSurface().isReadOnly() )
		);
	}
};

/**
 * @inheritdoc
 */
ve.ui.Tool.prototype.onSelect = function () {
	var contextClosePromise,
		command = this.getCommand(),
		surface = this.toolbar.getSurface(),
		tool = this;

	if ( command instanceof ve.ui.Command ) {
		if ( surface.context.inspector ) {
			contextClosePromise = surface.context.inspector.close().closed;
		} else {
			contextClosePromise = ve.createDeferred().resolve().promise();
		}
	}
	if ( this.constructor.static.deactivateOnSelect ) {
		// It's fine to call setActive here before the promise resolves; it
		// just disables the button, stopping double-clicks and making it feel more responsive
		// if the promise is slow.
		this.setActive( false );
	}
	if ( contextClosePromise ) {
		// N.B. If contextClosePromise is already resolved, then the handler is called
		// before the call to .done returns
		contextClosePromise.done( function () {
			if ( !command.execute( surface, undefined, 'tool' ) ) {
				// If the command fails, ensure the tool is not active
				tool.setActive( false );
			}
		} );
	}
};

/**
 * Get the command for this tool.
 *
 * @return {ve.ui.Command|null|undefined} Undefined means command not found, null means no command set
 */
ve.ui.Tool.prototype.getCommand = function () {
	return this.constructor.static.getCommand( this.toolbar.getSurface() );
};
