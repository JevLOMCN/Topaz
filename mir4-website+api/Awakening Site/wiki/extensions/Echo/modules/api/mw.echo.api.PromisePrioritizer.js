( function () {
	/**
	 * Promise prioritizer for API actions. The prioritizer takes
	 * a promise at a time, always prioritizing the latest promise and
	 * aborting and ignoring the others.
	 *
	 * This allows us to send multiple promises in quick successions but
	 * trust that we get back only the latest successful request.
	 *
	 * @class
	 *
	 * @constructor
	 */
	mw.echo.api.PromisePrioritizer = function MwEchoApiPromisePrioritizer() {
		this.deferred = $.Deferred();
		this.promise = null;
	};

	/* Initialization */

	OO.initClass( mw.echo.api.PromisePrioritizer );

	/**
	 * Prioritize a promise
	 *
	 * @external Promise
	 * @param {jQuery.Promise|Promise} promise Promise
	 * @return {jQuery.Promise} The main deferred object that resolves
	 *  or rejects when the latest promise is resolved or rejected.
	 */
	mw.echo.api.PromisePrioritizer.prototype.prioritize = function ( promise ) {
		var previousPromise = this.promise;

		promise
			.then(
				this.setSuccess.bind( this, promise ),
				this.setFailure.bind( this, promise )
			);
		this.promise = promise;

		if ( previousPromise && previousPromise.abort ) {
			previousPromise.abort();
		}

		return this.deferred.promise();
	};

	/**
	 * Set success for the promise. Resolve the main deferred object only
	 * if we are dealing with the currently prioritized promise.
	 *
	 * @param {jQuery.Promise} promise The promise that resolved successfully.
	 *  The main deferred object is resolved with the result of the
	 *  latest prioritized promise.
	 */
	mw.echo.api.PromisePrioritizer.prototype.setSuccess = function ( promise ) {
		var prioritizer = this;

		if ( this.promise === promise ) {
			this.promise.done( function () {
				prioritizer.deferred.resolve.apply( prioritizer.deferred, arguments );

				prioritizer.promise = null;
				prioritizer.deferred = $.Deferred();
			} );
		}
	};

	/**
	 * Set failure for the promise. Reject the main deferred object only
	 * if we are dealing with the currently prioritized promise.
	 *
	 * @param {jQuery.Promise} promise The promise that failed.
	 *  The main deferred object is rejected with the result of the
	 *  latest prioritized promise
	 */
	mw.echo.api.PromisePrioritizer.prototype.setFailure = function ( promise ) {
		var prioritizer = this;

		if ( this.promise === promise ) {
			this.promise.fail( function () {
				prioritizer.deferred.reject.apply( prioritizer.deferred, arguments );

				prioritizer.promise = null;
				prioritizer.deferred = $.Deferred();
			} );
		}
	};
}() );
