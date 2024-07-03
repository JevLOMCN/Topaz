/*!
 * VisualEditor HashValueStore class.
 *
 * @copyright See AUTHORS.txt
 */

/* global SparkMD5 */

/**
 * Ordered append-only hash store, whose values once inserted are immutable
 *
 * Values are objects, strings or Arrays, and are hashed using an algorithm with low collision
 * probability: values with the same hash can be assumed equal.
 *
 * Values are stored in insertion order, and the store can be sliced to get a subset of values
 * inserted consecutively.
 *
 * Two stores can be merged even if they have differently computed hashes, so long as two values
 * will (with high probability) have the same hash only if equal. In this case, equivalent
 * values can have two different hashes.
 *
 * @class
 * @constructor
 * @param {Object[]} [values] Values to insert
 */
ve.dm.HashValueStore = function VeDmHashValueStore( values ) {
	// Maps hashes to values
	this.hashStore = {};
	// Hashes in order of insertion (used for slicing)
	this.hashes = [];
	if ( values ) {
		this.hashAll( values );
	}
};

/* Inheritance */

OO.initClass( ve.dm.HashValueStore );

/* Static Methods */

/**
 * Deserialize a store from a JSONable object
 *
 * @param {Function} deserializeValue Deserializer for arbitrary store values
 * @param {Object|null} data Store serialized as a JSONable object
 * @return {ve.dm.HashValueStore} Deserialized store
 */
ve.dm.HashValueStore.static.deserialize = function ( deserializeValue, data ) {
	var store = new ve.dm.HashValueStore();

	if ( !data ) {
		return store;
	}

	store.hashes = data.hashes.slice();
	store.hashStore = {};
	for ( var hash in data.hashStore ) {
		store.hashStore[ hash ] = deserializeValue( data.hashStore[ hash ] );
	}
	return store;
};

/* Methods */

/**
 * Serialize the store into a JSONable object
 *
 * @param {Function} serializeValue Serializer for arbitrary store values
 * @return {Object|null} Serialized store, null if empty
 */
ve.dm.HashValueStore.prototype.serialize = function ( serializeValue ) {
	var serialized = {};

	for ( var hash in this.hashStore ) {
		serialized[ hash ] = serializeValue( this.hashStore[ hash ] );
	}
	return this.getLength() ? {
		hashes: this.hashes.slice(),
		hashStore: serialized
	} : null;
};

/**
 * Get the number of values in the store
 *
 * @return {number} Number of values in the store
 */
ve.dm.HashValueStore.prototype.getLength = function () {
	return this.hashes.length;
};

ve.dm.HashValueStore.prototype.truncate = function ( start ) {
	var removedHashes = this.hashes.splice( start );
	for ( var i = 0, len = removedHashes.length; i < len; i++ ) {
		delete this.hashStore[ removedHashes[ i ] ];
	}
};

/**
 * Return a new store containing a slice of the values in insertion order
 *
 * @param {number} [start] Include values from position start onwards (default: 0)
 * @param {number} [end] Include values to position end exclusive (default: slice to end)
 * @return {ve.dm.HashValueStore} Slice of the current store (with non-cloned value references)
 */
ve.dm.HashValueStore.prototype.slice = function ( start, end ) {
	var sliced = new this.constructor();

	sliced.hashes = this.hashes.slice( start, end );
	for ( var i = 0, len = sliced.hashes.length; i < len; i++ ) {
		var hash = sliced.hashes[ i ];
		sliced.hashStore[ hash ] = this.hashStore[ hash ];
	}
	return sliced;
};

/**
 * Clone a store.
 *
 * @deprecated Use #slice with no arguments.
 * @return {ve.dm.HashValueStore} New store with the same contents as this one
 */
ve.dm.HashValueStore.prototype.clone = function () {
	return this.slice();
};

/**
 * Insert a value into the store
 *
 * @param {Object|string|Array} value Value to store
 * @param {string} [stringified] Stringified version of value; default OO.getHash( value )
 * @return {string} Hash value with low collision probability
 */
ve.dm.HashValueStore.prototype.hash = function ( value, stringified ) {
	var hash = this.hashOfValue( value, stringified );

	if ( !this.hashStore[ hash ] ) {
		if ( Array.isArray( value ) ) {
			this.hashStore[ hash ] = ve.copy( value );
		} else if ( value !== null && typeof value === 'object' ) {
			this.hashStore[ hash ] = ve.cloneObject( value );
		} else {
			this.hashStore[ hash ] = value;
		}
		this.hashes.push( hash );
	}

	return hash;
};

/**
 * Replace a value's stored hash, e.g. if the value has changed and you want to discard the old one.
 *
 * @param {string} oldHash The value's previously stored hash
 * @param {Object|string|Array} value New value
 * @throws {Error} Old hash not found
 * @return {string} New hash
 */
ve.dm.HashValueStore.prototype.replaceHash = function ( oldHash, value ) {
	var newHash = this.hashOfValue( value );

	if ( !Object.prototype.hasOwnProperty.call( this.hashStore, oldHash ) ) {
		throw new Error( 'Old hash not found: ' + oldHash );
	}

	delete this.hashStore[ oldHash ];

	if ( this.hashStore[ newHash ] === undefined ) {
		this.hashStore[ newHash ] = value;
		this.hashes.splice( this.hashes.indexOf( oldHash ), 1, newHash );
	}

	return newHash;
};

/**
 * Get the hash of a value without inserting it in the store
 *
 * @param {Object|string|Array} value Value to hash
 * @param {string} [stringified] Stringified version of value; default OO.getHash( value )
 * @return {string} Hash value with low collision probability
 */
ve.dm.HashValueStore.prototype.hashOfValue = function ( value, stringified ) {
	if ( typeof stringified !== 'string' ) {
		stringified = OO.getHash( value );
	}

	// We don't need cryptographically strong hashes, just low collision probability. Given
	// effectively random hash distribution, for n values hashed into a space of m hash
	// strings, the probability of a collision is roughly n^2 / (2m). We use 16 hex digits
	// of MD5 i.e. 2^64 possible hash strings, so given 2^16 stored values the collision
	// probability is about 2^-33 =~ 0.0000000001 , i.e. negligible.
	//
	// Prefix with a letter to prevent all numeric hashes, and to constrain the space of
	// possible object property values.
	return 'h' + SparkMD5.hash( stringified ).slice( 0, 16 );
};

/**
 * Get the hashes of values in the store
 *
 * Same as hash but with arrays.
 *
 * @param {Object[]} values Values to lookup or store
 * @return {string[]} The hashes of the values in the store
 */
ve.dm.HashValueStore.prototype.hashAll = function ( values ) {
	var hashes = [];
	for ( var i = 0, length = values.length; i < length; i++ ) {
		hashes.push( this.hash( values[ i ] ) );
	}
	return hashes;
};

/**
 * Get the value stored for a particular hash
 *
 * @param {string} hash Hash to look up
 * @return {Object|undefined} Value stored for this hash if present, else undefined
 */
ve.dm.HashValueStore.prototype.value = function ( hash ) {
	return this.hashStore[ hash ];
};

/**
 * Get the values stored for a list of hashes
 *
 * Same as value but with arrays.
 *
 * @param {string[]} hashes Hashes to lookup
 * @return {Array} Values for these hashes (undefined for any not present)
 */
ve.dm.HashValueStore.prototype.values = function ( hashes ) {
	var values = [];
	for ( var i = 0, length = hashes.length; i < length; i++ ) {
		values.push( this.value( hashes[ i ] ) );
	}
	return values;
};

/**
 * Merge another store into this store.
 *
 * It is allowed for the two stores to have differently computed hashes, so long as two values
 * will (with high probability) have the same hash only if equal. In this case, equivalent
 * values can have two different hashes.
 *
 * Values are added in the order they appear in the other store. Objects added to the store are
 * added by reference, not cloned, unlike in .hash()
 *
 * @param {ve.dm.HashValueStore} other Store to merge into this one
 */
ve.dm.HashValueStore.prototype.merge = function ( other ) {
	if ( other === this ) {
		return;
	}

	for ( var i = 0, len = other.hashes.length; i < len; i++ ) {
		var hash = other.hashes[ i ];
		if ( !Object.prototype.hasOwnProperty.call( this.hashStore, hash ) ) {
			this.hashStore[ hash ] = other.hashStore[ hash ];
			this.hashes.push( hash );
		}
	}
};

/**
 * Clone this store excluding certain values, like a set difference operation
 *
 * @param {ve.dm.HashValueStore|Object} omit Store of values to omit, or object whose keys are hashes to emit
 * @return {ve.dm.HashValueStore} All values in this that do not appear in other
 */
ve.dm.HashValueStore.prototype.difference = function ( omit ) {
	var store = new this.constructor();

	if ( omit instanceof ve.dm.HashValueStore ) {
		omit = omit.hashStore;
	}
	for ( var i = 0, len = this.hashes.length; i < len; i++ ) {
		var hash = this.hashes[ i ];
		if ( !Object.prototype.hasOwnProperty.call( omit, hash ) ) {
			store.hashes.push( hash );
			store.hashStore[ hash ] = this.hashStore[ hash ];
		}
	}
	return store;
};
