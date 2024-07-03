/*!
 * VisualEditor DataModel ArticleNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel article node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.ArticleNode = function VeDmArticleNode() {
	// Parent constructor
	ve.dm.ArticleNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.ArticleNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.ArticleNode.static.name = 'article';

ve.dm.ArticleNode.static.isUnwrappable = false;

ve.dm.ArticleNode.static.matchTagNames = [ 'article' ];

/* Methods */

ve.dm.ArticleNode.prototype.canHaveSlugBefore = function () {
	return false;
};

ve.dm.ArticleNode.prototype.canHaveSlugAfter = function () {
	return false;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.ArticleNode );
