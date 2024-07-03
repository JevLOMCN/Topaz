/*!
 * VisualEditor DataModel MWInlineExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki inline extension node.
 *
 * @class
 * @abstract
 * @extends ve.dm.MWExtensionNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWInlineExtensionNode = function VeDmMWInlineExtensionNode() {
	// Parent constructor
	ve.dm.MWInlineExtensionNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWInlineExtensionNode, ve.dm.MWExtensionNode );

/* Static members */

ve.dm.MWInlineExtensionNode.static.isContent = true;

ve.dm.MWInlineExtensionNode.static.tagName = 'span';
