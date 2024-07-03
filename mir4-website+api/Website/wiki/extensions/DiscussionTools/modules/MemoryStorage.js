/**
 * MemoryStorage is a wrapper around mw.SafeStorage objects.
 *
 * It provides two mechanisms to improve their behavior when the underlying storage mechanism fails
 * (e.g. quota exceeded):
 *
 * - Duplicating their contents in memory, so that the storage can be relied on before the page has
 *   been reloaded.
 * - Storing all keys in a single underlying key, to make the stores are atomic: either all values
 *   are stored or none.
 *
 * This has two additional consequences which are convenient for our use case:
 *
 * - All keys share the same expiry time, removing the need to specify it repeatedly.
 * - When multiple processes try to write the same keys, all keys are overwritten, so that we don't
 *   have to worry about inconsistent data.
 *
 * @example
 * var sessionStorage = new MemoryStorage( mw.storage.session, 'myprefix' );
 * var localStorage = new MemoryStorage( mw.storage, 'myprefix' );
 */
class MemoryStorage {
	/**
	 * @param {mw.SafeStorage} backend
	 * @param {string} key Key name to store under
	 * @param {number} [expiry] Number of seconds after which this item can be deleted
	 */
	constructor( backend, key, expiry ) {
		this.backend = backend;
		this.key = key;
		this.expiry = expiry;

		this.data = this.backend.getObject( this.key ) || {};
	}

	get( key ) {
		if ( Object.prototype.hasOwnProperty.call( this.data, key ) ) {
			return this.data[ key ];
		}
		return null;
	}

	set( key, value ) {
		this.data[ key ] = value;
		this.backend.setObject( this.key, this.data, this.expiry );
		return true;
	}

	remove( key ) {
		delete this.data[ key ];
		if ( Object.keys( this.data ).length === 0 ) {
			this.backend.remove( this.key );
		} else {
			this.backend.setObject( this.key, this.data, this.expiry );
		}
		return true;
	}

	clear() {
		this.data = {};
		this.backend.remove( this.key );
	}

	// For compatibility with mw.SafeStorage API
	getObject( key ) {
		return this.get( key );
	}

	// For compatibility with mw.SafeStorage API
	setObject( key, value ) {
		return this.set( key, value );
	}
}

module.exports = MemoryStorage;
