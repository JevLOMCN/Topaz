/*!
 * VisualEditor ContentEditable FocusableNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable focusable node.
 *
 * Focusable elements have a special treatment by ve.ce.Surface. When the user selects only a single
 * node, if it is focusable, the surface will set the focusable node's focused state. Other systems,
 * such as the context, may also use a focusable node's $focusable property as a hint of where the
 * primary element in the node is. Typically, and by default, the primary element is the root
 * element, but in some cases it may need to be configured to be a specific child element within the
 * node's DOM rendering.
 *
 * If your focusable node changes size and the highlight must be redrawn, call redrawHighlights().
 * 'resizeEnd' and 'rerender' are already bound to call this.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {jQuery} [$focusable=this.$element] Primary element user is focusing on
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$bounding=$focusable] Element to consider for bounding box calculations (e.g.
 *   attaching inspectors)
 * @cfg {string[]} [classes] CSS classes to be added to the highlight container
 */
ve.ce.FocusableNode = function VeCeFocusableNode( $focusable, config ) {
	config = config || {};

	// Properties
	this.focused = false;
	this.highlighted = false;
	this.isFocusableSetup = false;
	this.$highlights = $( '<div>' ).addClass( 've-ce-focusableNode-highlights' )
		// Allow the highlight to take focus, so that focus is not removed from
		// the surface when clicking on it (T341681)
		.attr( 'tabIndex', 0 );
	this.$focusable = $focusable || this.$element;
	this.$bounding = config.$bounding || this.$focusable;
	this.focusableSurface = null;
	this.rects = null;
	this.boundingRect = null;
	this.startAndEndRects = null;
	this.icon = null;
	this.touchMoved = false;

	if ( Array.isArray( config.classes ) ) {
		// eslint-disable-next-line mediawiki/class-doc
		this.$highlights.addClass( config.classes );
	}

	// Use a debounced handler as some actions can trigger redrawHighlights
	// twice in quick succession resizeEnd+rerender
	this.redrawHighlightsDebounced = ve.debounce( this.redrawHighlights.bind( this ), 100 );

	// DOM changes
	this.$element
		.addClass( 've-ce-focusableNode' )
		.prop( 'contentEditable', 'false' );

	// Events
	this.connect( this, {
		setup: 'onFocusableSetup',
		teardown: 'onFocusableTeardown',
		resizeStart: 'onFocusableResizeStart',
		resizeEnd: 'onFocusableResizeEnd',
		rerender: 'onFocusableRerender'
	} );
};

/* Inheritance */

OO.initClass( ve.ce.FocusableNode );

/* Events */

/**
 * @event focus
 */

/**
 * @event blur
 */

/* Static properties */

/**
 * Icon to use when the rendering is considered not visible, as defined in #hasRendering
 *
 * No icon is show if null.
 *
 * @static
 * @property {string|null}
 * @inheritable
 */
ve.ce.FocusableNode.static.iconWhenInvisible = null;

/**
 * Command to execute when Enter is pressed while this node is focused, or when the node is double-clicked.
 *
 * @static
 * @property {string|null}
 * @inheritable
 */
ve.ce.FocusableNode.static.primaryCommandName = null;

/**
 * Command to execute when Delete or Backspace is pressed while this node is focused.
 *
 * @static
 * @property {string|null}
 * @inheritable
 */
ve.ce.FocusableNode.static.deleteCommandName = null;

/* Static methods */

/**
 * Get rects for an element
 *
 * @param {jQuery} $element Element to get highlights
 * @param {Object} [relativeRect] Rect with top & left to get position relative to
 * @return {Object} Object containing rects and boundingRect
 */
ve.ce.FocusableNode.static.getRectsForElement = function ( $element, relativeRect ) {
	var $set;
	var rects = [];

	function process( el ) {
		if ( el.classList.contains( 've-ce-noHighlight' ) ) {
			return;
		}

		var $el = $( el );

		// Don't descend if overflow is anything but visible as this prevents child
		// elements appearing beyond the bounding box of the parent, *unless* display
		// is inline, in which case the overflow setting will be ignored
		var overflow = $el.css( 'overflow' );
		if ( overflow && overflow !== 'visible' && $el.css( 'display' ) !== 'inline' ) {
			$set = $set.not( $el.find( '*' ) );
		}

		ve.batchPush( rects, el.getClientRects() );
	}

	$set = $element.find( '*' ).addBack();
	var i, l;
	// Calling process() may change $set.length
	for ( i = 0; i < $set.length; i++ ) {
		process( $set[ i ] );
	}

	rects = ve.minimizeRects( rects );

	// Elements with a width/height of 0 return a clientRect with a width/height of 1
	// As elements with an actual width/height of 1 aren't that useful anyway, just
	// throw away anything that is <=1
	var filteredRects = rects.filter( function ( rect ) {
		return rect.width > 1 && rect.height > 1;
	} );
	// But if this filtering doesn't leave any rects at all, then we do want to use the 1px rects
	if ( filteredRects.length > 0 ) {
		rects = filteredRects;
	}

	var boundingRect = null;

	for ( i = 0, l = rects.length; i < l; i++ ) {
		// Translate to relative
		if ( relativeRect ) {
			rects[ i ] = ve.translateRect( rects[ i ], -relativeRect.left, -relativeRect.top );
		}
		if ( !boundingRect ) {
			boundingRect = ve.copy( rects[ i ] );
		} else {
			boundingRect.top = Math.min( boundingRect.top, rects[ i ].top );
			boundingRect.left = Math.min( boundingRect.left, rects[ i ].left );
			boundingRect.bottom = Math.max( boundingRect.bottom, rects[ i ].bottom );
			boundingRect.right = Math.max( boundingRect.right, rects[ i ].right );
		}
	}
	if ( boundingRect ) {
		boundingRect.width = boundingRect.right - boundingRect.left;
		boundingRect.height = boundingRect.bottom - boundingRect.top;
	}

	return {
		rects: rects,
		boundingRect: boundingRect
	};
};

/* Methods */

/**
 * Create a highlight element.
 *
 * @return {jQuery} A highlight element
 */
ve.ce.FocusableNode.prototype.createHighlight = function () {
	// eslint-disable-next-line mediawiki/class-doc
	return $( '<div>' )
		.addClass( [ 've-ce-focusableNode-highlight' ].concat( this.getExtraHighlightClasses() ) )
		.prop( {
			title: this.constructor.static.getDescription( this.model ),
			draggable: true
		} )
		.on( {
			dragstart: this.onFocusableDragStart.bind( this ),
			dragend: this.onFocusableDragEnd.bind( this )
		} );
};

/**
 * Array of CSS classes to add to highlights
 *
 * @return {string[]}
 */
ve.ce.FocusableNode.prototype.getExtraHighlightClasses = function () {
	return this.generatedContentsInvalid ? [ 've-ce-focusableNode-highlight-error' ] : [];
};

/**
 * Handle node setup.
 */
ve.ce.FocusableNode.prototype.onFocusableSetup = function () {
	// Exit if already setup or not attached
	if ( this.isFocusableSetup || !this.root ) {
		return;
	}

	this.focusableSurface = this.root.getSurface();

	// DOM changes (duplicated from constructor in case this.$element is replaced)
	// eslint-disable-next-line no-jquery/no-class-state
	if ( !this.$element.hasClass( 've-ce-focusableNode' ) ) {
		// Optimization: If this.$element has the correct class assume it has already
		// been setup in the construct and avoid expensive DOM property manipulation
		this.$element
			.addClass( 've-ce-focusableNode' )
			.prop( 'contentEditable', 'false' );
	}

	// Events
	this.$focusable.on( {
		'mouseenter.ve-ce-focusableNode': this.onFocusableMouseEnter.bind( this ),
		'touchstart.ve-ce-focusableNode': this.onFocusableTouchStart.bind( this ),
		'touchmove.ve-ce-focusableNode': this.onFocusableTouchMove.bind( this ),
		'mousedown.ve-ce-focusableNode touchend.ve-ce-focusableNode': this.onFocusableMouseDown.bind( this )
	} );
	// $element is ce=false so make sure nothing happens when you click
	// on it, just in case the browser decides to do something.
	// If $element == $focusable then this can be skipped as $focusable already
	// handles mousedown events.
	if ( !this.$element.is( this.$focusable ) ) {
		this.$element.on( {
			'mousedown.ve-ce-focusableNode': function ( e ) {
				if ( !ve.isContentEditable( e.target ) ) {
					e.preventDefault();
				}
			}
		} );
	}
	// Note that preventing default on mousedown doesn't suppress click
	// events, so link navigation would still occur:
	this.$element.on( {
		'click.ve-ce-focusableNode': function ( e ) {
			if ( !ve.isContentEditable( e.target ) && e.which === OO.ui.MouseButtons.LEFT ) {
				e.preventDefault();
			}
		}
	} );

	if ( this.constructor.static.iconWhenInvisible ) {
		// Set up the invisible icon, and watch for its continued necessity if
		// unloaded images which don't specify their width or height are
		// involved.
		this.$element
			.find( 'img:not([width]),img:not([height])' )
			.addBack( 'img:not([width]),img:not([height])' )
			.on( 'load', this.updateInvisibleIcon.bind( this ) );
		this.updateInvisibleIcon();
	}

	// A node may be re-setup when focused. redrawHighlights will only do
	// something if the node is currently focused.
	this.redrawHighlightsDebounced();

	this.isFocusableSetup = true;
};

