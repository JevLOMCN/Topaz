/**
 * A wrapper for an HTML5 Storage interface (`localStorage` or `sessionStorage`)
 * that is safe to call on all browsers.
 *
 * @abstract
 * @class ve.init.SafeStorage
 *
 * @constructor
 * @param {Storage|undefined} store The Storage instance to wrap around
 */
ve.init.SafeStorage = function ( store ) {
	this.store = store;
};

/**
 * Retrieve value from device storage.
 *
 * @abstract
 * @param {string} key Key of item to retrieve
 * @return {string|null|boolean} String value, null if no value exists, or false
 *  if storage is not available.
 */
ve.init.SafeStorage.prototype.get = null;

/**
 * Set a value in device storage.
 *
 * @abstract
 * @param {string} key Key name to store under
 * @param {string} value Value to be stored
 * @param {number} [expiry] Number of seconds after which this item can be deleted
 * @return {boolean} The value was set
 */
ve.init.SafeStorage.prototype.set = null;

/**
 * Remove a value from device storage.
 *
 * @abstract
 * @param {string} key Key of item to remove
 * @return {boolean} Whether the save succeeded or not
 */
ve.init.SafeStorage.prototype.remove = null;

/**
 * Retrieve JSON object from device storage.
 *
 * @abstract
 * @param {string} key Key of item to retrieve
 * @return {Object|null|boolean} Object, null if no value exists or value
 *  is not JSON-parseable, or false if storage is not available.
 */
ve.init.SafeStorage.prototype.getObject = null;

/**
 * Set an object value in device storage by JSON encoding
 *
 * @abstract
 * @param {string} key Key name to store under
 * @param {Object} value Object value to be stored
 * @param {number} [expiry] Number of seconds after which this item can be deleted
 * @return {boolean} The value was set
 */
ve.init.SafeStorage.prototype.setObject = null;

/**
 * Set the expiry time for an item in the store
 *
 * @param {string} key Key name
 * @param {number} [expiry] Number of seconds after which this item can be deleted,
 *  omit to clear the expiry (either making the item never expire, or to clean up
 *  when deleting a key).
 */
ve.init.SafeStorage.prototype.setExpires = null;

/**
 * Clear any expired items from the store
 *
 * @protected
 * @return {jQuery.Promise} Resolves when items have been expired
 */
ve.init.SafeStorage.prototype.clearExpired = null;

/**
 * Get all keys with expiry values
 *
 * @protected
 * @return {jQuery.Promise} Promise resolving with all the keys which have
 *  expiry values (unprefixed), or as many could be retrieved in the allocated time.
 */
ve.init.SafeStorage.prototype.getExpiryKeys = null;

/**
 * Check if a given key has expired
 *
 * @protected
 * @param {string} key Key name
 * @return {boolean} Whether key is expired
 */
ve.init.SafeStorage.prototype.isExpired = null;
