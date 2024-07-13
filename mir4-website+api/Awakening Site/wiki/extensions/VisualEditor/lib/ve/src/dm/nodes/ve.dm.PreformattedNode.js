/*!
 * VisualEditor DataModel PreformattedNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel preformatted node.
 *
 * @class
 * @extends ve.dm.ContentBranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.PreformattedNode = function VeDmPreformattedNode() {
	// Parent constructor
	ve.dm.PreformattedNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.PreformattedNode, ve.dm.ContentBranchNode );

/* Static Properties */

ve.dm.PreformattedNode.static.name = 'preformatted';

ve.dm.PreformattedNode.static.hasSignificantWhitespace = true;

ve.dm.PreformattedNode.static.matchTagNames = [ 'pre' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.PreformattedNode );
