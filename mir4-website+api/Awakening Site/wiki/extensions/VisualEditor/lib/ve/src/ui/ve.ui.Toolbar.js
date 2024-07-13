/*!
 * VisualEditor UserInterface Toolbar class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface surface toolbar.
 *
 * @class
 * @extends OO.ui.Toolbar
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.Toolbar = function VeUiToolbar( config ) {
	config = config || {};

	// Parent constructor
	ve.ui.Toolbar.super.call( this, ve.ui.toolFactory, ve.ui.toolGroupFactory, config );

	this.updateToolStateDebounced = ve.debounce( this.updateToolState.bind( this ) );

	this.groups = null;
	// Default directions
	this.contextDirection = { inline: 'ltr', block: 'ltr' };
	// The following classes are used here:
	// * ve-ui-dir-inline-ltr
	// * ve-ui-dir-inline-rtl
	// * ve-ui-dir-block-ltr
	// * ve-ui-dir-block-rtl
	this.$element
		.addClass( 've-ui-toolbar' )
		.addClass( 've-ui-dir-inline-' + this.contextDirection.inline )
		.addClass( 've-ui-dir-block-' + this.contextDirection.block );
};

/* Inheritance */

OO.inheritClass( ve.ui.Toolbar, OO.ui.Toolbar );

/* Events */

/**
 * @event updateState
 * @param {ve.dm.SurfaceFragment|null} fragment Surface fragment. Null if no surface is active.
 * @param {Object|null} direction Context direction with 'inline' & 'block' properties if a surface exists. Null if no surface is active.
 * @param {string[]} activeDialogs List of names of currently open dialogs.
 */

/**
 * @event surfaceChange
 * @param {ve.ui.Surface|null} oldSurface Old surface being controlled
 * @param {ve.ui.Surface|null} newSurface New surface being controlled
 */

/**
 * @event resize
 */

/* Methods */

/**
 * Setup toolbar
 *
 * @param {Object} groups List of tool group configurations
 * @param {ve.ui.Surface} [surface] Surface to attach to
 */
ve.ui.Toolbar.prototype.setup = function ( groups, surface ) {
	var oldSurface,
		surfaceChange = false;

	this.detach();

	if ( surface !== this.surface ) {
		// this.surface should be changed before we fire the event
		oldSurface = this.surface;
		this.surface = surface;
		surfaceChange = true;
	}

	// The parent method just rebuilds the tool groups so only
	// do this if they have changed
	if ( groups !== this.groups ) {
		// Parent method
		groups = groups.map( function ( group ) {
			if ( group.name ) {
				group.classes = group.classes || [];
				group.classes.push( 've-ui-toolbar-group-' + group.name );
			} else {
				OO.ui.warnDeprecation( 'No name: ' + JSON.stringify( group ) );
			}
			return group;
		} );
		ve.ui.Toolbar.super.prototype.setup.call( this, groups );
	}

	this.groups = groups;

	if ( groups.length ) {
		this.$element.removeClass( 've-ui-toolbar-empty' );
	} else {
		this.$element.addClass( 've-ui-toolbar-empty' );
	}

	if ( surfaceChange ) {
		// Emit surface change event after tools have been setup
		this.emit( 'surfaceChange', oldSurface, surface );
		// Emit another resize event to let the surface know about the toolbar size
		this.emit( 'resize' );
	}

	// Events
	this.getSurface().getModel().connect( this, { contextChange: 'onContextChange' } );
	this.getSurface().getDialogs().connect( this, {
		opening: 'onInspectorOrDialogOpeningOrClosing',
		closing: 'onInspectorOrDialogOpeningOrClosing'
	} );
	this.getSurface().getToolbarDialogs().connect( this, {
		opening: 'onInspectorOrDialogOpeningOrClosing',
		closing: 'onInspectorOrDialogOpeningOrClosing'
	} );
	this.getSurface().getContext().getInspectors().connect( this, {
		opening: 'onInspectorOrDialogOpeningOrClosing',
		closing: 'onInspectorOrDialogOpeningOrClosing'
	} );
};

/**
 * @inheritdoc
 */
ve.ui.Toolbar.prototype.isToolAvailable = function ( name ) {
	if ( !ve.ui.Toolbar.super.prototype.isToolAvailable.apply( this, arguments ) ) {
		return false;
	}
	// Check the tool's command is available on the surface
	var tool = this.getToolFactory().lookup( name );
	if ( !tool ) {
		return false;
	}
	// FIXME should use .static.getCommandName(), but we have tools that aren't ve.ui.Tool subclasses :(
	var commandName = tool.static.commandName;
	return !commandName || this.getCommands().indexOf( commandName ) !== -1;
};

