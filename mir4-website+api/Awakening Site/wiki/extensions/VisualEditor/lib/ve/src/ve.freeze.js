/*!
 * VisualEditor Object freeze utilities.
 *
 * @copyright See AUTHORS.txt
 */

( function () {
	var freezeProxyHandler = {
		set: function ( obj, name ) {
			throw new Error( 'Object is frozen, can\'t set property: ' + name );
		},
		deleteProperty: function ( obj, name ) {
			throw new Error( 'Object is frozen, can\'t delete property: ' + name );
		}
	};

	if ( !window.Proxy || !window.Set ) {
		return;
	}
	var deepFreeze;
	/**
	 * Deep freeze an object, making it immutable
	 *
	 * Original object properties are overwritten with frozen versions.
	 *
	 * @param {Object} object Object to freeze
	 * @param {boolean} [onlyProperties] Only freeze properties (or array items)
	 * @param {Set} [seen] Set of already-seen objects (for internal, recursive use)
	 * @return {Object} Immutable deep copy of the original object
	 */
	ve.deepFreeze = deepFreeze = function ( object, onlyProperties, seen ) {
		if ( !seen ) {
			seen = new Set();
			seen.add( object );
		}
		for ( var name in object ) {
			if ( Object.prototype.hasOwnProperty.call( object, name ) ) {
				var value = object[ name ];
				if (
					// Truth check so we don't try to freeze null
					value &&
					typeof value === 'object' &&
					!seen.has( value ) &&
					!Object.isFrozen( value )
				) {
					seen.add( value );
					// Recurse. Use local name (tests may alias ve.deepFreeze)
					object[ name ] = deepFreeze( value, false, seen );
				}
			}
		}

		if ( !onlyProperties ) {
			object = new window.Proxy( object, freezeProxyHandler );
			// Object#freeze isn't really necessary after proxying,
			// but use it so we can detect frozen objects with Object.isFrozen.
			Object.freeze( object );
		}
		return object;
	};
}() );
