/*!
 * VisualEditor DataModel AlienInlineNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel alien inline node.
 *
 * @class
 * @extends ve.dm.AlienNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.AlienInlineNode = function VeDmAlienInlineNode() {
	// Parent constructor
	ve.dm.AlienInlineNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.AlienInlineNode, ve.dm.AlienNode );

ve.dm.AlienInlineNode.static.name = 'alienInline';

ve.dm.AlienInlineNode.static.isContent = true;

ve.dm.AlienInlineNode.static.matchRdfaTypes = [];

ve.dm.AlienInlineNode.static.matchFunction = null;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.AlienInlineNode );
