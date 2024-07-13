/*!
 * VisualEditor ContentEditable GalleryImageCaptionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable gallery image caption node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @mixins ve.ce.ActiveNode
 *
 * @constructor
 * @param {ve.dm.MWGalleryImageCaptionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWGalleryImageCaptionNode = function VeCeMWGalleryImageCaptionNode() {
	// Parent constructor
	ve.ce.MWGalleryImageCaptionNode.super.apply( this, arguments );

	// Mixin constructor
	ve.ce.ActiveNode.call( this );

	this.$element.addClass( 'gallerytext' );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWGalleryImageCaptionNode, ve.ce.BranchNode );

OO.mixinClass( ve.ce.MWGalleryImageCaptionNode, ve.ce.ActiveNode );

/* Static Properties */

ve.ce.MWGalleryImageCaptionNode.static.name = 'mwGalleryImageCaption';

ve.ce.MWGalleryImageCaptionNode.static.tagName = 'div';

/* Methods */

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWGalleryImageCaptionNode );
