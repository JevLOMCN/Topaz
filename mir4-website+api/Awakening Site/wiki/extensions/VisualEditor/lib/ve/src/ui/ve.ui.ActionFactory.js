/*!
 * VisualEditor UserInterface ActionFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Action factory.
 *
 * @class
 * @extends OO.Factory
 * @constructor
 */
ve.ui.ActionFactory = function VeUiActionFactory() {
	// Parent constructor
	OO.Factory.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.ActionFactory, OO.Factory );

/* Methods */

/**
 * Check if an action supports a method.
 *
 * @param {string} action Name of action
 * @param {string} method Name of method
 * @return {boolean} The action supports the method
 */
ve.ui.ActionFactory.prototype.doesActionSupportMethod = function ( action, method ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, action ) ) {
		return this.registry[ action ].static.methods.indexOf( method ) !== -1;
	}
	throw new Error( 'Unknown action: ' + action );
};

/* Initialization */

ve.ui.actionFactory = new ve.ui.ActionFactory();
