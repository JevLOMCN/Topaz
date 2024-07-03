/*!
 * VisualEditor LeafNode mixin.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Leaf node mixin.
 *
 * @class
 * @abstract
 * @constructor
 */
ve.LeafNode = function VeLeafNode() {
	//
};

/* Methods */

/**
 * Check if the node has children.
 *
 * @return {boolean} Whether the node has children
 */
ve.LeafNode.prototype.hasChildren = function () {
	return false;
};
