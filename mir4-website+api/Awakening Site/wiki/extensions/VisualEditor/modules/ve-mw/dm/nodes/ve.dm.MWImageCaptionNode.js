/*!
 * VisualEditor DataModel MWImageCaptionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel image caption item node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWImageCaptionNode = function VeDmMWImageCaptionNode() {
	// Parent constructor
	ve.dm.MWImageCaptionNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.MWImageCaptionNode, ve.dm.BranchNode );

ve.dm.MWImageCaptionNode.static.name = 'mwImageCaption';

ve.dm.MWImageCaptionNode.static.matchTagNames = [];

ve.dm.MWImageCaptionNode.static.parentNodeTypes = [ 'mwBlockImage' ];

ve.dm.MWImageCaptionNode.static.toDomElements = function ( dataElement, doc ) {
	return [ doc.createElement( 'figcaption' ) ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWImageCaptionNode );
