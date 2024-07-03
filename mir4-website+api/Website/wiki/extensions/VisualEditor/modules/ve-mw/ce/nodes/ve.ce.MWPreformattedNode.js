/*!
 * VisualEditor ContentEditable MWPreformattedNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MW preformatted node.
 *
 * @class
 * @extends ve.ce.PreformattedNode
 * @constructor
 * @param {ve.dm.MWPreformattedNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWPreformattedNode = function VeCeMWPreformattedNode() {
	// Parent constructor
	ve.ce.MWPreformattedNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWPreformattedNode, ve.ce.PreformattedNode );

/* Static Properties */

ve.ce.MWPreformattedNode.static.name = 'mwPreformatted';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWPreformattedNode );
