( function () {
	/**
	 * Network handler for echo notifications. Manages multiple APIHandlers
	 * according to their sources.
	 *
	 * @class
	 *
	 * @constructor
	 * @param {Object} config Configuration options
	 * @cfg {number} limit Number of notifications to fetch
	 */
	mw.echo.api.NetworkHandler = function MwEchoApiNetworkHandler( config ) {
		config = config || {};

		this.handlers = {};

		// Add initial local handler
		this.setApiHandler( 'local', new mw.echo.api.LocalAPIHandler( { limit: config.limit } ) );
	};

	/* Setup */

	OO.initClass( mw.echo.api.NetworkHandler );

	/* Static methods */
	/**
	 * Wait for all promises to finish either with a resolve or reject and
	 * return them to the caller once they do.
	 *
	 * @param {jQuery.Promise[]} promiseArray An array of promises
	 * @return {jQuery.Promise} A promise that resolves when all the promises
	 *  finished with some resolution or rejection.
	 */
	mw.echo.api.NetworkHandler.static.waitForAllPromises = function ( promiseArray ) {
		var promises = promiseArray.slice( 0 ),
			counter = 0,
			deferred = $.Deferred(),
			countPromises = function () {
				counter++;
				if ( counter === promises.length ) {
					deferred.resolve( promises );
				}
			};

		if ( !promiseArray.length ) {
			deferred.resolve();
		}

		for ( var i = 0; i < promises.length; i++ ) {
			promises[ i ].always( countPromises );
		}

		return deferred.promise();
	};

	/* Methods */

	/**
	 * Get the API handler that matches the symbolic name
	 *
	 * @param {string} name Symbolic name of the API handler
	 * @return {mw.echo.api.APIHandler|undefined} API handler, if exists
	 */
	mw.echo.api.NetworkHandler.prototype.getApiHandler = function ( name ) {
		return this.handlers[ name ];
	};

	/**
	 * Set an API handler by passing in an instance of an mw.echo.api.APIHandler subclass directly.
	 *
	 * @param {string} name Symbolic name
	 * @param {mw.echo.api.APIHandler} handler Handler object
	 * @throws {Error} If handler already exists
	 */
	mw.echo.api.NetworkHandler.prototype.setApiHandler = function ( name, handler ) {
		this.handlers[ name ] = handler;
	};
}() );
