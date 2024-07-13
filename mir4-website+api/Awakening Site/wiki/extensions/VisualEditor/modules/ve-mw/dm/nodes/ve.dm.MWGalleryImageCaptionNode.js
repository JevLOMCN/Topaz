/*!
 * VisualEditor DataModel MWGalleryImageCaptionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel gallery image caption node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWGalleryImageCaptionNode = function VeDmMWGalleryImageCaptionNode() {
	// Parent constructor
	ve.dm.MWGalleryImageCaptionNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.MWGalleryImageCaptionNode, ve.dm.BranchNode );

ve.dm.MWGalleryImageCaptionNode.static.name = 'mwGalleryImageCaption';

ve.dm.MWGalleryImageCaptionNode.static.matchTagNames = [];

ve.dm.MWGalleryImageCaptionNode.static.parentNodeTypes = [ 'mwGalleryImage' ];

ve.dm.MWGalleryImageCaptionNode.static.toDomElements = function ( dataElement, doc ) {
	var div = doc.createElement( 'div' );
	div.classList.add( 'gallerytext' );
	return [ div ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWGalleryImageCaptionNode );
