/*!
 * VisualEditor DataModel MWPreNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki pre node.
 *
 * @class
 * @extends ve.dm.MWBlockExtensionNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWPreNode = function VeDmMWPreNode() {
	// Parent constructor
	ve.dm.MWPreNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWPreNode, ve.dm.MWBlockExtensionNode );

/* Static members */

ve.dm.MWPreNode.static.name = 'mwPre';

ve.dm.MWPreNode.static.extensionName = 'pre';

ve.dm.MWPreNode.static.tagName = 'pre';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWPreNode );
