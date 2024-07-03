/*!
 * VisualEditor ContentEditable MWLanguageVariantHiddenNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki language variant hidden node.
 *
 * @class
 * @extends ve.ce.MWLanguageVariantNode
 *
 * @constructor
 * @param {ve.dm.MWLanguageVariantHiddenNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWLanguageVariantHiddenNode = function VeCeMWLanguageVariantHiddenNode() {
	// Parent constructor
	ve.ce.MWLanguageVariantHiddenNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWLanguageVariantHiddenNode, ve.ce.MWLanguageVariantNode );

ve.ce.MWLanguageVariantHiddenNode.static.name = 'mwLanguageVariantHidden';

ve.ce.MWLanguageVariantHiddenNode.static.tagName = 'span';

ve.ce.MWLanguageVariantHiddenNode.prototype.appendHolder = function () {
	// No holder for a hidden node.
	return null;
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWLanguageVariantHiddenNode );
