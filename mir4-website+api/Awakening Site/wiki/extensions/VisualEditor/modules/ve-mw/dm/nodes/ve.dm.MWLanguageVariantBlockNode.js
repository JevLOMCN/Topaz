/*!
 * VisualEditor DataModel MWLanguageVariantBlockNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki language variant block node.
 *
 * @class
 * @extends ve.dm.MWLanguageVariantNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWLanguageVariantBlockNode = function VeDmMWLanguageVariantBlockNode() {
	// Parent constructor
	ve.dm.MWLanguageVariantBlockNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.MWLanguageVariantBlockNode, ve.dm.MWLanguageVariantNode );

ve.dm.MWLanguageVariantBlockNode.static.matchTagNames = [ 'div' ];

ve.dm.MWLanguageVariantBlockNode.static.name = 'mwLanguageVariantBlock';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWLanguageVariantBlockNode );
