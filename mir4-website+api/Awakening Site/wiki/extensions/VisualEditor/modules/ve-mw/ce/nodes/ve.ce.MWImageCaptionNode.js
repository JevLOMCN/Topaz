/*!
 * VisualEditor ContentEditable ImageCaptionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable image caption node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @mixins ve.ce.ActiveNode
 *
 * @constructor
 * @param {ve.dm.MWImageCaptionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWImageCaptionNode = function VeCeMWImageCaptionNode() {
	// Parent constructor
	ve.ce.MWImageCaptionNode.super.apply( this, arguments );

	// Mixin constructor
	ve.ce.ActiveNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWImageCaptionNode, ve.ce.BranchNode );

OO.mixinClass( ve.ce.MWImageCaptionNode, ve.ce.ActiveNode );

/* Static Properties */

ve.ce.MWImageCaptionNode.static.name = 'mwImageCaption';

ve.ce.MWImageCaptionNode.static.tagName = 'figcaption';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWImageCaptionNode );
