/*!
 * VisualEditor MediaWiki Initialization ApiResponseCache class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki API batch queue.
 *
 * Used to queue up lists of items centrally to get information about in batches
 *  of requests.
 *
 * @class
 * @extends OO.EventEmitter
 * @constructor
 * @param {mw.Api} [api] API object to use. Defaults to new mw.Api()
 */
ve.init.mw.ApiResponseCache = function VeInitMwApiResponseCache( api ) {
	// Mixin constructor
	OO.EventEmitter.call( this );

	this.api = api || new mw.Api();

	// Keys are titles, values are deferreds
	this.deferreds = {};

	// Keys are page names, values are link data objects
	// This is kept for synchronous retrieval of cached values via #getCached
	this.cacheValues = {};

	// Array of page titles queued to be looked up
	this.queue = [];

	this.schedule = ve.debounce( this.processQueue.bind( this ), 0 );
};

/* Inheritance */

OO.mixinClass( ve.init.mw.ApiResponseCache, OO.EventEmitter );

/* Static methods */

/**
 * Process each page in the response of an API request
 *
 * @abstract
 * @static
 * @param {Object} page
 * @return {Object|undefined} Any relevant info that we want to cache and return.
 */
ve.init.mw.ApiResponseCache.static.processPage = null;

/**
 * @param {string} title
 * @return {string}
 */
ve.init.mw.ApiResponseCache.static.normalizeTitle = function ( title ) {
	var titleObj = mw.Title.newFromText( title );
	if ( !titleObj ) {
		return title;
	}
	return titleObj.getPrefixedText();
};

/* Methods */

/**
 * Look up data about a title. If the data about this title is already in the cache, this
 * returns an already-resolved promise. Otherwise, it returns a pending promise and schedules
 * an request to retrieve the data.
 *
 * @param {string} title
 * @return {jQuery.Promise} Promise that will be resolved with the data once it's available
 */
ve.init.mw.ApiResponseCache.prototype.get = function ( title ) {
	if ( typeof title !== 'string' ) {
		// Don't bother letting things like undefined or null make it all the way through,
		// just reject them here. Otherwise they'll cause problems or exceptions at random
		// other points in this file.
		return ve.createDeferred().reject().promise();
	}
	title = this.constructor.static.normalizeTitle( title );
	if ( !Object.prototype.hasOwnProperty.call( this.deferreds, title ) ) {
		this.deferreds[ title ] = ve.createDeferred();
		this.queue.push( title );
		this.schedule();
	}
	return this.deferreds[ title ].promise();
};

/**
 * Look up data about a page in the cache. If the data about this page is already in the cache,
 * this returns that data. Otherwise, it returns undefined.
 *
 * @param {string} name Normalized page title
 * @return {Object|undefined} Cache data for this name.
 */
ve.init.mw.ApiResponseCache.prototype.getCached = function ( name ) {
	if ( Object.prototype.hasOwnProperty.call( this.cacheValues, name ) ) {
		return this.cacheValues[ name ];
	}
};

/**
 * Fired when a new entry is added to the cache.
 *
 * @event add
 * @param {Object} entries Cache entries that were added. Object mapping names to data objects.
 */

/**
 * Add entries to the cache. Does not overwrite already-set entries.
 *
 * @param {Object} entries Object keyed by page title, with the values being data objects
 * @fires add
 */
ve.init.mw.ApiResponseCache.prototype.set = function ( entries ) {
	for ( var name in entries ) {
		if ( !Object.prototype.hasOwnProperty.call( this.deferreds, name ) ) {
			this.deferreds[ name ] = ve.createDeferred();
		}
		if ( this.deferreds[ name ].state() === 'pending' ) {
			this.deferreds[ name ].resolve( entries[ name ] );
			this.cacheValues[ name ] = entries[ name ];
		}
	}
	this.emit( 'add', Object.keys( entries ) );
};

/**
 * Get an API request promise to deal with a list of titles
 *
 * @abstract
 * @param subqueue
 * @return {jQuery.Promise}
 */
ve.init.mw.ApiResponseCache.prototype.getRequestPromise = null;

/**
 * Perform any scheduled API requests.
 *
 * @private
 * @fires add
 */
ve.init.mw.ApiResponseCache.prototype.processQueue = function () {
	var cache = this;

	function rejectSubqueue( rejectQueue ) {
		for ( var i = 0, len = rejectQueue.length; i < len; i++ ) {
			cache.deferreds[ rejectQueue[ i ] ].reject();
		}
	}

	function processResult( data ) {
		var mappedTitles = [],
			pages = ( data.query && data.query.pages ) || data.pages,
			processed = {};

		[ 'redirects', 'normalized', 'converted' ].forEach( function ( map ) {
			mappedTitles = mappedTitles.concat( ( data.query && data.query[ map ] ) || [] );
		} );

		if ( pages ) {
			var page, processedPage;
			for ( var pageid in pages ) {
				page = pages[ pageid ];
				processedPage = cache.constructor.static.processPage( page );
				if ( processedPage !== undefined ) {
					processed[ page.title ] = processedPage;
				}
			}
			for ( var i = 0; i < mappedTitles.length; i++ ) {
				// Locate the title in mapped titles, if any.
				if ( mappedTitles[ i ].to === page.title ) {
					var from = mappedTitles[ i ].fromencoded === '' ?
						decodeURIComponent( mappedTitles[ i ].from ) :
						mappedTitles[ i ].from;
					processed[ from ] = processedPage;
					break;
				}
			}
			cache.set( processed );
		}
	}

	var queue = this.queue;
	this.queue = [];
	while ( queue.length ) {
		var subqueue = queue.splice( 0, 50 ).map( this.constructor.static.normalizeTitle );
		this.getRequestPromise( subqueue )
			.then( processResult )

			// Reject everything in subqueue; this will only reject the ones
			// that weren't already resolved above, because .reject() on an
			// already resolved Deferred is a no-op.
			.then( rejectSubqueue.bind( null, subqueue ) );
	}
};
