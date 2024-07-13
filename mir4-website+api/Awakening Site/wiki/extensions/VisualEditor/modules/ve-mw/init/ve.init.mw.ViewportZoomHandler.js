/*!
 * VisualEditor MediaWiki Initialization ViewportZoomHandler class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Prevent iOS browsers from wrongly zooming in the page when the surface is focussed. (T216446)
 *
 * When the user places a cursor for text input anywhere on the page, iOS browsers zoom in the page
 * to ensure the text size is legible and the cursor can be comfortably placed in the right place
 * with a finger.
 *
 * There's a browser bug that, on some devices (e.g. iPhone XS, but not iPhone SE), causes this
 * zoom to occur even though our text is already using the required minimum font size (16px).
 * Additionally, the zoom occurs when placing the cursor in image captions, which intentionally
 * use a smaller font size.
 *
 * In both cases the zoom is more problematic than helpful, because it causes parts of the toolbar
 * to disappear outside the viewport.
 *
 * To prevent it, temporarily add a tag like `<meta name="viewport" content="maximum-scale=1.0">`
 * to the page when the user is about to focus the editing surface. However, on iOS Chrome, this
 * also prevents intentional pinch-zoom. To avoid this, immediately remove the tag again after
 * focussing, or if it looks like the user is trying to zoom (used multi-touch or caused a scroll).
 *
 * @class
 * @constructor
 */
ve.init.mw.ViewportZoomHandler = function VeInitMwViewportZoomHandler() {
	// eslint-disable-next-line no-jquery/no-global-selector
	this.$viewportMeta = $( 'meta[name="viewport"]' );
	if ( !this.$viewportMeta.length ) {
		this.$viewportMeta = $( '<meta>' ).attr( 'name', 'viewport' ).appendTo( document.head );
	}
	this.origViewportMetaContent = this.$viewportMeta.attr( 'content' );

	this.onTouchStartHandler = this.onTouchStart.bind( this );
	this.onTouchMoveHandler = this.onTouchMove.bind( this );
	this.onTouchEndHandler = this.onTouchEnd.bind( this );
};

/* Methods */

/**
 * Change the `<meta name="viewport">` tag to prevent automatic zooming.
 */
ve.init.mw.ViewportZoomHandler.prototype.preventZoom = function () {
	this.$viewportMeta.attr( 'content', function ( i, val ) {
		// Remove existing maximum-scale, if any, and add 'maximum-scale=1.0'. Don't change other values.
		if ( val ) {
			val = val.replace( /maximum-scale=[\d.]+(,\s*|$)/, '' );
			val += ', ';
		} else {
			val = '';
		}
		return val + 'maximum-scale=1.0';
	} );
};

/**
 * Change the `<meta name="viewport">` tag to allow automatic zooming once again.
 */
ve.init.mw.ViewportZoomHandler.prototype.allowZoom = function () {
	this.$viewportMeta.attr( 'content', this.origViewportMetaContent );
};

/**
 * Start listening to events and preventing zooming.
 *
 * @param {ve.ui.Surface} surface
 */
ve.init.mw.ViewportZoomHandler.prototype.attach = function ( surface ) {
	this.surface = surface;

	this.surface.getView().$element.on( {
		touchstart: this.onTouchStartHandler,
		touchmove: this.onTouchMoveHandler,
		touchend: this.onTouchEndHandler
	} );
	this.surface.getModel().connect( this, {
		focus: 'onFocus'
	} );
};

/**
 * Stop listening to events.
 */
ve.init.mw.ViewportZoomHandler.prototype.detach = function () {
	this.surface.getView().$element.off( {
		touchstart: this.onTouchStartHandler,
		touchmove: this.onTouchMoveHandler,
		touchend: this.onTouchEndHandler
	} );
	this.surface.getModel().disconnect( this, {
		focus: 'onFocus'
	} );

	this.surface = null;
};

/**
 * Handle touch start events.
 *
 * @param {jQuery.Event} e Touch start event
 */
ve.init.mw.ViewportZoomHandler.prototype.onTouchStart = function ( e ) {
	if ( e.touches.length === 1 ) {
		this.wasMoved = false;
	}

	this.allowZoom();
};

/**
 * Handle touch move events.
 *
 * @param {jQuery.Event} e Touch move event
 */
ve.init.mw.ViewportZoomHandler.prototype.onTouchMove = function () {
	this.wasMoved = true;
};

/**
 * Handle touch end events.
 *
 * @param {jQuery.Event} e Touch end event
 */
ve.init.mw.ViewportZoomHandler.prototype.onTouchEnd = function ( e ) {
	if ( e.touches.length === 0 && !this.wasMoved ) {
		// There was a single touch point, that hasn't moved, and now it's gone.
		// Looks like we're going to focus the surface, so prevent automatic zoom.
		this.preventZoom();
	} else {
		// Otherwise, allow zoom, so that the user can pinch-zoom
		this.allowZoom();
	}
};

/**
 * Handle surface model focus events.
 */
ve.init.mw.ViewportZoomHandler.prototype.onFocus = function () {
	this.allowZoom();
};
