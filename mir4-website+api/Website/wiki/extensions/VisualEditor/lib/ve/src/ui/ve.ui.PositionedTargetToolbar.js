/*!
 * VisualEditor UserInterface PositionedTargetToolbar class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface positioned target toolbar.
 *
 * @class
 * @extends ve.ui.TargetToolbar
 *
 * @constructor
 * @param {ve.init.Target} target
 * @param {Object} [config] Configuration options
 * @cfg {boolean} [floatable] Toolbar can float when scrolled off the page
 */
ve.ui.PositionedTargetToolbar = function VeUiPositionedTargetToolbar( target, config ) {
	config = config || {};

	// Parent constructor
	ve.ui.PositionedTargetToolbar.super.apply( this, arguments );

	// Change default overlay to be this.$bar, instead of this.$element (T209192)
	// TODO: Upstream to OOUI
	if ( !config.$overlay ) {
		this.$overlay = this.$bar.append( this.$popups );
	}

	// Properties
	this.floating = false;
	this.floatable = !!config.floatable;
	this.height = 0;
	this.elementOffset = null;
	this.onWindowScrollThrottled = ve.throttle( this.onWindowScroll.bind( this ), 250 );

	// Initialization
	this.$element.addClass( 've-ui-positionedTargetToolbar' );
};

/* Inheritance */

OO.inheritClass( ve.ui.PositionedTargetToolbar, ve.ui.TargetToolbar );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.PositionedTargetToolbar.prototype.setup = function ( groups, surface ) {
	var toolbarDialogs = surface.getToolbarDialogs();

	// Parent method
	ve.ui.PositionedTargetToolbar.super.prototype.setup.apply( this, arguments );

	if ( this.position === 'bottom' ) {
		this.$bar.prepend( toolbarDialogs.$element );
	} else {
		this.$bar.append( toolbarDialogs.$element );
	}
	toolbarDialogs.connect( this, {
		opening: 'onToolbarDialogsOpeningOrClosing',
		closing: 'onToolbarDialogsOpeningOrClosing'
	} );
	if ( this.isFloatable() ) {
		this.target.$scrollListener[ 0 ].addEventListener( 'scroll', this.onWindowScrollThrottled, { passive: true } );
	}
};

/**
 * @inheritdoc
 */
ve.ui.PositionedTargetToolbar.prototype.detach = function () {
	// Events
	if ( this.getSurface() ) {
		this.getSurface().getToolbarDialogs().disconnect( this );
		this.getSurface().getToolbarDialogs().clearWindows();
	}
	this.target.$scrollListener[ 0 ].removeEventListener( 'scroll', this.onWindowScrollThrottled );

	// Parent method
	ve.ui.PositionedTargetToolbar.super.prototype.detach.apply( this, arguments );
};

/**
 * @inheritdoc
 *
 * While toolbar floating is enabled,
 * the toolbar will stick to the top of the screen unless it would be over or under the last visible
 * branch node in the root of the document being edited, at which point it will stop just above it.
 */
ve.ui.PositionedTargetToolbar.prototype.onWindowResize = function () {
	ve.ui.Toolbar.super.prototype.onWindowResize.call( this );

	// Update offsets after resize (see #float)
	this.calculateOffset();

	if ( this.floating ) {
		this.$bar.css( {
			left: this.elementOffset.left,
			right: this.elementOffset.right
		} );
	}

	this.onViewportResize();
};

/**
 * Calculate the left and right offsets of the toolbar
 */
ve.ui.PositionedTargetToolbar.prototype.calculateOffset = function () {
	this.elementOffset = this.$element.offset();
	this.elementOffset.right = document.documentElement.clientWidth - this.$element[ 0 ].offsetWidth - this.elementOffset.left;
};

/**
 * Get height of the toolbar while floating
 *
 * @return {number} Height of the toolbar
 */
ve.ui.PositionedTargetToolbar.prototype.getHeight = function () {
	return this.height;
};

/**
 * Get toolbar element's offsets
 *
 * @return {Object} Toolbar element's offsets
 */
ve.ui.PositionedTargetToolbar.prototype.getElementOffset = function () {
	if ( !this.elementOffset ) {
		this.calculateOffset();
	}
	return this.elementOffset;
};

/**
 * Float the toolbar.
 */