/**
 * Update the state of icon if this node is invisible
 *
 * If the node doesn't have a visible rendering, we insert an icon to represent
 * it. If the icon was already present, and this is called again when rendering
 * has developed, we remove the icon.
 */
ve.ce.FocusableNode.prototype.updateInvisibleIcon = function () {
	if ( !this.constructor.static.iconWhenInvisible ) {
		return;
	}

	// Make sure any existing icon is detached before measuring
	if ( this.icon ) {
		this.icon.$element.detach();
	}
	var showIcon = !this.hasRendering();
	var rAF = window.requestAnimationFrame || setTimeout;
	var node = this;

	// Defer updating the DOM. If we don't do this, the hasRendering() call for the next
	// FocusableNode will force a reflow, which is slow.
	rAF( function () {
		node.updateInvisibleIconSync( showIcon );
	} );
};

/**
 * Synchronous part of #updateInvisibleIconSync
 *
 * @param {boolean} showIcon Show the icon
 * @private
 */
ve.ce.FocusableNode.prototype.updateInvisibleIconSync = function ( showIcon ) {
	if ( !this.getModel() ) {
		// Check the node hasn't been destroyed, as this method is called after an rAF
		return;
	}
	if ( showIcon ) {
		// Don't try to append to void tags, or unrendered tags
		var voidAndHiddenTypes = ve.elementTypes.void.concat( 'style', 'script' );
		var $firstElement = this.$element.not( voidAndHiddenTypes.join( ',' ) ).first();
		this.createInvisibleIcon();
		if (
			// Not needed if node is not attached (e.g. if used in the converter)
			document.body.contains( $firstElement[ 0 ] ) &&
			// eslint-disable-next-line no-jquery/no-sizzle
			!$firstElement.is( ':visible' )
		) {
			// The first element to which we want to attach our icon is invisible.
			// In this case make sure it *is* visible, so the button is visible,
			// but remove the contents, so we don't start showing them (T305110).
			$firstElement.empty().css( 'display', 'inline-block' );
		}
		$firstElement
			.addClass( 've-ce-focusableNode-invisible' )
			.prepend( this.icon.$element );
	} else if ( this.icon ) {
		this.$element.removeClass( 've-ce-focusableNode-invisible' );
		this.icon.$element.detach();
	}
};

/**
 * Create a element to show if the node is invisible
 */
ve.ce.FocusableNode.prototype.createInvisibleIcon = function () {
	if ( this.icon ) {
		return;
	}
	this.icon = new OO.ui.ButtonWidget( {
		classes: [ 've-ce-focusableNode-invisibleIcon' ],
		framed: false,
		// Make button unfocusable, T198912
		tabIndex: null,
		icon: this.constructor.static.iconWhenInvisible
	} );
	this.updateInvisibleIconLabel();
};

/**
 * Get a label for the invisible icon
 *
 * Defaults to #getDescription
 *
 * @return {jQuery|string|OO.ui.HtmlSnippet|Function|null} Invisible icon label
 */
ve.ce.FocusableNode.prototype.getInvisibleIconLabel = function () {
	return this.model ? this.constructor.static.getDescription( this.model ) : '';
};

/**
 * Update the invisible icon's label
 */
ve.ce.FocusableNode.prototype.updateInvisibleIconLabel = function () {
	if ( this.icon ) {
		this.icon.setLabel( this.getInvisibleIconLabel() || null );
	}
};

/**
 * Handle node teardown.
 */
ve.ce.FocusableNode.prototype.onFocusableTeardown = function () {
	// Exit if not setup or not attached
	if ( !this.isFocusableSetup || !this.root ) {
		return;
	}

	// Events
	this.$focusable.off( '.ve-ce-focusableNode' );
	this.$element.off( '.ve-ce-focusableNode' );

	// Highlights
	this.clearHighlights();

	// DOM changes
	this.$element
		.removeClass( 've-ce-focusableNode' )
		.removeProp( 'contentEditable' );

	this.focusableSurface = null;
	this.isFocusableSetup = false;
};

/**
 * Handle highlight mouse down events.
 *
 * @param {jQuery.Event} e Mouse down event
 */
ve.ce.FocusableNode.prototype.onFocusableMouseDown = function ( e ) {
	if ( e.type === 'touchend' && this.touchMoved ) {
		return;
	}

	if ( this.isInContentEditableDisabled() ) {
		return;
	}

	var node = this,
		surfaceModel = this.focusableSurface.getModel(),
		selection = surfaceModel.getSelection(),
		nodeRange = this.model.getOuterRange();

	if ( e.which === OO.ui.MouseButtons.RIGHT ) {
		// The same technique is used in ve.ce.TableNode:
		// Make ce=true so we get cut/paste options in the context menu
		this.$highlights.prop( 'contentEditable', 'true' );
		// Select the clicked element so we get a copy option in the context menu
		ve.selectElement( this.$highlights[ 0 ] );
		setTimeout( function () {
			// Undo ce=true as soon as the context menu is shown
			node.$highlights.prop( 'contentEditable', 'false' );
			node.focusableSurface.preparePasteTargetForCopy();
		} );
	}

	// Wait for native selection to change before correcting
	setTimeout( function () {
		// Check surface still exists after timeout
		if ( node.focusableSurface ) {
			var range = selection instanceof ve.dm.LinearSelection && selection.getRange();
			surfaceModel.getLinearFragment(
				e.shiftKey && range ?
					ve.Range.static.newCoveringRange(
						[ range, nodeRange ], range.from > nodeRange.from
					) :
					nodeRange
			).select();
			node.focusableSurface.updateActiveAnnotations();
			// Ensure surface is active as native 'focus' event won't be fired
			node.focusableSurface.activate();
		}
	} );
};

/**
 * Handle highlight double click events.
 *
 * @param {jQuery.Event} e Double click event
 */
ve.ce.FocusableNode.prototype.onFocusableDblClick = function () {
	if ( this.isInContentEditableDisabled() ) {
		return;
	}
	if ( this.getModel().isEditable() ) {
		this.executeCommand();
	}
};

/**
 * Execute the command associated with this node.
 */
ve.ce.FocusableNode.prototype.executeCommand = function () {
	if ( !this.model.isInspectable() ) {
		return;
	}
	var surface = this.focusableSurface.getSurface();
	var command = surface.commandRegistry.getCommandForNode( this );
	if ( command ) {
		command.execute( surface );
	}
};

/**
 * Handle element drag start.
 *
 * @param {jQuery.Event} e Drag start event
 */
ve.ce.FocusableNode.prototype.onFocusableDragStart = function ( e ) {
	if ( this.focusableSurface ) {
		// Pass event up to the surface
		this.focusableSurface.onDocumentDragStart( e );
	}
};

/**
 * Handle element drag end.
 *
 * If a relocation actually takes place the node is destroyed before this events fires.
 *
 * @param {jQuery.Event} e Drag end event
 */
ve.ce.FocusableNode.prototype.onFocusableDragEnd = function () {
	// endRelocation is usually triggered by onDocumentDrop in the surface, but if it isn't
	// trigger it here instead
	if ( this.focusableSurface ) {
		this.focusableSurface.endRelocation();
	}
};

/**
 * Handle mouse enter events.
 *
 * @param {jQuery.Event} e Mouse enter event
 */
ve.ce.FocusableNode.prototype.onFocusableMouseEnter = function () {
	if ( !this.root.getSurface().dragging && !this.root.getSurface().resizing && !this.isInContentEditableDisabled() ) {
		this.createHighlights();
	}
};

/**
 * Handle touch start events.
 *
 * @param {jQuery.Event} e Touch start event
 */
ve.ce.FocusableNode.prototype.onFocusableTouchStart = function () {
	this.touchMoved = false;
};

/**
 * Handle touch move events.
 *
 * @param {jQuery.Event} e Touch move event
 */
