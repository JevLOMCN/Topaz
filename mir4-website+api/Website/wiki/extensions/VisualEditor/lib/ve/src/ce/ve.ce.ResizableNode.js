/*!
 * VisualEditor ContentEditable ResizableNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable resizable node.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {jQuery} [$resizable=this.$element] Resizable DOM element
 * @param {Object} [config] Configuration options
 * @cfg {number|null} [snapToGrid=10] Snap to a grid of size X when the shift key is held. Null disables.
 * @cfg {boolean} [outline=false] Resize using an outline of the element only, don't live preview.
 * @cfg {boolean} [showSizeLabel=true] Show a label with the current dimensions while resizing
 * @cfg {boolean} [showScaleLabel=true] Show a label with the current scale while resizing
 */
ve.ce.ResizableNode = function VeCeResizableNode( $resizable, config ) {
	config = config || {};

	// Properties
	this.$resizable = $resizable || this.$element;
	this.resizing = false;
	this.$resizeHandles = $( '<div>' );
	this.snapToGrid = config.snapToGrid !== undefined ? config.snapToGrid : 10;
	this.outline = !!config.outline;
	this.showSizeLabel = config.showSizeLabel !== false;
	this.showScaleLabel = config.showScaleLabel !== false;
	// Only gets enabled when the original dimensions are provided
	this.canShowScaleLabel = false;
	if ( this.showSizeLabel || this.showScaleLabel ) {
		this.$sizeText = $( '<span>' ).addClass( 've-ce-resizableNode-sizeText' );
		this.$sizeLabel = $( '<div>' ).addClass( 've-ce-resizableNode-sizeLabel' ).append( this.$sizeText );
	}
	this.resizableOffset = null;
	this.resizableSurface = null;

	// Events
	this.connect( this, {
		focus: 'onResizableFocus',
		blur: 'onResizableBlur',
		setup: 'onResizableSetup',
		teardown: 'onResizableTeardown',
		resizing: 'onResizableResizing',
		resizeEnd: 'onResizableFocus',
		rerender: 'onResizableFocus',
		align: 'onResizableAlign'
	} );
	this.model.connect( this, {
		attributeChange: 'onResizableAttributeChange'
	} );

	// Initialization
	this.$resizeHandles
		.addClass( 've-ce-resizableNode-handles' )
		.append( $( '<div>' )
			.addClass( 've-ce-resizableNode-nwHandle' )
			.data( 'handle', 'nw' ) )
		.append( $( '<div>' )
			.addClass( 've-ce-resizableNode-neHandle' )
			.data( 'handle', 'ne' ) )
		.append( $( '<div>' )
			.addClass( 've-ce-resizableNode-seHandle' )
			.data( 'handle', 'se' ) )
		.append( $( '<div>' )
			.addClass( 've-ce-resizableNode-swHandle' )
			.data( 'handle', 'sw' ) );
};

/* Inheritance */

OO.initClass( ve.ce.ResizableNode );

/* Events */

/**
 * @event resizeStart
 */

/**
 * @event resizing
 * @param {Object} dimensions Dimension object containing width & height
 */

/**
 * @event resizeEnd
 */

/* Methods */

/**
 * Check if the node is resizable in its current state
 *
 * @return {boolean} The node is currently resizable
 */
ve.ce.ResizableNode.prototype.isResizable = function () {
	return this.$resizable && !!this.$resizable.length && !OO.ui.isMobile() &&
		!( this.root && this.root.getSurface() && this.root.getSurface().isReadOnly() );
};

/**
 * Get and cache the relative offset of the $resizable node
 *
 * @return {Object} Position coordinates, containing top & left
 */
ve.ce.ResizableNode.prototype.getResizableOffset = function () {
	if ( !this.resizableOffset ) {
		this.resizableOffset = OO.ui.Element.static.getRelativePosition(
			this.$resizable, this.resizableSurface.getSurface().$element
		);
	}
	return this.resizableOffset;
};

/**
 * Set the original dimensions of the scalable object
 *
 * @param {Object} dimensions
 */
ve.ce.ResizableNode.prototype.setOriginalDimensions = function ( dimensions ) {
	if ( !this.isResizable() ) {
		return;
	}

	var scalable = this.model.getScalable();

	scalable.setOriginalDimensions( dimensions );

	// If dimensions are valid and the scale label is desired, enable it
	this.canShowScaleLabel = this.showScaleLabel &&
		scalable.getOriginalDimensions().width &&
		scalable.getOriginalDimensions().height;
};

/**
 * Hide the size label
 */
