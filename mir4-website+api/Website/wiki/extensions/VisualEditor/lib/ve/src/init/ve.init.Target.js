/*!
 * VisualEditor Initialization Target class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic Initialization target.
 *
 * @class
 * @abstract
 * @extends OO.ui.Element
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {Object} [toolbarConfig={}] Configuration options for the toolbar
 * @cfg {Object} [toolbarGroups] Toolbar groups, defaults to this.constructor.static.toolbarGroups
 * @cfg {Object} [actionGroups] Toolbar groups, defaults to this.constructor.static.actionGroups
 * @cfg {string[]} [modes] Available editing modes. Defaults to static.modes
 * @cfg {string} [defaultMode] Default mode for new surfaces. Must be in this.modes and defaults to first item.
 * @cfg {boolean} [register=true] Register the target at ve.init.target
 */
ve.init.Target = function VeInitTarget( config ) {
	config = config || {};

	// Parent constructor
	ve.init.Target.super.call( this, config );

	// Mixin constructors
	OO.EventEmitter.call( this );

	// Register
	if ( config.register !== false ) {
		ve.init.target = this;
	}

	// Properties
	this.surfaces = [];
	this.surface = null;
	this.toolbar = null;
	this.toolbarConfig = config.toolbarConfig || {};
	this.toolbarGroups = config.toolbarGroups || this.constructor.static.toolbarGroups;
	this.actionGroups = config.actionGroups || this.constructor.static.actionGroups;
	this.$scrollContainer = this.getScrollContainer();
	this.$scrollListener = this.$scrollContainer.is( 'html, body' ) ?
		$( OO.ui.Element.static.getWindow( this.$scrollContainer[ 0 ] ) ) :
		this.$scrollContainer;

	this.toolbarScrollOffset = 0;
	this.activeToolbars = 0;
	this.wasSurfaceActive = null;
	this.teardownPromise = null;

	this.modes = config.modes || this.constructor.static.modes;
	this.setDefaultMode( config.defaultMode );

	this.setupTriggerListeners();

	// Initialization
	this.$element.addClass( 've-init-target' );

	if ( ve.init.platform.constructor.static.isIos() ) {
		this.$element.addClass( 've-init-target-ios' );
	}

	// Events
	this.onDocumentKeyDownHandler = this.onDocumentKeyDown.bind( this );
	this.onDocumentKeyUpHandler = this.onDocumentKeyUp.bind( this );
	this.onDocumentVisibilityChangeHandler = this.onDocumentVisibilityChange.bind( this );
	this.onTargetKeyDownHandler = this.onTargetKeyDown.bind( this );
	this.onContainerScrollHandler = this.onContainerScroll.bind( this );
	this.bindHandlers();
};

/* Inheritance */

OO.inheritClass( ve.init.Target, OO.ui.Element );

OO.mixinClass( ve.init.Target, OO.EventEmitter );

/* Events */

/**
 * @event surfaceReady
 *
 * Must be fired after the surface is initialized
 */

/* Static Properties */

/**
 * Editing modes available in the target.
 *
 * Must contain at least one mode. Overridden if the #modes config option is used.
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.init.Target.static.modes = [ 'visual' ];

/**
 * Toolbar definition, passed to ve.ui.Toolbar#setup
 *
 * @static
 * @property {Array}
 * @inheritable
 */
ve.init.Target.static.toolbarGroups = [
	{
		name: 'history',
		include: [ { group: 'history' } ]
	},
	{
		name: 'format',
		header: OO.ui.deferMsg( 'visualeditor-toolbar-paragraph-format' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-format-tooltip' ),
		type: 'menu',
		include: [ { group: 'format' } ],
		promote: [ 'paragraph' ],
		demote: [ 'preformatted', 'blockquote' ]
	},
	{
		name: 'style',
		include: [ 'bold', 'italic', 'moreTextStyle' ]
	},
	{
		name: 'link',
		include: [ 'link' ]
	},
	{
		name: 'structure',
		header: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		invisibleLabel: true,
		type: 'list',
		icon: 'listBullet',
		include: [ { group: 'structure' } ],
		demote: [ 'outdent', 'indent' ]
	},
	{
		name: 'insert',
		header: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		invisibleLabel: true,
		type: 'list',
		icon: 'add',
		include: '*'
	},
	{
		name: 'specialCharacter',
		include: [ 'specialCharacter' ]
	},
	{
		name: 'pageMenu',
		type: 'list',
		align: 'after',
		icon: 'menu',
		indicator: null,
		header: OO.ui.deferMsg( 'visualeditor-pagemenu-tooltip' ),
		title: OO.ui.deferMsg( 'visualeditor-pagemenu-tooltip' ),
		label: OO.ui.deferMsg( 'visualeditor-pagemenu-tooltip' ),
		invisibleLabel: true,
		include: [ { group: 'utility' }, { group: 'help' } ]
	}
	// ve-mw puts help in a separate group and so uses the
	// visualeditor-help-tool message.
	// TODO: Consider downstreaming this message.
];

/**
 * Toolbar definition for the actions side of the toolbar
 *
 * @deprecated Use align:'after' in the regular toolbarGroups instead.
 * @static
 * @property {Array}
 * @inheritable
 */
ve.init.Target.static.actionGroups = [];

/**
 * List of commands which can be triggered anywhere from within the document
 *
 * @type {string[]} List of command names
 */
ve.init.Target.static.documentCommands = [];

/**
 * List of commands which can be triggered from within the target element
 *
 * @type {string[]} List of command names
 */
ve.init.Target.static.targetCommands = [ 'commandHelp', 'findAndReplace', 'findNext', 'findPrevious' ];

/**
 * List of commands to include in the target
 *
 * Null means all commands in the registry are used (excluding excludeCommands)
 *
 * @type {string[]|null} List of command names
 */
ve.init.Target.static.includeCommands = null;

/**
 * List of commands to exclude from the target entirely
 *
 * @type {string[]} List of command names
 */
ve.init.Target.static.excludeCommands = [];

/**
 * Surface import rules
 *
 * One set for external (non-VE) paste sources and one for all paste sources.
 *
 * Most rules are handled in ve.dm.ElementLinearData#sanitize, but htmlBlacklist
 * is handled in ve.ce.Surface#afterPaste.
 *
 * @type {Object}
 */
ve.init.Target.static.importRules = {
	external: {
		blacklist: {
			// Annotations
			// TODO: This also removes harmless things like <span style="font-weight: bold;">
			// which would otherwise get converted to a bold annotation
			'textStyle/span': true,
			'textStyle/font': true,
			// Nodes
			alienInline: true,
			alienBlock: true,
			alienTableCell: true,
			comment: true,
			div: true
		},
		// Selectors to filter. Runs before model type blacklist above.
		htmlBlacklist: {
			// remove: { '.selectorToRemove': true }
			unwrap: {
				fieldset: true,
				legend: true,
				// Unsupported sectioning tags
				main: true,
				nav: true,
				aside: true,
				// HTML headings are already bold by default. Some skins may use non-bold
				// heaidngs, but more likely we will end up with useless bold annotations.
				'h1 b, h2 b, h3 b, h4 b, h5 b, h6 b': true,
				'h1 strong, h2 strong, h3 strong, h4 strong, h5 strong, h6 strong': true
			}
		},
		nodeSanitization: true
	},
	all: null
};

/* Static methods */

/**
 * Create a document model from an HTML document.
 *
 * @param {HTMLDocument|string} doc HTML document or source text
 * @param {string} mode Editing mode
 * @param {Object} options Conversion options
 * @return {ve.dm.Document} Document model
 */
ve.init.Target.static.createModelFromDom = function ( doc, mode, options ) {
	if ( mode === 'source' ) {
		return ve.dm.sourceConverter.getModelFromSourceText( doc, options );
	} else {
		return ve.dm.converter.getModelFromDom( doc, options );
	}
};

/**
 * Parse document string into an HTML document
 *
 * @param {string} documentString Document. Note that this must really be a whole document
 *   with a single root tag.
 * @param {string} mode Editing mode
 * @return {HTMLDocument|string} HTML document, or document string (source mode)
 */
ve.init.Target.static.parseDocument = function ( documentString, mode ) {
	if ( mode === 'source' ) {
		return documentString;
	}
	return ve.createDocumentFromHtml( documentString );
};

// Deprecated alias
ve.init.Target.prototype.parseDocument = function () {
	return this.constructor.static.parseDocument.apply( this.constructor.static, arguments );
};

/* Methods */

/**
 * Set default editing mode for new surfaces
 *
 * @param {string} defaultMode Editing mode, see static.modes
 */
ve.init.Target.prototype.setDefaultMode = function ( defaultMode ) {
	// Mode is not available
	if ( !this.isModeAvailable( defaultMode ) ) {
		if ( !this.defaultMode ) {
			// Use default mode if nothing has been set
			defaultMode = this.modes[ 0 ];
		} else {
			// Fail if we already have a valid mode
			return;
		}
	}
	if ( defaultMode !== this.defaultMode ) {
		if ( this.defaultMode ) {
			// Documented below
			// eslint-disable-next-line mediawiki/class-doc
			this.$element.removeClass( 've-init-target-' + this.defaultMode );
		}
		// The following classes are used here:
		// * ve-init-target-visual
		// * ve-init-target-[modename]
		this.$element.addClass( 've-init-target-' + defaultMode );
		this.defaultMode = defaultMode;
	}
};

/**
 * Get default editing mode for new surfaces
 *
 * @return {string} Editing mode
 */
ve.init.Target.prototype.getDefaultMode = function () {
	return this.defaultMode;
};

/**
 * Check if a specific editing mode is available
 *
 * @param {string} mode Editing mode
 * @return {boolean} Editing mode is available
 */
ve.init.Target.prototype.isModeAvailable = function ( mode ) {
	return this.modes.indexOf( mode ) !== -1;
};

/**
 * Bind event handlers to target and document
 */
ve.init.Target.prototype.bindHandlers = function () {
	$( this.getElementDocument() ).on( {
		keydown: this.onDocumentKeyDownHandler,
		keyup: this.onDocumentKeyUpHandler,
		visibilitychange: this.onDocumentVisibilityChangeHandler
	} );
	this.$element.on( 'keydown', this.onTargetKeyDownHandler );
	this.$scrollListener[ 0 ].addEventListener( 'scroll', this.onContainerScrollHandler, { passive: true } );
};

/**
 * Unbind event handlers on target and document
 */
ve.init.Target.prototype.unbindHandlers = function () {
	$( this.getElementDocument() ).off( {
		keydown: this.onDocumentKeyDownHandler,
		keyup: this.onDocumentKeyUpHandler,
		visibilitychange: this.onDocumentVisibilityChangeHandler
	} );
	this.$element.off( 'keydown', this.onTargetKeyDownHandler );
	this.$scrollListener[ 0 ].removeEventListener( 'scroll', this.onContainerScrollHandler );
};

/**
 * Teardown the target, removing all surfaces, toolbars and handlers
 *
 * @return {jQuery.Promise} Promise which resolves when the target has been torn down
 */
ve.init.Target.prototype.teardown = function () {
	if ( !this.teardownPromise ) {
		this.unbindHandlers();
		// Wait for the toolbar to teardown before clearing surfaces,
		// as it may want to transition away
		this.teardownPromise = this.teardownToolbar().then( this.clearSurfaces.bind( this ) );
	}
	return this.teardownPromise;
};

/**
 * Destroy the target
 *
 * @return {jQuery.Promise} Promise which resolves when the target has been destroyed
 */
ve.init.Target.prototype.destroy = function () {
	var target = this;
	return this.teardown().then( function () {
		target.$element.remove();
		if ( ve.init.target === target ) {
			ve.init.target = null;
		}
	} );
};

/**
 * Set up trigger listeners
 */
ve.init.Target.prototype.setupTriggerListeners = function () {
	var surfaceOrSurfaceConfig = this.getSurface() || this.getSurfaceConfig();
	this.documentTriggerListener = new ve.TriggerListener( this.constructor.static.documentCommands, surfaceOrSurfaceConfig.commandRegistry );
	this.targetTriggerListener = new ve.TriggerListener( this.constructor.static.targetCommands, surfaceOrSurfaceConfig.commandRegistry );
};

/**
 * Get the target's scroll container
 *
 * @return {jQuery} The target's scroll container
 */
ve.init.Target.prototype.getScrollContainer = function () {
	return $( OO.ui.Element.static.getClosestScrollableContainer( document.body ) );
};

/**
 * Handle scroll container scroll events
 */
ve.init.Target.prototype.onContainerScroll = function () {
	// Don't use getter as it creates the toolbar
	var toolbar = this.toolbar;

	if ( toolbar && toolbar.isFloatable() ) {
		var wasFloating = toolbar.isFloating();
		var scrollTop = this.$scrollContainer.scrollTop();

		if ( scrollTop + this.toolbarScrollOffset > toolbar.getElementOffset().top ) {
			toolbar.float();
		} else {
			toolbar.unfloat();
		}

		if ( toolbar.isFloating() !== wasFloating ) {
			// HACK: Re-position any active toolgroup popups. We can't rely on normal event handler order
			// because we're mixing jQuery and non-jQuery events. T205924#4657203
			toolbar.getItems().forEach( function ( toolgroup ) {
				if ( toolgroup instanceof OO.ui.PopupToolGroup && toolgroup.isActive() ) {
					toolgroup.position();
				}
			} );
		}
	}
};

/**
 * Handle key down events on the document
 *
 * @param {jQuery.Event} e Key down event
 */
ve.init.Target.prototype.onDocumentKeyDown = function ( e ) {
	var trigger = new ve.ui.Trigger( e );
	if ( trigger.isComplete() ) {
		var command = this.documentTriggerListener.getCommandByTrigger( trigger.toString() );
		var surface = this.getSurface();
		if ( surface && command && command.execute( surface, undefined, 'trigger' ) ) {
			e.preventDefault();
		}
	}
	// Allows elements to re-style for ctrl+click behaviour, e.g. ve.ce.LinkAnnotation
	this.$element.toggleClass( 've-init-target-ctrl-meta-down', e.ctrlKey || e.metaKey );
};

/**
 * Handle key up events on the document
 *
 * @param {jQuery.Event} e Key up event
 */
ve.init.Target.prototype.onDocumentKeyUp = function ( e ) {
	this.$element.toggleClass( 've-init-target-ctrl-meta-down', e.ctrlKey || e.metaKey );
};

/**
 * Handle visibility change events on the document
 *
 * @param {jQuery.Event} e Visibility change event
 */
ve.init.Target.prototype.onDocumentVisibilityChange = function () {
	// keyup event will be missed if you ctrl+tab away from the page
	this.$element.removeClass( 've-init-target-ctrl-meta-down' );
};

/**
 * Handle key down events on the target
 *
 * @param {jQuery.Event} e Key down event
 */
ve.init.Target.prototype.onTargetKeyDown = function ( e ) {
	var trigger = new ve.ui.Trigger( e );
	if ( trigger.isComplete() ) {
		var command = this.targetTriggerListener.getCommandByTrigger( trigger.toString() );
		var surface = this.getSurface();
		if ( surface && command && command.execute( surface, undefined, 'trigger' ) ) {
			e.preventDefault();
		}
	}
};

/**
 * Handle toolbar resize events
 */
ve.init.Target.prototype.onToolbarResize = function () {
	if ( !this.getSurface() ) {
		return;
	}
	this.getSurface().setPadding( {
		top: this.getToolbar().getHeight() + this.toolbarScrollOffset
	} );
};

/**
 * Create a target widget.
 *
 * @param {Object} [config] Configuration options
 * @return {ve.ui.TargetWidget}
 */
ve.init.Target.prototype.createTargetWidget = function ( config ) {
	return new ve.ui.TargetWidget( ve.extendObject( {
		toolbarGroups: this.toolbarGroups
	}, config ) );
};

/**
 * Create a surface.
 *
 * @param {ve.dm.Document|ve.dm.Surface} dmDocOrSurface Document model or surface model
 * @param {Object} [config] Configuration options
 * @return {ve.ui.Surface}
 */
ve.init.Target.prototype.createSurface = function ( dmDocOrSurface, config ) {
	return new ve.ui.Surface( this, dmDocOrSurface, this.getSurfaceConfig( config ) );
};

/**
 * Get surface configuration options
 *
 * @param {Object} config Configuration option overrides
 * @return {Object} Surface configuration options
 */
ve.init.Target.prototype.getSurfaceConfig = function ( config ) {
	return ve.extendObject( {
		$scrollContainer: this.$scrollContainer,
		$scrollListener: this.$scrollListener,
		commandRegistry: ve.ui.commandRegistry,
		sequenceRegistry: ve.ui.sequenceRegistry,
		dataTransferHandlerFactory: ve.ui.dataTransferHandlerFactory,
		includeCommands: this.constructor.static.includeCommands,
		excludeCommands: OO.simpleArrayUnion(
			this.constructor.static.excludeCommands,
			this.constructor.static.documentCommands,
			this.constructor.static.targetCommands
		),
		importRules: this.constructor.static.importRules
	}, config );
};

/**
 * Add a surface to the target
 *
 * @param {ve.dm.Document|ve.dm.Surface} dmDocOrSurface Document model or surface model
 * @param {Object} [config] Configuration options
 * @return {ve.ui.Surface}
 */
ve.init.Target.prototype.addSurface = function ( dmDocOrSurface, config ) {
	var surface = this.createSurface( dmDocOrSurface, ve.extendObject( { mode: this.getDefaultMode() }, config ) );
	this.surfaces.push( surface );
	surface.getView().connect( this, {
		focus: this.onSurfaceViewFocus.bind( this, surface )
	} );
	// Sub-classes should initialize the surface when possible, then fire 'surfaceReady'
	return surface;
};

/**
 * Destroy and remove all surfaces from the target
 */
ve.init.Target.prototype.clearSurfaces = function () {
	// We're about to destroy this.surface, so unset it for sanity
	// Otherwise, getSurface() could return a destroyed surface
	this.surface = null;
	while ( this.surfaces.length ) {
		this.surfaces.pop().destroy();
	}
};

/**
 * Handle focus events from a surface's view
 *
 * @param {ve.ui.Surface} surface Surface firing the event
 */
ve.init.Target.prototype.onSurfaceViewFocus = function ( surface ) {
	this.setSurface( surface );
};

/**
 * Set the target's active surface
 *
 * @param {ve.ui.Surface} surface
 */
ve.init.Target.prototype.setSurface = function ( surface ) {
	if ( OO.ui.isMobile() ) {
		// Allow popup tool groups's menus to display on top of the mobile context, which is attached
		// to the global overlay (T307849)
		this.toolbarConfig.$overlay = surface.getGlobalOverlay().$element;
		// There is already a toolbar (e.g. when switching), swap out the overlay:
		// TODO: Add a setOverlay method to Toolbar, or create a new toolbar
		if ( this.toolbar ) {
			this.toolbar.$overlay = this.toolbarConfig.$overlay;
			this.toolbar.$overlay.append( this.toolbar.$popups );
		}
	}

	if ( this.surfaces.indexOf( surface ) === -1 ) {
		throw new Error( 'Active surface must have been added first' );
	}
	if ( surface !== this.surface ) {
		this.surface = surface;
		this.setupToolbar( surface );
	}
};

/**
 * Get the target's active surface, if it exists
 *
 * @return {ve.ui.Surface|null}
 */
ve.init.Target.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Get the target's toolbar
 *
 * @return {ve.ui.TargetToolbar} Toolbar
 */
ve.init.Target.prototype.getToolbar = function () {
	if ( !this.toolbar ) {
		this.toolbar = new ve.ui.PositionedTargetToolbar( this, this.toolbarConfig );
	}
	return this.toolbar;
};

/**
 * Get the actions toolbar
 *
 * @deprecated
 * @return {ve.ui.TargetToolbar} Actions toolbar (same as the normal toolbar)
 */
ve.init.Target.prototype.getActions = function () {
	OO.ui.warnDeprecation( 'Target#getActions: Use #getToolbar instead ' +
		'(actions toolbar has been merged into the normal toolbar)' );
	if ( !this.actionsToolbar ) {
		this.actionsToolbar = this.getToolbar();
	}
	return this.actionsToolbar;
};

/**
 * Set up the toolbar, attaching it to a surface.
 *
 * @param {ve.ui.Surface} surface
 */
ve.init.Target.prototype.setupToolbar = function ( surface ) {
	var toolbar = this.getToolbar();
	if ( this.actionGroups.length ) {
		// Backwards-compatibility
		if ( !this.actionsToolbar ) {
			this.actionsToolbar = this.getToolbar();
		}
	}

	toolbar.connect( this, {
		resize: 'onToolbarResize',
		active: 'onToolbarActive'
	} );

	if ( surface.nullSelectionOnBlur ) {
		toolbar.$element
			.on( 'focusin', function () {
				// When the focus moves to the toolbar, deactivate the surface but keep the selection (even if
				// nullSelectionOnBlur is true), to allow tools to act on that selection.
				surface.getView().deactivate( /* showAsActivated= */ true );
			} )
			.on( 'focusout', function ( e ) {
				var newFocusedElement = e.relatedTarget;
				if ( !OO.ui.contains( [ toolbar.$element[ 0 ], toolbar.$overlay[ 0 ] ], newFocusedElement, true ) ) {
					// When the focus moves out of the toolbar:
					if ( OO.ui.contains( surface.getView().$element[ 0 ], newFocusedElement, true ) ) {
						// When the focus moves out of the toolbar, and it moves back into the surface,
						// make sure the previous selection is restored.
						var previousSelection = surface.getModel().getSelection();
						surface.getView().activate();
						if ( !previousSelection.isNull() ) {
							surface.getModel().setSelection( previousSelection );
						}
					} else {
						// When the focus moves out of the toolbar, and it doesn't move back into the surface,
						// blur the surface explicitly to restore the expected nullSelectionOnBlur behavior.
						// The surface was deactivated, so it doesn't react to the focus change itself.
						surface.getView().blur();
					}
				}
			} );
	}

	this.actionGroups.forEach( function ( group ) {
		group.align = 'after';
	} );
	var groups = [].concat( this.toolbarGroups, this.actionGroups );

	toolbar.setup( groups, surface );
	this.attachToolbar();
	var rAF = window.requestAnimationFrame || setTimeout;
	rAF( this.onContainerScrollHandler );
};

/**
 * Deactivate the surface. Maybe save some properties that should be restored when it's activated.
 *
 * @protected
 */
ve.init.Target.prototype.deactivateSurfaceForToolbar = function () {
	var view = this.getSurface().getView();
	// Surface may already be deactivated (e.g. link inspector is open)
	this.wasSurfaceActive = !view.deactivated;
	if ( this.wasSurfaceActive ) {
		view.deactivate();
	}
};

/**
 * Activate the surface. Restore any properties saved in #deactivate.
 *
 * @protected
 */
ve.init.Target.prototype.activateSurfaceForToolbar = function () {
	var view = this.getSurface().getView();
	// For non-collapsed mobile selections, don't reactivate
	if ( this.wasSurfaceActive && !( OO.ui.isMobile() && !view.getModel().getSelection().isCollapsed() ) ) {
		view.activate();
	}
};

/**
 * Handle active events from the toolbar
 *
 * @param {boolean} active The toolbar is active
 */
ve.init.Target.prototype.onToolbarActive = function ( active ) {
	// Deactivate the surface when the toolbar is active (T109529, T201329)
	if ( active ) {
		this.activeToolbars++;
		if ( this.activeToolbars === 1 ) {
			this.deactivateSurfaceForToolbar();
		}
	} else {
		this.activeToolbars--;
		if ( !this.activeToolbars ) {
			this.activateSurfaceForToolbar();
		}
	}
};

/**
 * Teardown the toolbar
 *
 * @return {jQuery.Promise} Promise which resolves when the toolbar has been torn down
 */
ve.init.Target.prototype.teardownToolbar = function () {
	if ( this.toolbar ) {
		this.toolbar.destroy();
		this.toolbar = null;
	}
	if ( this.actionsToolbar ) {
		this.actionsToolbar = null;
	}
	return ve.createDeferred().resolve().promise();
};

/**
 * Attach the toolbar to the DOM
 */
ve.init.Target.prototype.attachToolbar = function () {
	var toolbar = this.getToolbar();
	toolbar.$element.insertBefore( toolbar.getSurface().$element );
	toolbar.initialize();
};
