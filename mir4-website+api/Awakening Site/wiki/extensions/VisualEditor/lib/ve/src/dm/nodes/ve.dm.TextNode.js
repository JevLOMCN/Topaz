/*!
 * VisualEditor DataModel TextNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel text node.
 *
 * @class
 * @extends ve.dm.LeafNode
 *
 * @constructor
 * @param {number} [length] Length of content data in document
 */
ve.dm.TextNode = function VeDmTextNode( length ) {
	// Parent constructor
	ve.dm.TextNode.super.call( this );

	// TODO: length is only set on construction in tests
	this.length = length || 0;
};

/* Inheritance */

OO.inheritClass( ve.dm.TextNode, ve.dm.LeafNode );

/* Static Properties */

ve.dm.TextNode.static.name = 'text';

ve.dm.TextNode.static.isWrapped = false;

ve.dm.TextNode.static.isContent = true;

ve.dm.TextNode.static.matchTagNames = [];

/* Methods */

ve.dm.TextNode.prototype.canHaveSlugBefore = function () {
	return false;
};

ve.dm.TextNode.prototype.canHaveSlugAfter = function () {
	return false;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TextNode );
