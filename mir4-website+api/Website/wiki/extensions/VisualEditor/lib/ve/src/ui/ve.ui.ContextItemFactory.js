/*!
 * VisualEditor UserInterface ContextItemFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Factory for context items.
 *
 * @class
 * @extends OO.Factory
 * @mixins ve.ui.ModeledFactory
 *
 * @constructor
 */
ve.ui.ContextItemFactory = function VeUiContextItemFactory() {
	// Parent constructor
	ve.ui.ContextItemFactory.super.call( this );

	// Mixin constructors
	ve.ui.ModeledFactory.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.ContextItemFactory, OO.Factory );
OO.mixinClass( ve.ui.ContextItemFactory, ve.ui.ModeledFactory );

/* Methods */

/**
 * Check if an item is embeddable.
 *
 * @param {string} name Symbolic item name
 * @return {boolean} Item is embeddable
 */
ve.ui.ContextItemFactory.prototype.isEmbeddable = function ( name ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, name ) ) {
		return !!this.registry[ name ].static.embeddable;
	}
	throw new Error( 'Unrecognized symbolic name: ' + name );
};

/**
 * Check if an item is exclusive.
 *
 * @param {string} name Symbolic item name
 * @return {boolean} Item is exclusive
 */
ve.ui.ContextItemFactory.prototype.isExclusive = function ( name ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, name ) ) {
		return !!this.registry[ name ].static.exclusive;
	}
	throw new Error( 'Unrecognized symbolic name: ' + name );
};

/* Initialization */

ve.ui.contextItemFactory = new ve.ui.ContextItemFactory();
