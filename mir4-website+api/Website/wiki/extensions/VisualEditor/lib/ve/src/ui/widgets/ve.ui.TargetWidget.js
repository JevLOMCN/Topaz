/*!
 * VisualEditor UserInterface TargetWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Creates an ve.ui.TargetWidget object.
 *
 * User must call #initialize after the widget has been attached
 * to the DOM, and also after the document is changed with #setDocument.
 *
 * @class
 * @abstract
 * @extends OO.ui.Widget
 * @mixins OO.ui.mixin.PendingElement
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {ve.dm.Document} [doc] Initial document model
 * @cfg {Object} [modes] Available editing modes.
 * @cfg {Object} [defaultMode] Default mode for new surfaces.
 * @cfg {Object} [toolbarGroups] Target's toolbar groups config.
 * @cfg {string[]|null} [includeCommands] List of commands to include, null for all registered commands
 * @cfg {string[]} [excludeCommands] List of commands to exclude
 * @cfg {Object} [importRules] Import rules
 * @cfg {boolean} [multiline=true] Multi-line surface
 * @cfg {string} [placeholder] Placeholder text to display when the surface is empty
 * @cfg {boolean} [readOnly] Surface is read-only
 * @cfg {string} [inDialog] The name of the dialog this surface widget is in
 */
ve.ui.TargetWidget = function VeUiTargetWidget( config ) {
	// Config initialization
	config = config || {};

	// Parent constructor
	ve.ui.TargetWidget.super.call( this, config );

	// Mixin constructor
	OO.ui.mixin.PendingElement.call( this, config );

	// Properties
	this.toolbarGroups = config.toolbarGroups;
	// TODO: Override document/targetTriggerListener
	this.includeCommands = config.includeCommands;
	this.excludeCommands = config.excludeCommands;
	this.multiline = config.multiline !== false;
	this.placeholder = config.placeholder;
	this.readOnly = config.readOnly;
	this.importRules = config.importRules;
	this.inDialog = config.inDialog;
	this.modes = config.modes;
	this.defaultMode = config.defaultMode;

	this.target = this.createTarget();

	if ( config.doc ) {
		this.setDocument( config.doc );
	}

	// Initialization
	this.$element.addClass( 've-ui-targetWidget' )
		.append( this.target.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.TargetWidget, OO.ui.Widget );
OO.mixinClass( ve.ui.TargetWidget, OO.ui.mixin.PendingElement );

/* Methods */

/**
 * The target's surface has been changed.
 *
 * @event change
 */

/**
 * The target's surface has been submitted, e.g. Ctrl+Enter
 *
 * @event submit
 */

/**
 * The target's surface has been cancelled, e.g. Escape
 *
 * @event cancel
 */

/**
 * A document has been attached to the target, and a toolbar and surface created.
 *
 * @event setup
 */

/**
 * Create the target for this widget to use
 *
 * @return {ve.init.Target}
 */
ve.ui.TargetWidget.prototype.createTarget = function () {
	return new ve.init.Target( {
		register: false,
		toolbarGroups: this.toolbarGroups,
		modes: this.modes,
		defaultMode: this.defaultMode
	} );
};

/**
 * Set the document to edit
 *
 * This replaces the entire surface in the target.
 *
 * @param {ve.dm.Document} doc
 */
ve.ui.TargetWidget.prototype.setDocument = function ( doc ) {
	// Destroy the previous surface
	this.clear();
	var surface = this.target.addSurface( doc, {
		inTargetWidget: true,
		includeCommands: this.includeCommands,
		excludeCommands: this.excludeCommands,
		importRules: this.importRules,
		multiline: this.multiline,
		placeholder: this.placeholder,
		readOnly: this.readOnly,
		// Reduce from default 10 so inspector callouts are positioned correctly
		overlayPadding: 5,
		inDialog: this.inDialog
	} );
	this.target.setSurface( surface );

	// Events
	surface.getView().connect( this, {
		activation: 'onFocusChange',
		focus: 'onFocusChange',
		blur: 'onFocusChange'
	} );
	// Rethrow as target events so users don't have to re-bind when the surface is changed
	surface.getModel().connect( this, { history: [ 'emit', 'change' ] } );
	surface.connect( this, {
		submit: 'onSurfaceSubmit',
		cancel: 'onSurfaceCancel'
	} );
	// Emit 'position' on first focus, as target widgets are often setup before being made visible. (T303795)
	surface.getView().once( 'focus', function () {
		surface.getView().emit( 'position' );
	} );

	this.emit( 'setup' );
};

/**
 * Handle surface submit events
 *
 * @fires submit
 */
ve.ui.TargetWidget.prototype.onSurfaceSubmit = function () {
	var handled = this.emit( 'submit' );
	if ( !handled && this.inDialog ) {
		// If we are in a dialog, re-throw a fake keydown event for OO.ui.Dialog#onDialogKeyDown
		this.$element.parent().trigger( $.Event( 'keydown', {
			which: OO.ui.Keys.ENTER,
			ctrlKey: true
		} ) );
	}
};

/**
 * Handle surface cancel events
 *
 * @fires cancel
 */
ve.ui.TargetWidget.prototype.onSurfaceCancel = function () {
	var handled = this.emit( 'cancel' );
	if ( !handled && this.inDialog ) {
		// If we are in a dialog, re-throw a fake keydown event for OO.ui.Dialog#onDialogKeyDown
		this.$element.parent().trigger( $.Event( 'keydown', {
			which: OO.ui.Keys.ESCAPE
		} ) );
	}
};

/**
 * Check if the surface has been modified.
 *
 * @return {boolean} The surface has been modified
 */
ve.ui.TargetWidget.prototype.hasBeenModified = function () {
	return !!this.getSurface() && this.getSurface().getModel().hasBeenModified();
};

/**
 * Set the read-only state of the widget
 *
 * @param {boolean} readOnly Make widget read-only
 */
ve.ui.TargetWidget.prototype.setReadOnly = function ( readOnly ) {
	this.readOnly = !!readOnly;
	if ( this.getSurface() ) {
		this.getSurface().setReadOnly( this.readOnly );
	}
	this.$element.toggleClass( 've-ui-targetWidget-readOnly', this.readOnly );
};

/**
 * Check if the widget is read-only
 *
 * @return {boolean}
 */
ve.ui.TargetWidget.prototype.isReadOnly = function () {
	return this.readOnly;
};

/**
 * Get surface.
 *
 * @return {ve.ui.Surface|null}
 */
ve.ui.TargetWidget.prototype.getSurface = function () {
	return this.target.getSurface();
};

/**
 * Get toolbar.
 *
 * @return {OO.ui.Toolbar}
 */
ve.ui.TargetWidget.prototype.getToolbar = function () {
	return this.target.getToolbar();
};

/**
 * Get content data.
 *
 * @return {Array} Content data
 */
ve.ui.TargetWidget.prototype.getContent = function () {
	return this.getSurface().getModel().getDocument().getData();
};

/**
 * Initialize surface and toolbar.
 *
 * Widget must be attached to DOM before initializing.
 *
 * @deprecated
 */
ve.ui.TargetWidget.prototype.initialize = function () {
	OO.ui.warnDeprecation( 've.ui.TargetWidget#initialize is deprecated and no longer needed.' );
};

/**
 * Destroy surface and toolbar.
 */
ve.ui.TargetWidget.prototype.clear = function () {
	this.target.clearSurfaces();
	// Clear toolbar?
};

/**
 * Handle focus and blur events
 */
ve.ui.TargetWidget.prototype.onFocusChange = function () {
	// This may be null if the target is in the process of being destroyed
	var surface = this.getSurface();
	// Replacement for the :focus pseudo selector one would be able to
	// use on a regular input widget
	this.$element.toggleClass(
		've-ui-targetWidget-focused',
		surface && surface.getView().isFocused() && !surface.getView().isDeactivated()
	);
};

/**
 * Focus the surface.
 */
ve.ui.TargetWidget.prototype.focus = function () {
	var surface = this.getSurface();
	if ( surface ) {
		if ( !surface.getView().attachedRoot.isLive() ) {
			surface.once( 'ready', function () {
				surface.getView().focus();
			} );
		} else {
			surface.getView().focus();
		}
	}
};