/**
 * Handle windows opening or closing in the dialogs' or inspectors' window manager.
 *
 * @param {OO.ui.Window} win
 * @param {jQuery.Promise} openingOrClosing
 * @param {Object} data
 */
ve.ui.Toolbar.prototype.onInspectorOrDialogOpeningOrClosing = function ( win, openingOrClosing ) {
	var toolbar = this;
	openingOrClosing.then( function () {
		toolbar.updateToolStateDebounced();
	} );
};

/**
 * Handle context changes on the surface.
 *
 * @fires updateState
 */
ve.ui.Toolbar.prototype.onContextChange = function () {
	this.updateToolStateDebounced();
};

/**
 * Update the state of the tools
 */
ve.ui.Toolbar.prototype.updateToolState = function () {
	if ( !this.getSurface() ) {
		this.emit( 'updateState', null, null );
		return;
	}

	var fragment = this.getSurface().getModel().getFragment();

	// Update context direction for button icons UI.
	// By default, inline and block directions are the same.
	// If no context direction is available, use document model direction.
	var dirInline = this.surface.getView().getSelectionDirectionality();
	var dirBlock = dirInline;

	// 'inline' direction is different only if we are inside a language annotation
	var fragmentAnnotation = fragment.getAnnotations();
	if ( fragmentAnnotation.hasAnnotationWithName( 'meta/language' ) ) {
		dirInline = fragmentAnnotation.getAnnotationsByName( 'meta/language' ).get( 0 ).getAttribute( 'dir' );
	}

	if ( dirInline !== this.contextDirection.inline ) {
		// Remove previous class:
		this.$element.removeClass( 've-ui-dir-inline-rtl ve-ui-dir-inline-ltr' );
		// The following classes are used here:
		// * ve-ui-dir-inline-ltr
		// * ve-ui-dir-inline-rtl
		this.$element.addClass( 've-ui-dir-inline-' + dirInline );
		this.contextDirection.inline = dirInline;
	}
	if ( dirBlock !== this.contextDirection.block ) {
		this.$element.removeClass( 've-ui-dir-block-rtl ve-ui-dir-block-ltr' );
		// The following classes are used here:
		// * ve-ui-dir-block-ltr
		// * ve-ui-dir-block-rtl
		this.$element.addClass( 've-ui-dir-block-' + dirBlock );
		this.contextDirection.block = dirBlock;
	}

	var activeDialogs = [
		this.surface.getDialogs(),
		this.surface.getContext().getInspectors(),
		this.surface.getToolbarDialogs()
	].map( function ( windowManager ) {
		if ( windowManager.getCurrentWindow() ) {
			return windowManager.getCurrentWindow().constructor.static.name;
		}
		return null;
	} ).filter( function ( name ) {
		return name !== null;
	} );

	this.emit( 'updateState', fragment, this.contextDirection, activeDialogs );
};

/**
 * Get triggers for a specified name.
 *
 * @param {string} name Trigger name
 * @return {ve.ui.Trigger[]|undefined} Triggers
 */
ve.ui.Toolbar.prototype.getTriggers = function ( name ) {
	return this.getSurface().triggerListener.getTriggers( name );
};

/**
 * Get a list of commands available to this toolbar's surface
 *
 * @return {string[]} Command names
 */
ve.ui.Toolbar.prototype.getCommands = function () {
	return this.getSurface().getCommands();
};

/**
 * @inheritdoc
 */
ve.ui.Toolbar.prototype.getToolAccelerator = function ( name ) {
	var messages = ve.ui.triggerRegistry.getMessages( name );

	return messages ? messages.join( ', ' ) : undefined;
};

/**
 * Gets the surface which the toolbar controls.
 *
 * Returns null if the toolbar hasn't been set up yet.
 *
 * @return {ve.ui.Surface|null} Surface being controlled
 */
ve.ui.Toolbar.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Detach toolbar from surface and all event listeners
 */
ve.ui.Toolbar.prototype.detach = function () {
	// Events
	if ( this.getSurface() ) {
		this.getSurface().getModel().disconnect( this );
		this.surface = null;
	}
	// Reset narrow state/cache as when we setup again it
	// may be with a different tool list.
	// TODO: Create upstream detach/teardown
	this.setNarrow( false );
	this.narrowThreshold = null;
};

/**
 * Destroys toolbar, removing event handlers and DOM elements.
 *
 * Call this whenever you are done using a toolbar.
 */
ve.ui.Toolbar.prototype.destroy = function () {
	// Parent method
	ve.ui.Toolbar.super.prototype.destroy.call( this );

	// Detach surface last, because tool destructors need getSurface()
	this.detach();
};