ve.ce.ResizableNode.prototype.hideSizeLabel = function () {
	if ( !this.isResizable() ) {
		return;
	}

	var node = this;
	// Defer the removal of this class otherwise other DOM changes may cause
	// the opacity transition to not play out smoothly
	setTimeout( function () {
		node.$sizeLabel.removeClass( 've-ce-resizableNode-sizeLabel-resizing' );
	} );
	// Actually hide the size label after it's done animating
	setTimeout( function () {
		node.$sizeLabel.addClass( 'oo-ui-element-hidden' );
	}, 200 );
};

/**
 * Update the contents and position of the size label
 */
ve.ce.ResizableNode.prototype.updateSizeLabel = function () {
	if ( !this.isResizable() ) {
		return;
	}
	if ( !this.showSizeLabel && !this.canShowScaleLabel ) {
		return;
	}

	var scalable = this.model.getScalable();
	var dimensions = scalable.getCurrentDimensions();
	var offset = this.getResizableOffset();
	var minWidth = ( this.showSizeLabel ? 100 : 0 ) + ( this.showScaleLabel ? 30 : 0 );

	var top, height;
	// Put the label on the outside when too narrow
	if ( dimensions.width < minWidth ) {
		top = offset.top + dimensions.height;
		height = 30;
	} else {
		top = offset.top;
		height = dimensions.height;
	}
	this.$sizeLabel
		.removeClass( 'oo-ui-element-hidden' )
		.addClass( 've-ce-resizableNode-sizeLabel-resizing' )
		.css( {
			top: top,
			left: offset.left,
			width: dimensions.width,
			height: height,
			lineHeight: height + 'px'
		} );
	this.$sizeText.empty();
	if ( this.showSizeLabel ) {
		this.$sizeText.append( $( '<span>' )
			.addClass( 've-ce-resizableNode-sizeText-size' )
			// TODO: i18n?
			.text( Math.round( dimensions.width ) + ' × ' + Math.round( dimensions.height ) )
		);
	}
	if ( this.canShowScaleLabel ) {
		this.$sizeText.append( $( '<span>' )
			.addClass( 've-ce-resizableNode-sizeText-scale' )
			.text( Math.round( 100 * scalable.getCurrentScale() ) + '%' )
		);
	}
	this.$sizeText.toggleClass( 've-ce-resizableNode-sizeText-warning', scalable.isTooSmall() || scalable.isTooLarge() );
};

/**
 * Show specific resize handles
 *
 * @param {string[]} [handles] List of handles to show: 'nw', 'ne', 'sw', 'se'. Show all if undefined.
 */
ve.ce.ResizableNode.prototype.showHandles = function ( handles ) {
	if ( !this.isResizable() ) {
		return;
	}

	var add = [],
		remove = [],
		allDirections = [ 'nw', 'ne', 'sw', 'se' ];

	for ( var i = 0, len = allDirections.length; i < len; i++ ) {
		if ( handles === undefined || handles.indexOf( allDirections[ i ] ) !== -1 ) {
			remove.push( 've-ce-resizableNode-hide-' + allDirections[ i ] );
		} else {
			add.push( 've-ce-resizableNode-hide-' + allDirections[ i ] );
		}
	}

	// The following classes are used here:
	// * ve-ce-resizableNode-hide-nw
	// * ve-ce-resizableNode-hide-ne
	// * ve-ce-resizableNode-hide-sw
	// * ve-ce-resizableNode-hide-se
	this.$resizeHandles
		.addClass( add )
		.removeClass( remove );
};

/**
 * Handle node focus.
 */
ve.ce.ResizableNode.prototype.onResizableFocus = function () {
	// Also check the node is focused as this method is also triggered by rerender.
	if ( !this.isResizable() || !this.isFocused() ) {
		return;
	}
	this.$resizeHandles.appendTo( this.resizableSurface.getSurface().$controls );
	if ( this.$sizeLabel ) {
		this.$sizeLabel.appendTo( this.resizableSurface.getSurface().$controls );
	}

	// Call getScalable to pre-fetch the extended data
	this.model.getScalable();

	this.setResizableHandlesSizeAndPosition();

	this.$resizeHandles
		.find( '.ve-ce-resizableNode-neHandle' )
		.css( { marginRight: -this.$resizable.outerWidth() } );
	this.$resizeHandles
		.find( '.ve-ce-resizableNode-swHandle' )
		.css( { marginBottom: -this.$resizable.outerHeight() } );
	this.$resizeHandles
		.find( '.ve-ce-resizableNode-seHandle' )
		.css( {
			marginRight: -this.$resizable.outerWidth(),
			marginBottom: -this.$resizable.outerHeight()
		} );

	this.$resizeHandles.children()
		.off( '.ve-ce-resizableNode' )
		.on(
			'mousedown.ve-ce-resizableNode',
			this.onResizeHandlesCornerMouseDown.bind( this )
		);

	this.resizableSurface.connect( this, { position: 'setResizableHandlesSizeAndPosition' } );

};

/**
 * Handle node blur.
 */
ve.ce.ResizableNode.prototype.onResizableBlur = function () {
	// Node may have already been torn down, e.g. after delete
	if ( !this.isResizableSetup || !this.root ) {
		return;
	}

	this.$resizeHandles.detach();
	if ( this.$sizeLabel ) {
		this.$sizeLabel.detach();
	}

	this.resizableSurface.disconnect( this, { position: 'setResizableHandlesSizeAndPosition' } );
};

/**
 * Respond to AlignableNodes changing their alignment by hiding useless resize handles.
 *
 * @param {string} align Alignment
 */
ve.ce.ResizableNode.prototype.onResizableAlign = function ( align ) {
	if ( !this.isResizable() ) {
		return;
	}

	this.showHandles( {
		right: [ 'sw' ],
		left: [ 'se' ],
		center: [ 'sw', 'se' ]
		// Defaults to undefined
	}[ align ] );
};

/**
 * Handle setup event.
 */
ve.ce.ResizableNode.prototype.onResizableSetup = function () {
	// Exit if already setup or not attached
	if ( this.isResizableSetup || !this.root ) {
		return;
	}

	this.resizableSurface = this.root.getSurface();
	this.isResizableSetup = true;
};

/**
 * Handle teardown event.
 */
ve.ce.ResizableNode.prototype.onResizableTeardown = function () {
	// Exit if not setup or not attached
	if ( !this.isResizableSetup || !this.root ) {
		return;
	}

	this.onResizableBlur();
	this.resizableSurface = null;
	this.isResizableSetup = false;
};

/**
 * Handle resizing event.
 *
 * @param {Object} dimensions Dimension object containing width & height
 */
ve.ce.ResizableNode.prototype.onResizableResizing = function ( dimensions ) {
	if ( !this.isResizable() ) {
		return;
	}
	// Clear cached resizable offset position as it may have changed
	this.resizableOffset = null;
	this.model.getScalable().setCurrentDimensions( dimensions );
	if ( !this.outline ) {
		this.$resizable.css( this.model.getScalable().getCurrentDimensions() );
		this.setResizableHandlesPosition();
	}
	this.updateSizeLabel();
};

/**
 * Handle attribute change events from the model.
 *
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.ce.ResizableNode.prototype.onResizableAttributeChange = function () {
	if ( !this.isResizable() ) {
		return;
	}
	this.$resizable.css( this.model.getCurrentDimensions() );
};

/**
 * Handle bounding box handle mousedown.
 *
 * @param {jQuery.Event} e Click event
 * @fires resizeStart
 */
ve.ce.ResizableNode.prototype.onResizeHandlesCornerMouseDown = function ( e ) {
	// Hide context menu
	// TODO: Maybe there's a more generic way to handle this sort of thing? For relocation it's
	// handled in ve.ce.Surface
	this.root.getSurface().getSurface().getContext().toggle( false );

	// Set bounding box width and undo the handle margins
	this.$resizeHandles
		.addClass( 've-ce-resizableNode-handles-resizing' )
		.css( {
			width: this.$resizable.outerWidth(),
			height: this.$resizable.outerHeight()
		} );

	this.$resizeHandles.children().css( 'margin', 0 );

	// Values to calculate adjusted bounding box size
	this.resizeInfo = {
		mouseX: e.screenX,
		mouseY: e.screenY,
		top: this.$resizeHandles.position().top,
		left: this.$resizeHandles.position().left,
		height: this.$resizeHandles.height(),
		width: this.$resizeHandles.width(),
		handle: $( e.target ).data( 'handle' )
	};

	// Bind resize events
	this.resizing = true;
	this.root.getSurface().resizing = true;

	this.model.getScalable().setCurrentDimensions( {
		width: this.resizeInfo.width,
		height: this.resizeInfo.height
	} );
	this.updateSizeLabel();
	$( this.getElementDocument() ).on( {
		'mousemove.ve-ce-resizableNode': this.onDocumentMouseMove.bind( this ),
		'mouseup.ve-ce-resizableNode': this.onDocumentMouseUp.bind( this )
	} );
	this.emit( 'resizeStart' );

	e.preventDefault();
};

/**
 * Set the proper size and position for resize handles
 */