ve.ce.FocusableNode.prototype.onFocusableTouchMove = function () {
	this.touchMoved = true;
};

/**
 * Handle surface mouse move events.
 *
 * @param {jQuery.Event} e Mouse move event
 */
ve.ce.FocusableNode.prototype.onSurfaceMouseMove = function ( e ) {
	var $target = $( e.target );
	if (
		// eslint-disable-next-line no-jquery/no-class-state
		!$target.hasClass( 've-ce-focusableNode-highlight' ) &&
		!OO.ui.contains( this.$focusable.toArray(), $target[ 0 ], true )
	) {
		this.clearHighlights();
	}
};

/**
 * Handle surface mouse leave events.
 *
 * @param {jQuery.Event} e Mouse leave event
 */
ve.ce.FocusableNode.prototype.onSurfaceMouseLeave = function ( e ) {
	if ( e.relatedTarget === null ) {
		this.clearHighlights();
	}
};

/**
 * Handle resize start events.
 */
ve.ce.FocusableNode.prototype.onFocusableResizeStart = function () {
	this.clearHighlights();
};

/**
 * Handle resize end event.
 */
ve.ce.FocusableNode.prototype.onFocusableResizeEnd = function () {
	this.redrawHighlightsDebounced();
};

/**
 * Handle rerender event.
 */
ve.ce.FocusableNode.prototype.onFocusableRerender = function () {
	if ( this.focused && this.focusableSurface ) {
		this.redrawHighlightsDebounced();
		// Reposition menu
		this.focusableSurface.getSurface().getContext().updateDimensions( true );
	}
};

/**
 * Check if node is focused.
 *
 * @return {boolean} Node is focused
 */
ve.ce.FocusableNode.prototype.isFocused = function () {
	return this.focused;
};

/**
 * Set the selected state of the node.
 *
 * @param {boolean} value Node is focused
 * @fires focus
 * @fires blur
 */
ve.ce.FocusableNode.prototype.setFocused = function ( value ) {
	value = !!value;
	if ( this.focused !== value ) {
		this.focused = value;
		if ( this.focused ) {
			this.emit( 'focus' );
			this.$element.addClass( 've-ce-focusableNode-focused' );
			this.createHighlights();
			// this.focused may have changed, so re-attach in the correct container
			this.focusableSurface.appendHighlights( this.$highlights, this.focused );
			this.focusableSurface.$element.off( '.ve-ce-focusableNode' );
		} else {
			this.emit( 'blur' );
			this.$element.removeClass( 've-ce-focusableNode-focused' );
			this.$highlights.removeClass( 've-ce-focusableNode-highlights-deactivated' );
			this.clearHighlights();
		}
	}
};

/**
 * Creates highlights.
 */
ve.ce.FocusableNode.prototype.createHighlights = function () {
	if ( this.highlighted ) {
		return;
	}

	this.$highlights.on( {
		mousedown: this.onFocusableMouseDown.bind( this ),
		dblclick: this.onFocusableDblClick.bind( this )
	} );

	this.highlighted = true;

	this.positionHighlights();

	this.focusableSurface.appendHighlights( this.$highlights, this.focused );
	this.focusableSurface.connect( this, {
		position: 'positionHighlights',
		activation: 'onSurfaceActivation'
	} );

	// Events
	if ( !this.focused ) {
		this.focusableSurface.$element.on( {
			'mousemove.ve-ce-focusableNode': this.onSurfaceMouseMove.bind( this ),
			'mouseleave.ve-ce-focusableNode': this.onSurfaceMouseLeave.bind( this )
		} );
	}
};

/**
 * Handle activation events from the surface
 */
ve.ce.FocusableNode.prototype.onSurfaceActivation = function () {
	this.$highlights.toggleClass( 've-ce-focusableNode-highlights-deactivated', !!this.focusableSurface.isShownAsDeactivated() );
};

/**
 * Clears highlight.
 */
ve.ce.FocusableNode.prototype.clearHighlights = function () {
	if ( !this.highlighted ) {
		return;
	}
	this.$highlights.remove().empty();
	this.focusableSurface.$element.off( '.ve-ce-focusableNode' );
	this.focusableSurface.disconnect( this );
	this.highlighted = false;
	this.boundingRect = null;
};

/**
 * Redraws highlight.
 */
