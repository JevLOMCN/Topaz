/*!
 * VisualEditor ContentEditable Surface class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable surface observer.
 *
 * @class
 *
 * @constructor
 * @param {ve.ce.Surface} surface Surface to observe
 */
ve.ce.SurfaceObserver = function VeCeSurfaceObserver( surface ) {
	// Properties
	this.surface = surface;
	this.domDocument = surface.attachedRoot.getElementDocument();
	this.polling = false;
	this.disabled = false;
	this.timeoutId = null;
	this.pollInterval = 250; // ms
	this.rangeState = null;
};

/* Inheritance */

OO.initClass( ve.ce.SurfaceObserver );

/* Methods */

/**
 * Clear polling data.
 */
ve.ce.SurfaceObserver.prototype.clear = function () {
	this.rangeState = null;
};

/**
 * Detach from the document view
 */
ve.ce.SurfaceObserver.prototype.detach = function () {
	this.surface = null;
	this.domDocument = null;
	this.rangeState = null;
};

/**
 * Start the setTimeout synchronisation loop
 */
ve.ce.SurfaceObserver.prototype.startTimerLoop = function () {
	this.polling = true;
	this.timerLoop( true ); // Will not sync immediately, because timeoutId should be null
};

/**
 * Loop once with `setTimeout`
 *
 * @param {boolean} firstTime Wait before polling
 */
ve.ce.SurfaceObserver.prototype.timerLoop = function ( firstTime ) {
	if ( this.timeoutId ) {
		// In case we're not running from setTimeout
		clearTimeout( this.timeoutId );
		this.timeoutId = null;
	}
	if ( !firstTime ) {
		this.pollOnce();
	}
	// Only reach this point if pollOnce does not throw an exception
	if ( this.pollInterval !== null ) {
		this.timeoutId = this.setTimeout(
			this.timerLoop.bind( this ),
			this.pollInterval
		);
	}
};

/**
 * Stop polling
 */
ve.ce.SurfaceObserver.prototype.stopTimerLoop = function () {
	if ( this.polling === true ) {
		this.polling = false;
		clearTimeout( this.timeoutId );
		this.timeoutId = null;
	}
};

/**
 * Disable the surface observer
 */
ve.ce.SurfaceObserver.prototype.disable = function () {
	this.disabled = true;
};

/**
 * Enable the surface observer
 */
ve.ce.SurfaceObserver.prototype.enable = function () {
	this.disabled = false;
};

/**
 * Poll for changes.
 */
ve.ce.SurfaceObserver.prototype.pollOnce = function () {
	this.pollOnceInternal( true );
};

/**
 * Poll to update SurfaceObserver, but don't signal any changes back to the Surface
 */
ve.ce.SurfaceObserver.prototype.pollOnceNoCallback = function () {
	this.pollOnceInternal( false );
};

/**
 * Poll to update SurfaceObserver, but only check for selection changes
 *
 * Used as an optimisation when you know the content hasn't changed
 */
ve.ce.SurfaceObserver.prototype.pollOnceSelection = function () {
	this.pollOnceInternal( true, true );
};

/**
 * Poll for changes.
 *
 * @private
 * @param {boolean} signalChanges If there changes are observed, call Surface#handleObservedChange
 * @param {boolean} selectionOnly Check for selection changes only
 */
ve.ce.SurfaceObserver.prototype.pollOnceInternal = function ( signalChanges, selectionOnly ) {
	if ( !this.domDocument || this.disabled ) {
		return;
	}

	var oldState = this.rangeState;
	var newState = new ve.ce.RangeState(
		oldState,
		this.surface.attachedRoot,
		selectionOnly
	);
	this.rangeState = newState;

	if ( signalChanges && (
		newState.contentChanged ||
		newState.branchNodeChanged ||
		newState.selectionChanged
	) ) {
		this.surface.handleObservedChanges( oldState, newState );
	}
};

/**
 * Wrapper for setTimeout, for ease of debugging
 *
 * @param {Function} callback
 * @param {number} timeout Timeout ms
 * @return {number} Timeout ID
 */
ve.ce.SurfaceObserver.prototype.setTimeout = function ( callback, timeout ) {
	return setTimeout( callback, timeout );
};

/**
 * Get the range last observed.
 *
 * Used when you have just polled, but don't want to wait for a 'rangeChange' event.
 *
 * @return {ve.Range|null}
 */
ve.ce.SurfaceObserver.prototype.getRange = function () {
	if ( !this.rangeState ) {
		return null;
	}
	return this.rangeState.veRange;
};