ve.ce.ResizableNode.prototype.setResizableHandlesSizeAndPosition = function () {
	if ( !this.isResizable() ) {
		return;
	}

	var width = this.$resizable.outerWidth();
	var height = this.$resizable.outerHeight();

	// Clear cached resizable offset position as it may have changed
	this.resizableOffset = null;

	this.setResizableHandlesPosition();

	this.$resizeHandles
		.css( {
			width: 0,
			height: 0
		} )
		.find( '.ve-ce-resizableNode-neHandle' )
		.css( { marginRight: -width } );
	this.$resizeHandles
		.find( '.ve-ce-resizableNode-swHandle' )
		.css( { marginBottom: -height } );
	this.$resizeHandles
		.find( '.ve-ce-resizableNode-seHandle' )
		.css( {
			marginRight: -width,
			marginBottom: -height
		} );
};

/**
 * Set the proper position for resize handles
 */
ve.ce.ResizableNode.prototype.setResizableHandlesPosition = function () {
	if ( !this.isResizable() ) {
		return;
	}

	var offset = this.getResizableOffset();

	this.$resizeHandles.css( {
		top: offset.top,
		left: offset.left
	} );
};

/**
 * Handle body mousemove.
 *
 * @param {jQuery.Event} e Click event
 * @fires resizing
 */
ve.ce.ResizableNode.prototype.onDocumentMouseMove = function ( e ) {
	var diff = {},
		dimensions = {
			width: 0,
			height: 0,
			top: this.resizeInfo.top,
			left: this.resizeInfo.left
		};

	if ( this.resizing ) {
		// X and Y diff
		switch ( this.resizeInfo.handle ) {
			case 'se':
				diff.x = e.screenX - this.resizeInfo.mouseX;
				diff.y = e.screenY - this.resizeInfo.mouseY;
				break;
			case 'nw':
				diff.x = this.resizeInfo.mouseX - e.screenX;
				diff.y = this.resizeInfo.mouseY - e.screenY;
				break;
			case 'ne':
				diff.x = e.screenX - this.resizeInfo.mouseX;
				diff.y = this.resizeInfo.mouseY - e.screenY;
				break;
			case 'sw':
				diff.x = this.resizeInfo.mouseX - e.screenX;
				diff.y = e.screenY - this.resizeInfo.mouseY;
				break;
		}

		dimensions = this.model.getScalable().getBoundedDimensions( {
			width: this.resizeInfo.width + diff.x,
			height: this.resizeInfo.height + diff.y
		}, e.shiftKey && this.snapToGrid );

		// Fix the position
		switch ( this.resizeInfo.handle ) {
			case 'ne':
				dimensions.top = this.resizeInfo.top +
					( this.resizeInfo.height - dimensions.height );
				break;
			case 'sw':
				dimensions.left = this.resizeInfo.left +
					( this.resizeInfo.width - dimensions.width );
				break;
			case 'nw':
				dimensions.top = this.resizeInfo.top +
					( this.resizeInfo.height - dimensions.height );
				dimensions.left = this.resizeInfo.left +
					( this.resizeInfo.width - dimensions.width );
				break;
		}

		// Update bounding box
		this.$resizeHandles.css( dimensions );
		this.emit( 'resizing', {
			width: dimensions.width,
			height: dimensions.height
		} );
	}
};

/**
 * Handle body mouseup.
 *
 * @fires resizeEnd
 */
ve.ce.ResizableNode.prototype.onDocumentMouseUp = function () {
	var width = this.$resizeHandles.outerWidth(),
		height = this.$resizeHandles.outerHeight();

	this.$resizeHandles.removeClass( 've-ce-resizableNode-handles-resizing' );
	$( this.getElementDocument() ).off( '.ve-ce-resizableNode' );
	this.resizing = false;
	this.root.getSurface().resizing = false;
	this.hideSizeLabel();

	// Apply changes to the model
	var attrChanges = this.getAttributeChanges( width, height );
	if ( !ve.isEmptyObject( attrChanges ) ) {
		this.resizableSurface.getModel().getFragment().changeAttributes( attrChanges );
	}

	// Update the context menu. This usually happens with the redraw, but not if the
	// user doesn't perform a drag
	this.root.getSurface().getSurface().getContext().updateDimensions();

	this.emit( 'resizeEnd' );
};

/**
 * Generate an object of attributes changes from the new width and height.
 *
 * @param {number} width New image width
 * @param {number} height New image height
 * @return {Object} Attribute changes
 */
ve.ce.ResizableNode.prototype.getAttributeChanges = function ( width, height ) {
	var attrChanges = {},
		currentDimensions = this.model.getCurrentDimensions();

	if ( currentDimensions.width !== width ) {
		attrChanges.width = width;
	}
	if ( currentDimensions.height !== height ) {
		attrChanges.height = height;
	}
	return attrChanges;
};
