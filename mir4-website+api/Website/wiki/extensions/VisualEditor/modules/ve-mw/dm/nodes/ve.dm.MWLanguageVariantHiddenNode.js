/*!
 * VisualEditor DataModel MWLanguageVariantHiddenNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki language variant hidden node.
 *
 * @class
 * @extends ve.dm.MWLanguageVariantNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWLanguageVariantHiddenNode = function VeDmMWLanguageVariantHiddenNode() {
	// Parent constructor
	ve.dm.MWLanguageVariantHiddenNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.MWLanguageVariantHiddenNode, ve.dm.MWLanguageVariantNode );

ve.dm.MWLanguageVariantHiddenNode.static.matchTagNames = [ 'meta' ];

ve.dm.MWLanguageVariantHiddenNode.static.name = 'mwLanguageVariantHidden';

ve.dm.MWLanguageVariantHiddenNode.static.isContent = true;

/**
 * @inheritdoc
 */
ve.dm.MWLanguageVariantHiddenNode.prototype.isHidden = function () {
	return true;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWLanguageVariantHiddenNode );
