/*!
 * VisualEditor DataModel MWLanguageVariantInlineNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki language variant inline node.
 *
 * @class
 * @extends ve.dm.MWLanguageVariantNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWLanguageVariantInlineNode = function VeDmMWLanguageVariantInlineNode() {
	// Parent constructor
	ve.dm.MWLanguageVariantInlineNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.MWLanguageVariantInlineNode, ve.dm.MWLanguageVariantNode );

ve.dm.MWLanguageVariantInlineNode.static.matchTagNames = [ 'span' ];

ve.dm.MWLanguageVariantInlineNode.static.name = 'mwLanguageVariantInline';

ve.dm.MWLanguageVariantInlineNode.static.isContent = true;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWLanguageVariantInlineNode );
