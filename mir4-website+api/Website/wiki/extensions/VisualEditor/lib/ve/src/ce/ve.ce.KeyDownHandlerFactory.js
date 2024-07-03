/*!
 * VisualEditor KeyDownHandlerFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Key down handler factory.
 *
 * @class
 * @extends OO.Factory
 * @constructor
 */
ve.ce.KeyDownHandlerFactory = function VeCeKeyDownHandlerFactory() {
	// Parent constructor
	ve.ce.KeyDownHandlerFactory.super.apply( this, arguments );

	// Handlers which match all kinds and a specific type
	this.handlerNamesByKeys = {};
};

/* Inheritance */

OO.inheritClass( ve.ce.KeyDownHandlerFactory, OO.Factory );

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
ve.ce.KeyDownHandlerFactory.prototype.register = function ( constructor ) {
	// Parent method
	ve.ce.KeyDownHandlerFactory.super.prototype.register.call( this, constructor );

	var keys = constructor.static.keys;
	var name = constructor.static.name;

	// TODO: Clean up handlerNamesByKeys in unregister
	for ( var i = 0, ilen = keys.length; i < ilen; i++ ) {
		this.handlerNamesByKeys[ keys[ i ] ] = this.handlerNamesByKeys[ keys[ i ] ] || [];
		if ( this.handlerNamesByKeys[ keys[ i ] ].indexOf( name ) === -1 ) {
			this.handlerNamesByKeys[ keys[ i ] ].push( name );
		}
	}
};

/**
 * Get the handler for a specific key
 *
 * @param {number} key Key code
 * @param {string} selectionName Selection type name
 * @return {Function[]} Matched handlers
 */
ve.ce.KeyDownHandlerFactory.prototype.lookupHandlersForKey = function ( key, selectionName ) {
	var constructors = [],
		names = this.handlerNamesByKeys[ key ] || [];

	// Length is likely to be 1 or 0 so don't cache
	for ( var i = 0; i < names.length; i++ ) {
		var constructor = this.registry[ names[ i ] ];
		var supportedSelections = constructor.static.supportedSelections;
		if ( !supportedSelections || supportedSelections.indexOf( selectionName ) !== -1 ) {
			constructors.push( constructor );
		}
	}

	return constructors;
};

/**
 * Execute the handlers for a specific key
 *
 * @param {number} key Key code
 * @param {string} selectionName Selection type name
 * @param {ve.ce.Surface} surface
 * @param {jQuery.Event} e Key down event
 * @return {boolean} Some handlers acted
 */
ve.ce.KeyDownHandlerFactory.prototype.executeHandlersForKey = function ( key, selectionName, surface, e ) {
	var acted = false,
		handlers = this.lookupHandlersForKey( key, selectionName );

	// Length is likely to be 1 or 0 so don't cache
	for ( var i = 0; i < handlers.length; i++ ) {
		if ( handlers[ i ].static.execute( surface, e ) ) {
			acted = true;
		}
	}

	return acted;
};

/* Initialization */

ve.ce.keyDownHandlerFactory = new ve.ce.KeyDownHandlerFactory();
