/*!
 * VisualEditor DataModel AlienBlockNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel alien block node.
 *
 * @class
 * @extends ve.dm.AlienNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.AlienBlockNode = function VeDmAlienBlockNode() {
	// Parent constructor
	ve.dm.AlienBlockNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.AlienBlockNode, ve.dm.AlienNode );

ve.dm.AlienBlockNode.static.name = 'alienBlock';

ve.dm.AlienBlockNode.static.matchRdfaTypes = [];

ve.dm.AlienBlockNode.static.matchFunction = null;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.AlienBlockNode );
