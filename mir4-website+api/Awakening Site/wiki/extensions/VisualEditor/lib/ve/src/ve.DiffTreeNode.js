/*!
 * VisualEditor DiffTreeNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/* global treeDiffer */

/**
 * Tree node for conducting a tree diff.
 *
 * @class
 * @extends treeDiffer.TreeNode
 * @constructor
 * @param {Object} node Node of any type
 * @param {Object} config
 */
ve.DiffTreeNode = function ( node ) {
	// Parent constructor
	ve.DiffTreeNode.super.apply( this, arguments );

	this.doc = node.getDocument();
};

/* Inheritance */

OO.inheritClass( ve.DiffTreeNode, treeDiffer.TreeNode );

/* Methods */

/**
 * Determine whether two nodes are equal. Branch nodes are considered equal if
 * they have the same types and element.attributes. Content branch nodes are
 * only equal if they also have the same content.
 *
 * @param {ve.DiffTreeNode} otherNode Node to compare to this node
 * @return {boolean} The nodes are equal
 */
ve.DiffTreeNode.prototype.isEqual = function ( otherNode ) {
	if ( !this.node.isDiffedAsTree() && !otherNode.node.isDiffedAsTree() ) {
		return ve.dm.VisualDiff.static.compareNodes( this.node, otherNode.node );
	} else {
		return ve.dm.ElementLinearData.static.compareElementsUnannotated( this.node.element, otherNode.node.element );
	}
};

/**
 * Get the children of the original node
 *
 * @return {Array} Array of nodes the same type as the original node
 */
ve.DiffTreeNode.prototype.getOriginalNodeChildren = function () {
	return ( this.node.isDiffedAsTree() && this.node.children ) || [];
};
