ve.init.createConflictableStorage = function ( storage ) {
	var conflictKey = '__conflictId';
	var EXPIRY_PREFIX = '_EXPIRY_';

	/**
	 * Conflict-safe storage extending a ve.init.SafeStorage instance
	 *
	 * Implements conflict handling for localStorage:
	 * Any time the storage is used and it is detected that another process has
	 * modified the underlying data, all the managed keys are restored from an
	 * in-memory cache. There is no merging of data, all managed keys are either
	 * completely overwritten, or deleted if they were not originally set.
	 *
	 * This would be namespaced ve.init.ConflictableStorage, but as a generated class
	 * it is never exported.
	 *
	 * @class ve.init.ConflictableStorage
	 * @extends ve.init.SafeStorage
	 *
	 * @constructor
	 * @param {Storage|undefined} store The Storage instance to wrap around
	 */
	function ConflictableStorage() {
		// Parent constructor
		ConflictableStorage.super.apply( this, arguments );

		this.storageMayConflict = false;
		this.conflictBackup = {};
		this.conflictableKeys = {};
		this.conflictId = null;
	}

	/* Inheritance */

	// Dynamically extend the class of the storage object, in case
	// it is a sub-class of SafeStorage.
	var ParentStorage = storage.constructor;
	OO.inheritClass( ConflictableStorage, ParentStorage );

	/* Methods */

	/**
	 * @inheritdoc
	 */
	ConflictableStorage.prototype.set = function ( key, value ) {
		if ( key === conflictKey ) {
			throw new Error( 'Can\'t set key ' + conflictKey + ' directly.' );
		}
		if ( this.storageMayConflict ) {
			if ( this.isConflicted() ) {
				this.overwriteFromBackup();
			}
			if ( Object.prototype.hasOwnProperty.call( this.conflictableKeys, key ) ) {
				this.conflictBackup[ key ] = value;
			}
		}

		// Parent method
		return ConflictableStorage.super.prototype.set.apply( this, arguments );
	};

	/**
	 * @inheritdoc
	 */
	ConflictableStorage.prototype.remove = function ( key ) {
		if ( key === conflictKey ) {
			throw new Error( 'Can\'t remove key ' + conflictKey + ' directly.' );
		}
		if ( this.storageMayConflict ) {
			if ( this.isConflicted() ) {
				this.overwriteFromBackup();
			}
			if ( Object.prototype.hasOwnProperty.call( this.conflictableKeys, key ) ) {
				delete this.conflictBackup[ key ];
			}
		}

		// Parent method
		return ConflictableStorage.super.prototype.remove.apply( this, arguments );
	};

	/**
	 * @inheritdoc
	 */
	ConflictableStorage.prototype.get = function () {
		if ( this.isConflicted() ) {
			this.overwriteFromBackup();
		}

		// Parent method
		return ConflictableStorage.super.prototype.get.apply( this, arguments );
	};

	/**
	 * @inheritdoc
	 */
	ConflictableStorage.prototype.setExpires = function ( key ) {
		// Parent method
		ConflictableStorage.super.prototype.setExpires.apply( this, arguments );

		if ( this.storageMayConflict ) {
			if ( Object.prototype.hasOwnProperty.call( this.conflictableKeys, key ) ) {
				var expiryAbsolute = null;
				try {
					expiryAbsolute = this.store.getItem( EXPIRY_PREFIX + key );
				} catch ( e ) {}

				if ( expiryAbsolute ) {
					this.conflictBackup[ EXPIRY_PREFIX + key ] = expiryAbsolute;
				} else {
					delete this.conflictBackup[ EXPIRY_PREFIX + key ];
				}
			}
		}
	};

	/**
	 * Check if another process has written to the shared storage, leaving
	 * our data in a conflicted state.
	 *
	 * @return {boolean} Data is conflicted
	 */
	ConflictableStorage.prototype.isConflicted = function () {
		if ( !this.storageMayConflict ) {
			return false;
		}
		// Read directly from store to avoid any caching used by sub-classes
		try {
			return this.store.getItem( conflictKey ) !== this.conflictId;
		} catch ( e ) {
			return false;
		}
	};

	/**
	 * Overwrite data in the store from our in-memory backup
	 *
	 * Only keys added in #addConflictableKeys are restored
	 */
	ConflictableStorage.prototype.overwriteFromBackup = function () {
		// Call parent method directly when setting conflict key
		ConflictableStorage.super.prototype.set.call( this, conflictKey, this.conflictId );

		for ( var key in this.conflictableKeys ) {
			if ( Object.prototype.hasOwnProperty.call( this.conflictBackup, key ) && this.conflictBackup[ key ] !== null ) {
				var expiryKey = EXPIRY_PREFIX + key;
				var expiryAbsolute = this.conflictBackup[ expiryKey ];
				var expiry = null;
				if ( expiryAbsolute ) {
					expiry = expiryAbsolute - Math.floor( Date.now() / 1000 );
				}

				// Call parent methods directly when restoring
				ConflictableStorage.super.prototype.set.call( this, key, this.conflictBackup[ key ], expiry );
			} else {
				ConflictableStorage.super.prototype.remove.call( this, key, this.conflictBackup[ key ] );
			}
		}
	};

	/**
	 * Add keys which will need to be conflict-aware
	 *
	 * @param {Object} keys Object with conflict-aware keys as keys, and value set to true
	 */
	ConflictableStorage.prototype.addConflictableKeys = function ( keys ) {
		ve.extendObject( this.conflictableKeys, keys );

		this.storageMayConflict = true;

		if ( !this.conflictId ) {
			this.conflictId = Math.random().toString( 36 ).slice( 2 );
			// Call parent method directly when setting conflict key
			ConflictableStorage.super.prototype.set.call( this, conflictKey, this.conflictId );
		}

		for ( var key in keys ) {
			if ( Object.prototype.hasOwnProperty.call( keys, key ) ) {
				this.conflictBackup[ key ] = this.get( key );

				var expiryAbsolute = null;
				try {
					expiryAbsolute = this.store.getItem( EXPIRY_PREFIX + key );
				} catch ( e ) {}

				if ( expiryAbsolute ) {
					this.conflictBackup[ EXPIRY_PREFIX + key ] = expiryAbsolute;
				}
			}
		}

	};

	return new ConflictableStorage( storage.store );
};
