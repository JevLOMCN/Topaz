/*!
 * VisualEditor EventSequencer class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * EventSequencer class with on-event and after-event listeners.
 *
 * After-event listeners are fired as soon as possible after the
 * corresponding native event. They are similar to the setTimeout(f, 0)
 * idiom, except that they are guaranteed to execute before any subsequent
 * on-event listener. Therefore, events are executed in the 'right order'.
 *
 * This matters when many events are added to the task queue in one go.
 * For instance, browsers often queue 'keydown' and 'keypress' in immediate
 * sequence, so a setTimeout(f, 0) defined in the keydown listener will run
 * **after** the keypress listener (i.e. in the 'wrong' order). EventSequencer
 * ensures that this does not happen.
 *
 * All these listeners receive the jQuery event as an argument. If an on-event
 * listener needs to pass information to a corresponding after-event listener,
 * it can do so by adding properties into the jQuery event itself.
 *
 * There are also 'onLoop' and 'afterLoop' listeners, which only fire once per
 * Javascript event loop iteration, respectively before and after all the
 * other listeners fire.
 *
 * There is special handling for sequences (keydown,keypress), where the
 * keypress handler is called before the native keydown action happens. In
 * this case, after-keydown handlers fire after on-keypress handlers.
 *
 * For further event loop / task queue information, see:
 * http://www.whatwg.org/specs/web-apps/current-work/multipage/webappapis.html#event-loops
 *
 * @class ve.EventSequencer
 */

/**
 *
 * To fire after-event listeners promptly, the EventSequencer may need to
 * listen to some events for which it has no registered on-event or
 * after-event listeners. For instance, to ensure an after-keydown listener
 * is be fired before the native keyup action, you must include both
 * 'keydown' and 'keyup' in the eventNames Array.
 *
 * @constructor
 * @param {string[]} eventNames List of event Names to listen to
 */
ve.EventSequencer = function VeEventSequencer( eventNames ) {
	var eventSequencer = this;
	this.$node = null;
	this.eventNames = eventNames;
	this.eventHandlers = {};

	/**
	 * Generate an event handler for a specific event
	 *
	 * @private
	 * @param {string} name The event's name
	 * @return {Function} An event handler
	 */
	function makeEventHandler( name ) {
		return function ( ev ) {
			return eventSequencer.onEvent( name, ev );
		};
	}

	/**
	 * @property {Object[]} Pending calls
	 *  - id {number} Id for setTimeout
	 *  - func {Function} Post-event listener
	 *  - ev {jQuery.Event} Browser event
	 *  - eventName {string} Name, such as keydown
	 */
	this.pendingCalls = [];

	/**
	 * @property {Object.<string,Function[]>}
	 */
	this.onListenersForEvent = {};

	/**
	 * @property {Object.<string,Function[]>}
	 */
	this.afterListenersForEvent = {};

	/**
	 * @property {Object.<string,Function[]>}
	 */
	this.afterOneListenersForEvent = {};

	for ( var i = 0, len = eventNames.length; i < len; i++ ) {
		var eventName = eventNames[ i ];
		this.onListenersForEvent[ eventName ] = [];
		this.afterListenersForEvent[ eventName ] = [];
		this.afterOneListenersForEvent[ eventName ] = [];
		this.eventHandlers[ eventName ] = makeEventHandler( eventName );
	}

	/**
	 * @property {Function[]}
	 */
	this.onLoopListeners = [];

	/**
	 * @property {Function[]}
	 */
	this.afterLoopListeners = [];

	/**
	 * @property {Function[]}
	 */
	this.afterLoopOneListeners = [];

	/**
	 * @property {boolean}
	 */
	this.doneOnLoop = false;

	/**
	 * @property {number}
	 */
	this.afterLoopTimeoutId = null;
};

