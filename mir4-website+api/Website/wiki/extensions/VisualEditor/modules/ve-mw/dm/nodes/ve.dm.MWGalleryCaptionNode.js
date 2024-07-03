/*!
 * VisualEditor DataModel MWGalleryCaptionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel gallery caption node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWGalleryCaptionNode = function VeDmMWGalleryCaptionNode() {
	// Parent constructor
	ve.dm.MWGalleryCaptionNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.MWGalleryCaptionNode, ve.dm.BranchNode );

ve.dm.MWGalleryCaptionNode.static.name = 'mwGalleryCaption';

ve.dm.MWGalleryCaptionNode.static.matchTagNames = [ 'li' ];

ve.dm.MWGalleryCaptionNode.static.matchFunction = function ( element ) {
	var parentTypeof = ( element.parentNode && element.parentNode.getAttribute( 'typeof' ) ) || '';
	return element.getAttribute( 'class' ) === 'gallerycaption' &&
		parentTypeof.trim().split( /\s+/ ).indexOf( 'mw:Extension/gallery' ) !== -1;
};

ve.dm.MWGalleryCaptionNode.static.parentNodeTypes = [ 'mwGallery' ];

ve.dm.MWGalleryCaptionNode.static.toDomElements = function ( dataElement, doc ) {
	var li = doc.createElement( 'li' );
	li.classList.add( 'gallerycaption' );
	return [ li ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWGalleryCaptionNode );
