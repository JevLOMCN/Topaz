/*!
 * VisualEditor ContentEditable MWBlockExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki block extension node.
 *
 * @class
 * @abstract
 * @extends ve.ce.MWExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWBlockExtensionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWBlockExtensionNode = function VeCeMWBlockExtensionNode() {
	// Parent constructor
	ve.ce.MWBlockExtensionNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWBlockExtensionNode, ve.ce.MWExtensionNode );