/**
 * Attach to a node, to listen to its jQuery events
 *
 * @param {jQuery} $node The node to attach to
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.attach = function ( $node ) {
	this.detach();
	this.$node = $node.on( this.eventHandlers );
	return this;
};

/**
 * Detach from a node (if attached), to stop listen to its jQuery events
 *
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.detach = function () {
	if ( this.$node === null ) {
		return;
	}
	this.runPendingCalls();
	this.$node.off( this.eventHandlers );
	this.$node = null;
	return this;
};

/**
 * Add listeners to be fired at the start of the Javascript event loop iteration
 *
 * @param {Function|Function[]} listeners Listener(s) that take no arguments
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.onLoop = function ( listeners ) {
	if ( !Array.isArray( listeners ) ) {
		listeners = [ listeners ];
	}
	ve.batchPush( this.onLoopListeners, listeners );
	return this;
};

/**
 * Add listeners to be fired just before the browser native action
 *
 * @param {Object.<string,Function>} listeners Function for each event
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.on = function ( listeners ) {
	for ( var eventName in listeners ) {
		this.onListenersForEvent[ eventName ].push( listeners[ eventName ] );
	}
	return this;
};

/**
 * Add listeners to be fired as soon as possible after the native action
 *
 * @param {Object.<string,Function>} listeners Function for each event
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.after = function ( listeners ) {
	for ( var eventName in listeners ) {
		this.afterListenersForEvent[ eventName ].push( listeners[ eventName ] );
	}
	return this;
};

/**
 * Add listeners to be fired once, as soon as possible after the native action
 *
 * @param {Object.<string,Function[]>} listeners Function for each event
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.afterOne = function ( listeners ) {
	for ( var eventName in listeners ) {
		this.afterOneListenersForEvent[ eventName ].push( listeners[ eventName ] );
	}
	return this;
};

/**
 * Add listeners to be fired at the end of the Javascript event loop iteration
 *
 * @param {Function|Function[]} listeners Listener(s) that take no arguments
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.afterLoop = function ( listeners ) {
	if ( !Array.isArray( listeners ) ) {
		listeners = [ listeners ];
	}
	ve.batchPush( this.afterLoopListeners, listeners );
	return this;
};

/**
 * Add listeners to be fired once, at the end of the Javascript event loop iteration
 *
 * @param {Function|Function[]} listeners Listener(s) that take no arguments
 * @return {ve.EventSequencer}
 * @chainable
 */
ve.EventSequencer.prototype.afterLoopOne = function ( listeners ) {
	if ( !Array.isArray( listeners ) ) {
		listeners = [ listeners ];
	}
	ve.batchPush( this.afterLoopOneListeners, listeners );
	return this;
};

/**
 * Generic listener method which does the sequencing
 *
 * @private
 * @param {string} eventName Javascript name of the event, e.g. 'keydown'
 * @param {jQuery.Event} ev The browser event
 */
ve.EventSequencer.prototype.onEvent = function ( eventName, ev ) {
	this.runPendingCalls( eventName );
	if ( !this.doneOnLoop ) {
		this.doneOnLoop = true;
		this.doOnLoop();
	}

	// Listener list: take snapshot (for immutability if a listener adds another listener)
	var onListeners = ( this.onListenersForEvent[ eventName ] || [] ).slice();

	for ( var i = 0, len = onListeners.length; i < len; i++ ) {
		var onListener = onListeners[ i ];
		this.callListener( 'on', eventName, i, onListener, ev );
	}
	// Create a cancellable pending call. We need one even if there are no after*Listeners, to
	// call resetAfterLoopTimeout which resets doneOneLoop to false.
	// - Create the pendingCall object first
	// - then create the setTimeout invocation to modify pendingCall.id
	// - then set pendingCall.id to the setTimeout id, so the call can cancel itself
	var pendingCall = { id: null, ev: ev, eventName: eventName };
	var eventSequencer = this;
	var id = this.postpone( function () {
		if ( pendingCall.id === null ) {
			// clearTimeout seems not always to work immediately
			return;
		}
		eventSequencer.resetAfterLoopTimeout();
		pendingCall.id = null;
		eventSequencer.afterEvent( eventName, ev );
	} );
	pendingCall.id = id;
	this.pendingCalls.push( pendingCall );
};

/**
 * Generic after listener method which gets queued
 *
 * @private
 * @param {string} eventName Javascript name of the event, e.g. 'keydown'
 * @param {jQuery.Event} ev The browser event
 */
ve.EventSequencer.prototype.afterEvent = function ( eventName, ev ) {
	// Listener list: take snapshot (for immutability if a listener adds another listener)
	var afterListeners = ( this.afterListenersForEvent[ eventName ] || [] ).slice();
	// One-time listener list: take snapshot (for immutability) and blank the list
	var afterOneListeners = ( this.afterOneListenersForEvent[ eventName ] || [] ).splice( 0 );

	var i, len;
	for ( i = 0, len = afterListeners.length; i < len; i++ ) {
		this.callListener( 'after', eventName, i, afterListeners[ i ], ev );
	}

	for ( i = 0, len = afterOneListeners.length; i < len; i++ ) {
		this.callListener( 'afterOne', eventName, i, afterOneListeners[ i ], ev );
	}
};

/**
 * Call each onLoopListener once
 *
 * @private
 */
ve.EventSequencer.prototype.doOnLoop = function () {
	// Length cache 'len' is required, as the functions called may add another listener
	for ( var i = 0, len = this.onLoopListeners.length; i < len; i++ ) {
		this.callListener( 'onLoop', null, i, this.onLoopListeners[ i ], null );
	}
};

/**
 * Call each afterLoopListener once, unless the setTimeout is already cancelled
 *
 * @private
 * @param {number} myTimeoutId The calling setTimeout id
 */
ve.EventSequencer.prototype.doAfterLoop = function ( myTimeoutId ) {
	if ( this.afterLoopTimeoutId !== myTimeoutId ) {
		// Cancelled; do nothing
		return;
	}
	this.afterLoopTimeoutId = null;

	// Loop listener list: take snapshot (for immutability if a listener adds another listener)
	var afterLoopListeners = this.afterLoopListeners.slice();
	// One-time loop listener list: take snapshot (for immutability) and blank the list
	var afterLoopOneListeners = this.afterLoopOneListeners.splice( 0 );

	var i, len;
	for ( i = 0, len = afterLoopListeners.length; i < len; i++ ) {
		this.callListener( 'afterLoop', null, i, this.afterLoopListeners[ i ], null );
	}

	for ( i = 0, len = afterLoopOneListeners.length; i < len; i++ ) {
		this.callListener( 'afterLoopOne', null, i, afterLoopOneListeners[ i ], null );
	}
	this.doneOnLoop = false;
};

/**
 * Push any pending doAfterLoop to end of task queue (cancel, then re-set)
 *
 * @private
 */
ve.EventSequencer.prototype.resetAfterLoopTimeout = function () {
	if ( this.afterLoopTimeoutId !== null ) {
		this.cancelPostponed( this.afterLoopTimeoutId );
	}
	var eventSequencer = this;
	var timeoutId = this.postpone( function () {
		eventSequencer.doAfterLoop( timeoutId );
	} );
	this.afterLoopTimeoutId = timeoutId;
};

/**
 * Run any pending listeners, and clear the pending queue
 *
 * @private
 * @param {string} eventName The name of the event currently being triggered
 */
ve.EventSequencer.prototype.runPendingCalls = function ( eventName ) {
	var afterKeyDownCalls = [];

	for ( var i = 0; i < this.pendingCalls.length; i++ ) {
		// Length cache not possible, as a pending call appends another pending call.
		// It's important that this list remains mutable, in the case that this
		// function indirectly recurses.
		var pendingCall = this.pendingCalls[ i ];
		if ( pendingCall.id === null ) {
			// The call has already run
			continue;
		}
		if ( eventName === 'keypress' && pendingCall.eventName === 'keydown' ) {
			// Delay afterKeyDown till after keypress
			afterKeyDownCalls.push( pendingCall );
			continue;
		}

		this.cancelPostponed( pendingCall.id );
		pendingCall.id = null;
		// Force to run now. It's important that we set id to null before running,
		// so that there's no chance a recursive call will call the listener again.
		this.afterEvent( pendingCall.eventName, pendingCall.ev );
	}
	// This is safe: we only ever appended to the list, so it's definitely exhausted now.
	this.pendingCalls.length = 0;
	this.pendingCalls.push.apply( this.pendingCalls, afterKeyDownCalls );
};

/**
 * Make a postponed call.
 *
 * This is a separate function because that makes it easier to replace when testing
 *
 * @param {Function} callback The function to call
 * @return {number} Unique postponed timeout id
 */
ve.EventSequencer.prototype.postpone = function ( callback ) {
	return setTimeout( callback );
};

/**
 * Cancel a postponed call.
 *
 * This is a separate function because that makes it easier to replace when testing
 *
 * @param {number} timeoutId Unique postponed timeout id
 */
ve.EventSequencer.prototype.cancelPostponed = function ( timeoutId ) {
	clearTimeout( timeoutId );
};

/**
 * Single method to perform all listener calls, for ease of debugging
 *
 * @param {string} timing on|after|afterOne|onLoop|afterLoop|afterLoopOne
 * @param {string} eventName Name of the event
 * @param {number} i The sequence of the listener
 * @param {Function} listener The listener to call
 * @param {jQuery.Event} ev The browser event
 */
ve.EventSequencer.prototype.callListener = function ( timing, eventName, i, listener, ev ) {
	listener( ev );
};
