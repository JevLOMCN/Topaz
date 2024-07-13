/*!
 * VisualEditor DataModel ContentBranchNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel content branch node.
 *
 * @class
 * @abstract
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.ContentBranchNode = function VeDmContentBranchNode() {
	// Parent constructor
	ve.dm.ContentBranchNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.ContentBranchNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.ContentBranchNode.static.canContainContent = true;

ve.dm.ContentBranchNode.static.isDiffedAsLeaf = true;
