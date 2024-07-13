/*!
 * VisualEditor ContentEditable MWLanguageVariantInlineNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki language variant inline node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.MWLanguageVariantNode
 *
 * @constructor
 * @param {ve.dm.MWLanguageVariantInlineNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWLanguageVariantInlineNode = function VeCeMWLanguageVariantInlineNode() {
	// Parent constructor
	ve.ce.MWLanguageVariantInlineNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWLanguageVariantInlineNode, ve.ce.MWLanguageVariantNode );

ve.ce.MWLanguageVariantInlineNode.static.name = 'mwLanguageVariantInline';

ve.ce.MWLanguageVariantInlineNode.static.tagName = 'span';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWLanguageVariantInlineNode );
