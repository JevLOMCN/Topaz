/*!
 * VisualEditor DataModel MWGalleryNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki gallery node.
 *
 * @class
 * @extends ve.dm.BranchNode
 * @mixins ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWGalleryNode = function VeDmMWGalleryNode() {
	// Parent constructor
	ve.dm.MWGalleryNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWGalleryNode, ve.dm.BranchNode );

OO.mixinClass( ve.dm.MWGalleryNode, ve.dm.FocusableNode );

/* Static members */

ve.dm.MWGalleryNode.static.name = 'mwGallery';

ve.dm.MWGalleryNode.static.matchRdfaTypes = [ 'mw:Extension/gallery' ];

ve.dm.MWGalleryNode.static.matchTagNames = [ 'ul' ];

ve.dm.MWGalleryNode.static.childNodeTypes = [ 'mwGalleryCaption', 'mwGalleryImage' ];

ve.dm.MWGalleryNode.static.disallowedAnnotationTypes = [ 'link' ];

ve.dm.MWGalleryNode.static.cloneElement = function () {
	// Parent method
	var clone = ve.dm.LeafNode.static.cloneElement.apply( this, arguments );
	delete clone.attributes.originalMw;
	return clone;
};

ve.dm.MWGalleryNode.static.getHashObject = function ( dataElement ) {
	return {
		type: dataElement.type,
		mw: ve.copy( dataElement.attributes.mw )
	};
};

ve.dm.MWGalleryNode.static.toDataElement = function ( domElements ) {
	var mwDataJSON = domElements[ 0 ].getAttribute( 'data-mw' ),
		mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};

	return {
		type: this.name,
		attributes: {
			mw: mwData,
			originalMw: mwDataJSON
		}
	};
};

ve.dm.MWGalleryNode.static.toDomElements = function ( data, doc ) {
	var ul = doc.createElement( 'ul' );

	// Build ul
	ul.setAttribute( 'typeof', 'mw:Extension/gallery' );
	ul.setAttribute( 'data-mw', JSON.stringify( data.attributes.mw ) );

	return [ ul ];
};

ve.dm.MWGalleryNode.static.describeChanges = function ( attributeChanges, attributes, element ) {
	// Only do a comparison on the 'mw.attrs' attribute
	if ( attributeChanges.mw ) {
		return ve.dm.MWGalleryNode.super.static.describeChanges.call(
			this,
			ve.ui.DiffElement.static.compareAttributes( attributeChanges.mw.from.attrs || {}, attributeChanges.mw.to.attrs || {} ),
			attributes,
			element
		);
	}
	return [];
};

ve.dm.MWGalleryNode.static.describeChange = function ( key ) {
	// Caption diff is shown in the DOM
	if ( key === 'caption' ) {
		return null;
	}
	// Parent method
	return ve.dm.MWGalleryNode.super.static.describeChange.apply( this, arguments );
};

/* Methods */

ve.dm.MWGalleryNode.prototype.isDiffedAsDocument = function () {
	return true;
};

/**
 * Get the gallery's caption node.
 *
 * @return {ve.dm.MWImageCaptionNode|null} Caption node, if present
 */
ve.dm.MWGalleryNode.prototype.getCaptionNode = function () {
	var node = this.children[ 0 ];
	return node instanceof ve.dm.MWGalleryCaptionNode ? node : null;
};

/**
 * Get the gallery's image nodes.
 *
 * @return {ve.dm.MWGalleryImageNode[]} Gallery image nodes (may be empty if none are present)
 */
ve.dm.MWGalleryNode.prototype.getImageNodes = function () {
	var images = this.children.filter( function ( child ) {
		return child instanceof ve.dm.MWGalleryImageNode;
	} );
	return images;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWGalleryNode );
