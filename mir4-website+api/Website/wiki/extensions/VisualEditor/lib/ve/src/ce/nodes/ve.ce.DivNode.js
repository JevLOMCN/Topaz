/*!
 * VisualEditor ContentEditable DivNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable div node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.DivNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.DivNode = function VeCeDivNode() {
	// Parent constructor
	ve.ce.DivNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.DivNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.DivNode.static.name = 'div';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.DivNode );
