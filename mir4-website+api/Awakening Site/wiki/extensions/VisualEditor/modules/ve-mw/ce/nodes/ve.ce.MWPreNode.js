/*!
 * VisualEditor ContentEditable MWPreNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki pre node.
 *
 * @class
 * @extends ve.ce.MWBlockExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWPreNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWPreNode = function VeCeMWPreNode() {
	// Parent constructor
	ve.ce.MWPreNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWPreNode, ve.ce.MWBlockExtensionNode );

/* Static Properties */

ve.ce.MWPreNode.static.name = 'mwPre';

ve.ce.MWPreNode.static.tagName = 'pre';

ve.ce.MWPreNode.static.primaryCommandName = 'pre';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWPreNode );
