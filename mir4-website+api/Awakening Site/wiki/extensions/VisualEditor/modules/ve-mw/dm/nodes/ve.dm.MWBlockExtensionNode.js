/*!
 * VisualEditor DataModel MWBlockExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki block extension node.
 *
 * @class
 * @abstract
 * @extends ve.dm.MWExtensionNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWBlockExtensionNode = function VeDmMWBlockExtensionNode() {
	// Parent constructor
	ve.dm.MWBlockExtensionNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWBlockExtensionNode, ve.dm.MWExtensionNode );

/* Static members */

ve.dm.MWBlockExtensionNode.static.tagName = 'div';
