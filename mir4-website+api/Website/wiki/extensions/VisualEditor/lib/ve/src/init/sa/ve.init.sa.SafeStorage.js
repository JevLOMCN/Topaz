( function () {
	// Copied from mediawiki.requestIdleCallback
	var requestIdleCallbackInternal = function ( callback ) {
		setTimeout( function () {
			var start = ve.now();
			callback( {
				didTimeout: false,
				timeRemaining: function () {
					// Hard code a target maximum busy time of 50 milliseconds
					return Math.max( 0, 50 - ( ve.now() - start ) );
				}
			} );
		}, 1 );
	};

	// eslint-disable-next-line compat/compat
	var requestIdleCallback = window.requestIdleCallback ?
		// Bind because it throws TypeError if context is not window
		// eslint-disable-next-line compat/compat
		window.requestIdleCallback.bind( window ) :
		requestIdleCallbackInternal;

	var EXPIRY_PREFIX = '_EXPIRY_';
	/**
	 * Implementation of ve.init.SafeStorage
	 *
	 * Duplicate of mediawiki.storage.
	 *
	 * @class ve.init.sa.SafeStorage
	 * @extends ve.init.SafeStorage
	 *
	 * @constructor
	 * @param {Storage|undefined} store The Storage instance to wrap around
	 */
	ve.init.sa.SafeStorage = function ( store ) {
		this.store = store;

		// Purge expired items once per page session
		var storage = this;
		setTimeout( function () {
			storage.clearExpired();
		}, 2000 );
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.get = function ( key ) {
		if ( this.isExpired( key ) ) {
			return null;
		}
		try {
			return this.store.getItem( key );
		} catch ( e ) {}
		return false;
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.set = function ( key, value, expiry ) {
		if ( key.slice( 0, EXPIRY_PREFIX.length ) === EXPIRY_PREFIX ) {
			throw new Error( 'Key can\'t have a prefix of ' + EXPIRY_PREFIX );
		}
		try {
			this.store.setItem( key, value );
			this.setExpires( key, expiry );
			return true;
		} catch ( e ) {}
		return false;
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.remove = function ( key ) {
		try {
			this.store.removeItem( key );
			this.setExpires( key );
			return true;
		} catch ( e ) {}
		return false;
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.getObject = function ( key ) {
		var json = this.get( key );

		if ( json === false ) {
			return false;
		}

		try {
			return JSON.parse( json );
		} catch ( e ) {}

		return null;
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.setObject = function ( key, value, expiry ) {
		var json;
		try {
			json = JSON.stringify( value );
			return this.set( key, json, expiry );
		} catch ( e ) {}
		return false;
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.setExpires = function ( key, expiry ) {
		if ( expiry ) {
			try {
				this.store.setItem(
					EXPIRY_PREFIX + key,
					Math.floor( Date.now() / 1000 ) + expiry
				);
			} catch ( e ) {}
		} else {
			try {
				this.store.removeItem( EXPIRY_PREFIX + key );
			} catch ( e ) {}
		}
	};

	// Minimum amount of time (in milliseconds) for an iteration involving localStorage access.
	var MIN_WORK_TIME = 3;

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.clearExpired = function () {
		var storage = this;
		return this.getExpiryKeys().then( function ( keys ) {
			return $.Deferred( function ( d ) {
				requestIdleCallback( function iterate( deadline ) {
					while ( keys[ 0 ] !== undefined && deadline.timeRemaining() > MIN_WORK_TIME ) {
						var key = keys.shift();
						if ( storage.isExpired( key ) ) {
							storage.remove( key );
						}
					}
					if ( keys[ 0 ] !== undefined ) {
						// Ran out of time with keys still to remove, continue later
						requestIdleCallback( iterate );
					} else {
						return d.resolve();
					}
				} );
			} );
		} );
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.getExpiryKeys = function () {
		var store = this.store;
		return $.Deferred( function ( d ) {
			requestIdleCallback( function ( deadline ) {
				var prefixLength = EXPIRY_PREFIX.length;
				var keys = [];
				var length = 0;
				try {
					length = store.length;
				} catch ( e ) {}

				// Optimization: If time runs out, degrade to checking fewer keys.
				// We will get another chance during a future page view. Iterate forward
				// so that older keys are checked first and increase likelihood of recovering
				// from key exhaustion.
				//
				// We don't expect to have more keys than we can handle in 50ms long-task window.
				// But, we might still run out of time when other tasks run before this,
				// or when the device receives UI events (especially on low-end devices).
				for ( var i = 0; ( i < length && deadline.timeRemaining() > MIN_WORK_TIME ); i++ ) {
					var key = null;
					try {
						key = store.key( i );
					} catch ( e ) {}
					if ( key !== null && key.slice( 0, prefixLength ) === EXPIRY_PREFIX ) {
						keys.push( key.slice( prefixLength ) );
					}
				}
				d.resolve( keys );
			} );
		} ).promise();
	};

	/**
	 * @inheritdoc
	 */
	ve.init.sa.SafeStorage.prototype.isExpired = function ( key ) {
		var expiry;
		try {
			expiry = this.store.getItem( EXPIRY_PREFIX + key );
		} catch ( e ) {
			return false;
		}
		return !!expiry && expiry < Math.floor( Date.now() / 1000 );
	};
}() );
