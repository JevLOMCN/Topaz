/*!
 * VisualEditor ContentEditable ArticleNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable article node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.ArticleNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.ArticleNode = function VeCeArticleNode() {
	// Parent constructor
	ve.ce.ArticleNode.super.apply( this, arguments );

	this.$element
		.addClass( 've-ce-articleNode' )
		.prop( 'contentEditable', 'false' );
};

/* Inheritance */

OO.inheritClass( ve.ce.ArticleNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.ArticleNode.static.name = 'article';

ve.ce.ArticleNode.static.tagName = 'article';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.ArticleNode );