ve.ce.FocusableNode.prototype.redrawHighlights = function () {
	if ( this.focused && this.focusableSurface ) {
		// setFocused will call clearHighlights/createHighlights
		// and also re-bind events.
		this.setFocused( false );
		this.setFocused( true );
	}
};

/**
 * Re-calculate position of highlights
 */
ve.ce.FocusableNode.prototype.calculateHighlights = function () {
	// Protect against calling before/after surface setup/teardown
	if ( !this.focusableSurface ) {
		this.rects = [];
		this.boundingRect = null;
		this.startAndEndRects = null;
		return;
	}

	var surfaceOffset = this.focusableSurface.getSurface().getBoundingClientRect();

	var allRects = this.constructor.static.getRectsForElement( this.$focusable, surfaceOffset );

	this.rects = allRects.rects;
	this.boundingRect = allRects.boundingRect;
	// startAndEndRects is lazily evaluated in getStartAndEndRects from rects
	this.startAndEndRects = null;
};

/**
 * Positions highlights, and remove collapsed ones
 */
ve.ce.FocusableNode.prototype.positionHighlights = function () {
	if ( !this.highlighted ) {
		return;
	}

	this.calculateHighlights();
	this.$highlights.empty()
		// Append something selectable for right-click copy
		.append( $( '<span>' ).addClass( 've-ce-focusableNode-highlight-selectable' ).text( '\u00a0' ) );

	for ( var i = 0, l = this.rects.length; i < l; i++ ) {
		var $highlight = this.createHighlight();
		this.$highlights.append(
			$highlight.css( {
				top: this.rects[ i ].top,
				left: this.rects[ i ].left,
				width: this.rects[ i ].width,
				height: this.rects[ i ].height
			} )
		);
	}
};

/**
 * Get list of rectangles outlining the shape of the node relative to the surface
 *
 * @return {Object[]} List of rectangle objects
 */
ve.ce.FocusableNode.prototype.getRects = function () {
	if ( !this.highlighted ) {
		this.calculateHighlights();
	}
	return this.rects;
};

/**
 * Get the bounding rectangle of the focusable node highlight relative to the surface
 *
 * @return {Object|null} Top, left, bottom & right positions of the focusable node relative to the surface
 */
ve.ce.FocusableNode.prototype.getBoundingRect = function () {
	if ( !this.$bounding.is( this.$focusable ) ) {
		var surfaceOffset = this.focusableSurface.getSurface().getBoundingClientRect();
		var allRects = this.constructor.static.getRectsForElement( this.$bounding, surfaceOffset );
		return allRects.boundingRect;
	}
	if ( !this.highlighted ) {
		this.calculateHighlights();
	}
	return this.boundingRect;
};

/**
 * Get start and end rectangles of an inline focusable node relative to the surface
 *
 * @return {Object.<string,Object>|null} Start and end rectangles
 */
ve.ce.FocusableNode.prototype.getStartAndEndRects = function () {
	if ( !this.highlighted ) {
		this.calculateHighlights();
	}
	if ( !this.startAndEndRects ) {
		this.startAndEndRects = ve.getStartAndEndRects( this.rects );
	}
	return this.startAndEndRects;
};

/**
 * Check if the rendering is visible
 *
 * "Visible", in this case, is defined as at least 10px × 4px
 * or 4px × 10px in dimensions (T307527)
 *
 * @return {boolean} The node has a visible rendering
 */
ve.ce.FocusableNode.prototype.hasRendering = function () {
	var visible = false;

	function checkSize( width, height ) {
		return ( width >= 10 && height >= 4 ) ||
			( height >= 10 && width >= 4 );
	}

	this.$element.each( function () {
		if (
			checkSize( this.offsetWidth, this.offsetHeight ) ||
			// Check width/height attribute as well. (T125767)
			checkSize( this.width, this.height )
		) {
			visible = true;
			return false;
		}
	} );
	return visible;
};

/**
 * Check if the node is inside a ve.ce.ContentEditableNode with editing disabled
 *
 * Ignore nodes which just disable CE in the DOM manually (e.g. TableNode)
 * as focusables should still be highlightable in these.
 *
 * @return {boolean} Editing disabled
 */
ve.ce.FocusableNode.prototype.isInContentEditableDisabled = function () {
	return !!this.traverseUpstream( function ( node ) {
		return !(
			node.isContentEditable && !node.isContentEditable()
		);
	} );
};