ve.ui.PositionedTargetToolbar.prototype.float = function () {
	if ( !this.floating ) {
		this.height = this.$bar[ 0 ].offsetHeight;
		// When switching into floating mode, set the height of the wrapper and
		// move the bar to the same offset as the in-flow element
		this.$element
			.css( 'height', this.height )
			.addClass( 've-ui-toolbar-floating' );
		this.$bar.css( {
			left: this.elementOffset.left,
			right: this.elementOffset.right
		} );
		this.floating = true;
		this.emit( 'resize' );
		this.onViewportResize();
	}
};

/**
 * Reset the toolbar to it's default non-floating position.
 */
ve.ui.PositionedTargetToolbar.prototype.unfloat = function () {
	if ( this.floating ) {
		this.height = 0;
		this.$element
			.css( 'height', '' )
			.removeClass( 've-ui-toolbar-floating' );
		this.$bar.css( { left: '', right: '' } );
		this.floating = false;
		this.emit( 'resize' );
		this.onViewportResize();
	}
};

/**
 * Check if the toolbar is floating
 *
 * @return {boolean} The toolbar is floating
 */
ve.ui.PositionedTargetToolbar.prototype.isFloating = function () {
	return this.floating;
};

/**
 * Check if the toolbar can float
 *
 * @return {boolean} The toolbar can float
 */
ve.ui.PositionedTargetToolbar.prototype.isFloatable = function () {
	return this.floatable;
};

/**
 * Handle windows opening or closing in the toolbar window manager.
 *
 * @param {OO.ui.Window} win
 * @param {jQuery.Promise} openingOrClosing
 * @param {Object} data
 */
ve.ui.PositionedTargetToolbar.prototype.onToolbarDialogsOpeningOrClosing = function ( win, openingOrClosing ) {
	var $surface = this.getSurface().$element,
		transitionDuration = OO.ui.theme.getDialogTransitionDuration(),
		toolbar = this;

	// win.isOpened before promise means we are closing
	if ( win.constructor.static.position === 'side' && win.isOpened() ) {
		// First closing transition
		$surface.css(
			$surface.css( 'direction' ) === 'rtl' ? 'margin-left' : 'margin-right',
			''
		);
		win.$element.css( 'width', '' );
	}

	openingOrClosing.then( function () {
		if ( win.constructor.static.position === 'side' ) {
			// win.isOpened after promise means we are opening
			if ( win.isOpened() ) {
				var margin = $surface.css( 'direction' ) === 'rtl' ? 'margin-left' : 'margin-right';
				var originalMargin = parseFloat( $surface.css( margin ) );
				var width = win.getSizeProperties().width;
				toolbar.getSurface().$element
					.addClass( 've-ui-surface-toolbarDialog-side' )
					.css( margin, width + originalMargin );
				win.$element.css( 'width', width );
			} else {
				// Second closing transition
				toolbar.getSurface().$element.removeClass( 've-ui-surface-toolbarDialog-side' );
			}

			toolbar.onViewportResize();
			setTimeout( function () {
				toolbar.onViewportResize();
				toolbar.getSurface().getView().emit( 'position' );
			}, transitionDuration );
			toolbar.getSurface().getView().emit( 'position' );
		} else if ( win.constructor.static.position === 'below' ) {
			setTimeout( function () {
				toolbar.onViewportResize();
				toolbar.getSurface().getView().emit( 'position' );
			}, transitionDuration );
		}
		// Wait for window transition
		setTimeout( function () {
			if ( toolbar.floating ) {
				// Re-calculate height
				toolbar.unfloat();
				toolbar.float();
			}
		}, transitionDuration );
	} );
};

/**
 * Handle the visible part of the surface viewport change dimensions
 */
ve.ui.PositionedTargetToolbar.prototype.onViewportResize = function () {
	var surface = this.getSurface();

	if ( !surface ) {
		return;
	}

	var toolbarDialogs = surface.getToolbarDialogs();
	var win = toolbarDialogs.getCurrentWindow();

	if ( win && win.constructor.static.position === 'side' ) {
		var viewportDimensions = surface.getViewportDimensions();
		if ( viewportDimensions ) {
			toolbarDialogs.getCurrentWindow().$frame.css(
				'height', Math.min( surface.getBoundingClientRect().height, viewportDimensions.height )
			);
		}
	}
};

/**
 * Handle window scroll events
 */
ve.ui.PositionedTargetToolbar.prototype.onWindowScroll = function () {
	if ( !this.floating ) {
		this.onViewportResize();
	}
};
