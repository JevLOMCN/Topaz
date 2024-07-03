/*!
 * VisualEditor LinearData class.
 *
 * Class containing linear data and an hash-value store.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic linear data storage
 *
 * @class
 * @constructor
 * @param {ve.dm.HashValueStore} store Hash-value store
 * @param {Array} [data] Linear data
 */
ve.dm.LinearData = function VeDmLinearData( store, data ) {
	this.store = store;
	this.data = data ? ve.deepFreeze( data, true ) : [];
};

/* Inheritance */

OO.initClass( ve.dm.LinearData );

/* Static Methods */

/**
 * Get the type of an element
 *
 * This will return the same string for close and open elements.
 *
 * @param {Object} item Element item
 * @return {string} Type of the element
 */
ve.dm.LinearData.static.getType = function ( item ) {
	return this.isCloseElementData( item ) ? item.type.slice( 1 ) : item.type;
};

/**
 * Check if data item is an element.
 *
 * This method assumes that any value that has a type property that's a string is an element object.
 *
 * Element data:
 *
 *      <heading> a </heading> <paragraph> b c <img></img> </paragraph>
 *     ^         . ^          ^           . . ^     ^     ^            .
 *
 * @param {Object|Array|string} item Linear data item
 * @return {boolean} Item is an element
 */
ve.dm.LinearData.static.isElementData = function ( item ) {
	// Data exists and appears to be an element
	return item !== undefined && typeof item.type === 'string';
};

/**
 * Checks if data item is an open element.
 *
 * @param {Object} item Element item
 * @return {boolean} Item is an open element
 */
ve.dm.LinearData.static.isOpenElementData = function ( item ) {
	return this.isElementData( item ) && item.type.charAt( 0 ) !== '/';
};

/**
 * Checks if data item is a close element.
 *
 * @param {Object} item Element item
 * @return {boolean} Item is a close element
 */
ve.dm.LinearData.static.isCloseElementData = function ( item ) {
	return this.isElementData( item ) && item.type.charAt( 0 ) === '/';
};

/* Methods */

/**
 * Gets linear data from a specified index, or all data if no index specified
 *
 * @param {number} [offset] Offset to get data from
 * @return {Object|Array} Data from index, or all data (by reference)
 */
ve.dm.LinearData.prototype.getData = function ( offset ) {
	return offset === undefined ? this.data : this.data[ offset ];
};

/**
 * Sets linear data at a specified index
 *
 * @param {number} offset Offset to set data at
 * @param {Object|string} value Value to store
 */
ve.dm.LinearData.prototype.setData = function ( offset, value ) {
	this.data[ offset ] = typeof value === 'object' ? ve.deepFreeze( value ) : value;
	// this.data[ offset ] = value;
};

/**
 * Modify an existing object in the linear model
 *
 * As objects in the model and immutable, this creates
 * a clone which is passed to a callback function for
 * modification, then the clone is inserted into the model.
 *
 * @param {number} offset Offset to modify data at
 * @param {Function} modify Modification function. First argument is the data element to modify.
 */
ve.dm.LinearData.prototype.modifyData = function ( offset, modify ) {
	// Unfreeze object by creating a new copy
	var newItem = ve.copy( this.getData( offset ) );
	// Modify via callback
	modify( newItem );
	// Insert into linear model, re-freezing it
	this.setData( offset, newItem );
};

/**
 * Push data to the end of the array
 *
 * @param {...Object} [value] Values to store
 * @return {number} The new length of the linear data
 */
ve.dm.LinearData.prototype.push = function () {
	return Array.prototype.push.apply( this.data, arguments );
};

/**
 * Gets length of the linear data
 *
 * @return {number} Length of the linear data
 */
ve.dm.LinearData.prototype.getLength = function () {
	return this.getData().length;
};

/**
 * Gets the hash-value store
 *
 * @return {ve.dm.HashValueStore} The hash-value store
 */
ve.dm.LinearData.prototype.getStore = function () {
	return this.store;
};

/**
 * Slice linear data
 *
 * @param {number} begin Index to begin at
 * @param {number} [end] Index to end at
 * @return {Array} One-level deep copy of sliced range
 */
ve.dm.LinearData.prototype.slice = function () {
	return Array.prototype.slice.apply( this.data, arguments );
};

/**
 * Slice linear data and return new LinearData object containing result
 *
 * @param {number} begin Index to begin at
 * @param {number} [end] Index to end at
 * @return {ve.dm.LinearData} LinearData object containing one-level deep copy of sliced range
 */
ve.dm.LinearData.prototype.sliceObject = function () {
	return new this.constructor( this.getStore(), this.slice.apply( this, arguments ) );
};

/**
 * Splice linear data
 *
 * @param {number} index Splice from
 * @param {number} howmany Items to be removed
 * @param {...Object} [element] Items to be inserted
 * @return {Array} Elements removed
 */
ve.dm.LinearData.prototype.splice = function () {
	return Array.prototype.splice.apply( this.data, arguments );
};

/**
 * Splice linear data and return new LinearData object containing result
 *
 * @param {number} index Splice from
 * @param {number} howmany Items to be removed
 * @param {...Object} [element] Items to be inserted
 * @return {ve.dm.LinearData} LinearData object containing elements removed
 */
ve.dm.LinearData.prototype.spliceObject = function () {
	return new this.constructor( this.getStore(), this.splice.apply( this, arguments ) );
};

/**
 * Returns ve.batchSplice of linear data
 *
 * @see ve#batchSplice
 * @param {number} offset
 * @param {number} remove
 * @param {Array} data
 * @return {Array}
 */
ve.dm.LinearData.prototype.batchSplice = function ( offset, remove, data ) {
	return ve.batchSplice( this.getData(), offset, remove, data );
};

/**
 * Returns ve.batchSplice of linear data, wrapped in a LinearData object
 *
 * @see ve#batchSplice
 * @param {number} offset
 * @param {number} remove
 * @param {Array} data
 * @return {ve.dm.LinearData}
 */
ve.dm.LinearData.prototype.batchSpliceObject = function ( offset, remove, data ) {
	return new this.constructor(
		this.getStore(),
		this.batchSplice( offset, remove, data )
	);
};

/**
 * Get a slice or copy of the provided data.
 *
 * @param {ve.Range} [range] Range of data to get, all data will be given by default
 * @param {boolean} [deep=false] Whether to return a deep copy (WARNING! This may be very slow)
 * @return {Array} Slice or copy of data
 */
ve.dm.LinearData.prototype.getDataSlice = function ( range, deep ) {
	var end,
		start = 0,
		length = this.getLength();
	if ( range !== undefined ) {
		start = Math.max( 0, Math.min( length, range.start ) );
		end = Math.max( 0, Math.min( length, range.end ) );
	}
	var data = this.slice( start, end );
	// Return either the slice or a deep copy of the slice
	return deep ? ve.copy( data ) : data;
};

/*
 * Clone the data, with a deep copy of the data.
 *
 * @return {ve.dm.LinearData} Clone of this object
 */
ve.dm.LinearData.prototype.clone = function () {
	return new this.constructor(
		this.getStore(),
		ve.copy( this.data )
	);
};
