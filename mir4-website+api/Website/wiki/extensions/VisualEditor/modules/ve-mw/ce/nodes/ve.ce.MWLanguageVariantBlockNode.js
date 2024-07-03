/*!
 * VisualEditor ContentEditable MWLanguageVariantBlockNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki language variant block node.
 *
 * @class
 * @extends ve.ce.MWLanguageVariantNode
 *
 * @constructor
 * @param {ve.dm.MWLanguageVariantBlockNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWLanguageVariantBlockNode = function VeCeMWLanguageVariantBlockNode() {
	// Parent constructor
	ve.ce.MWLanguageVariantBlockNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWLanguageVariantBlockNode, ve.ce.MWLanguageVariantNode );

ve.ce.MWLanguageVariantBlockNode.static.name = 'mwLanguageVariantBlock';

ve.ce.MWLanguageVariantBlockNode.static.tagName = 'div';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWLanguageVariantBlockNode );
