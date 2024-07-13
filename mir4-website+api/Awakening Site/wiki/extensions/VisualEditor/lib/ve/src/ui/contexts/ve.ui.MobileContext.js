/*!
 * VisualEditor UserInterface MobileContext class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface context that displays inspector full screen.
 *
 * @class
 * @extends ve.ui.LinearContext
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {Object} [config] Configuration options
 */
ve.ui.MobileContext = function VeUiMobileContext() {
	// Parent constructor
	ve.ui.MobileContext.super.apply( this, arguments );

	this.openingTimeout = null;

	this.closeButton = new OO.ui.ButtonWidget( {
		classes: [ 've-ui-mobileContext-close' ],
		framed: false,
		label: ve.msg( 'visualeditor-contextitemwidget-label-close' ),
		invisibleLabel: true,
		icon: 'close'
	} );

	// Events
	this.closeButton.connect( this, { click: 'onCloseButtonClick' } );
	this.inspectors.connect( this, {
		setup: [ 'toggle', true ],
		teardown: [ 'toggle', false ]
	} );

	// Initialization
	this.$element.addClass( 've-ui-mobileContext' );
	this.$group.addClass( 've-ui-mobileContext-menu' );
	this.surface.getGlobalOverlay().$element.append( this.inspectors.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MobileContext, ve.ui.LinearContext );

/* Static Properties */

ve.ui.MobileContext.static.isMobile = true;

/* Methods */

/**
 * Handle click events from the close button
 */
ve.ui.MobileContext.prototype.onCloseButtonClick = function () {
	this.hide();
	ve.track( 'activity.mobileContext', { action: 'context-close' } );
};

/**
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.setupMenuItems = function () {
	// Parent method
	ve.ui.MobileContext.super.prototype.setupMenuItems.apply( this, arguments );

	// Ensure close button is at start after $group has been modified.
	this.$group.prepend( this.closeButton.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.createInspectorWindowManager = function () {
	return new ve.ui.MobileWindowManager( this.surface, {
		factory: ve.ui.windowFactory,
		overlay: this.surface.getGlobalOverlay()
	} );
};

/**
 * Stripped down version of onInspectorOpening as we don't need to hide/show
 * the context because the inspector is full screen.
 *
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.onInspectorOpening = function ( win, opening ) {
	var context = this,
		observer = this.surface.getView().surfaceObserver;

	this.inspector = win;

	// Shut down the SurfaceObserver as soon as possible, so it doesn't get confused
	// by the selection moving around in IE. Will be reenabled when inspector closes.
	// FIXME this should be done in a nicer way, managed by the Surface classes
	observer.pollOnce();
	observer.stopTimerLoop();

	opening
		.then( function ( opened ) {
			opened.then( function ( closed ) {
				closed.always( function () {
					context.inspector = null;
					// Reenable observer
					observer.startTimerLoop();
					context.afterContextChange();
				} );
			} );
		} );
};

/**
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.toggleMenu = function ( show ) {
	var context = this;
	show = show === undefined ? !this.choosing : !!show;

	if ( show !== this.choosing ) {
		if ( show ) {
			if ( this.hideMenuTimeout ) {
				clearTimeout( this.hideMenuTimeout );
				// Ensure menu is torn down before setting up again.
				ve.ui.MobileContext.super.prototype.toggleMenu.call( this, false );
			}
			// Parent method
			ve.ui.MobileContext.super.prototype.toggleMenu.call( this, true );
		} else {
			this.hideMenuTimeout = setTimeout( function () {
				// Parent method
				ve.ui.MobileContext.super.prototype.toggleMenu.call( context, false );
			}, 100 );
		}
	}

	return this;
};

/**
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.toggle = function ( show ) {
	var context = this;

	show = show === undefined ? !this.visible : !!show;
	if ( show && !this.visible ) {
		var deferred = ve.createDeferred();
		// Set opening flag immediately
		this.openingTimeout = setTimeout( function () {
			// Parent method
			ve.ui.MobileContext.super.prototype.toggle.call( context, true );
			context.emit( 'resize' );
			deferred.resolve();
			context.openingTimeout = null;
		}, 250 );
		return deferred;
	} else {
		if ( this.openingTimeout ) {
			clearTimeout( this.openingTimeout );
			this.openingTimeout = null;
		}
		setTimeout( function () {
			context.emit( 'resize' );
		}, 100 );
		// Parent method
		return ve.ui.MobileContext.super.prototype.toggle.call( context, show );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.isVisible = function () {
	return this.visible || !!this.openingTimeout;
};

/**
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.isInspectable = function () {
	// Parent method
	return ve.ui.MobileContext.super.prototype.isInspectable.call( this ) &&
		// Suppress context when virtual keyboard is visible
		!this.surface.getView().hasNativeCursorSelection();
};

/**
 * @inheritdoc
 */
ve.ui.MobileContext.prototype.getSurfacePadding = function () {
	return { bottom: this.$element[ 0 ].clientHeight };
};
