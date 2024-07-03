/*!
 * VisualEditor DataTransferHandlerFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Data transfer handler factory.
 *
 * @class
 * @extends OO.Factory
 * @constructor
 */
ve.ui.DataTransferHandlerFactory = function VeUiDataTransferHandlerFactory() {
	// Parent constructor
	ve.ui.DataTransferHandlerFactory.super.apply( this, arguments );

	// Handlers which match all kinds and a specific type
	this.handlerNamesByType = {};
	// Handlers which match a specific kind and type
	this.handlerNamesByKindAndType = {};
	// Handlers which match a specific file extension as a fallback
	this.handlerNamesByExtension = {};
};

/* Inheritance */

OO.inheritClass( ve.ui.DataTransferHandlerFactory, OO.Factory );

/* Methods */

/**
 * Register a constructor with the factory.
 *
 *     function MyClass() {};
 *     OO.initClass( MyClass );
 *     MyClass.static.name = 'hello';
 *     // Register class with the factory, available via the symbolic name "hello"
 *     factory.register( MyClass );
 *
 * See https://doc.wikimedia.org/oojs/master/OO.Factory.html
 *
 * @param {Function} constructor Constructor to use when creating object
 * @param {string} [name] Symbolic name to use for #create().
 *  This parameter may be omitted in favour of letting the constructor decide
 *  its own name, through `constructor.static.name`.
 * @throws {Error} If a parameter is invalid
 */
ve.ui.DataTransferHandlerFactory.prototype.register = function ( constructor ) {
	// Parent method
	ve.ui.DataTransferHandlerFactory.super.prototype.register.apply( this, arguments );

	this.updateIndexes( constructor, true );
};

/**
 * Unregister a constructor from the factory.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.Factory.html
 *
 * @param {string|Function} constructor Constructor function or symbolic name to unregister
 * @throws {Error} If a parameter is invalid
 */
ve.ui.DataTransferHandlerFactory.prototype.unregister = function ( constructor ) {
	// Parent method
	ve.ui.DataTransferHandlerFactory.super.prototype.unregister.apply( this, arguments );

	this.updateIndexes( constructor, false );
};

/**
 * Update indexes used for handler loopup
 *
 * @param {Function} constructor Handler's constructor to insert/remove
 * @param {boolean} insert Insert the handler into the indexes, remove otherwise
 */
ve.ui.DataTransferHandlerFactory.prototype.updateIndexes = function ( constructor, insert ) {
	function ensureArray( obj, prop ) {
		if ( obj[ prop ] === undefined ) {
			obj[ prop ] = [];
		}
		return obj[ prop ];
	}

	function ensureMap( obj, prop ) {
		if ( obj[ prop ] === undefined ) {
			obj[ prop ] = {};
		}
		return obj[ prop ];
	}

	function remove( arr, item ) {
		var index;
		if ( ( index = arr.indexOf( item ) ) !== -1 ) {
			arr.splice( index, 1 );
		}
	}

	var i, j, ilen, jlen,
		kinds = constructor.static.kinds,
		types = constructor.static.types,
		extensions = constructor.static.extensions;

	if ( !kinds ) {
		for ( j = 0, jlen = types.length; j < jlen; j++ ) {
			if ( insert ) {
				ensureArray( this.handlerNamesByType, types[ j ] ).unshift( constructor.static.name );
			} else {
				remove( this.handlerNamesByType[ types[ j ] ], constructor.static.name );
			}
		}
	} else {
		for ( i = 0, ilen = kinds.length; i < ilen; i++ ) {
			for ( j = 0, jlen = types.length; j < jlen; j++ ) {
				if ( insert ) {
					ensureArray(
						ensureMap( this.handlerNamesByKindAndType, kinds[ i ] ),
						types[ j ]
					).unshift( constructor.static.name );
				} else {
					remove( this.handlerNamesByKindAndType[ kinds[ i ] ][ types[ j ] ], constructor.static.name );
				}
			}
		}
	}
	if ( constructor.prototype instanceof ve.ui.FileTransferHandler ) {
		for ( i = 0, ilen = extensions.length; i < ilen; i++ ) {
			if ( insert ) {
				ensureArray( this.handlerNamesByExtension, extensions[ i ] ).unshift( constructor.static.name );
			} else {
				remove( this.handlerNamesByExtension[ extensions[ i ] ], constructor.static.name );
			}
		}
	}
};

/**
 * Get a handler name for a specific data transfer item
 *
 * @param {ve.ui.DataTransferItem} item Data transfer item
 * @param {boolean} isPaste Handler being used for paste
 * @param {boolean} isPasteSpecial Handler being used for "paste special"
 * @return {string|undefined} Handler name, or undefined if not found
 */
ve.ui.DataTransferHandlerFactory.prototype.getHandlerNameForItem = function ( item, isPaste, isPasteSpecial ) {
	// Fetch a given nested property, returning a zero-length array if
	// any component of the path is not present.
	// This is similar to ve.getProp, except with a `hasOwnProperty`
	// test to ensure we aren't fooled by __proto__ and friends.
	function fetch( obj /* , args… */ ) {
		for ( var j = 1; j < arguments.length; j++ ) {
			if (
				typeof arguments[ j ] !== 'string' ||
				!Object.prototype.hasOwnProperty.call( obj, arguments[ j ] )
			) {
				return [];
			}
			obj = obj[ arguments[ j ] ];
		}
		return obj;
	}

	var names = [].concat(
		// 1. Match by kind + type (e.g. 'file' + 'text/html')
		fetch( this.handlerNamesByKindAndType, item.kind, item.type ),
		// 2. Match by just type (e.g. 'image/jpeg')
		fetch( this.handlerNamesByType, item.type ),
		// 3. Match by file extension (e.g. 'csv')
		fetch( this.handlerNamesByExtension, item.getExtension() )
	);

	for ( var i = 0; i < names.length; i++ ) {
		var name = names[ i ];
		var constructor = this.registry[ name ];

		if ( isPasteSpecial && !constructor.static.handlesPasteSpecial ) {
			continue;
		}

		if ( isPaste && !constructor.static.handlesPaste ) {
			continue;
		}

		if ( constructor.static.matchFunction && !constructor.static.matchFunction( item ) ) {
			continue;
		}

		return name;
	}

	// No matching handler
	return;
};

/* Initialization */

ve.ui.dataTransferHandlerFactory = new ve.ui.DataTransferHandlerFactory();
