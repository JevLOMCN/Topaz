/*!
 * VisualEditor Scheduler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 *
 * @constructor
 */
ve.Scheduler = function VeScheduler() {
	// TODO: If we decide to start tracking setTimeout calls within actions, we'll
	// need to keep state here.
};

/* Inheritance */

OO.initClass( ve.Scheduler );

/* Static Properties */

ve.Scheduler.static.maxDelay = 1000;

/* Methods */

/**
 * Perform an action and await a callback when its side-effects are complete
 *
 * The ultimate definition of "side-effects are complete" is "when the chain of async
 * actions / setTimeout calls spawned by the action finish". This is intended to be a way
 * to wrap non-promise code in promises and have it mostly work.
 *
 * As currently implemented, we use completionTest as our sole signal. This is not
 * guaranteed to remain true. Don't write code that assumes completionTest will be
 * called, or which tests for a completely unrelated condition.
 *
 * The signature of this function is designed to let you leave signals about your intent.
 * You pass the action with side-effects in, and explain the conditions that must be met
 * for further actions to be taken.
 *
 * @param {Function} immediateAction Action to take whose status we want to track
 * @param {Function} completionTest Tests whether action is complete; ideally very cheap;
 *        there's no guarantee that we will ever call this, if we can sense completion in
 *        some other way
 * @param {number} [delayHint] Optional hint about how long to wait between tests
 * @return {jQuery.Promise} Promise that resolves when the completionTest returns true.
 *         Note that this _could_ already be resolved when it's returned, so there's no
 *         guarantee that your `done` call on it will be delayed.
 */
ve.Scheduler.prototype.schedule = function ( immediateAction, completionTest, delayHint ) {
	var deferred = ve.createDeferred(),
		startTime = this.now(),
		testThenAct = function () {
			var complete;
			try {
				complete = completionTest();
			} catch ( e ) {
				deferred.reject( e );
				return;
			}
			if ( complete ) {
				deferred.resolve();
				return;
			}
			if ( this.now() - startTime > this.constructor.static.maxDelay ) {
				deferred.reject();
				return;
			}
			this.postpone( testThenAct, delayHint );
		}.bind( this );

	// In the future, we may want to expand this to track whether other async calls
	// were made within the action.
	immediateAction();

	// Spin up the test cycle
	testThenAct();

	return deferred.promise();
};

/**
 * Make a postponed call.
 *
 * This is a separate function because that makes it easier to replace when testing
 *
 * @param {Function} callback The function to call
 * @param {number} delay Delay before running callback
 * @return {number} Unique postponed timeout id
 */
ve.Scheduler.prototype.postpone = function ( callback, delay ) {
	return setTimeout( callback, delay );
};

/**
 * Obtain the current timestamp
 *
 * This is a separate function because that makes it easier to replace when testing
 *
 * @return {number} Current timestamp in milliseconds
 */
ve.Scheduler.prototype.now = function () {
	return Date.now();
};

/* Initialization */

ve.scheduler = new ve.Scheduler();
