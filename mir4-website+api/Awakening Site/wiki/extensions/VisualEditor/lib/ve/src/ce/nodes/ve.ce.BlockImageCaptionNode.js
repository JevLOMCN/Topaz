/*!
 * VisualEditor ContentEditable block image caption node class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable block image caption item node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @mixins ve.ce.ActiveNode
 *
 * @constructor
 * @param {ve.dm.BlockImageCaptionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.BlockImageCaptionNode = function VeCeBlockImageCaptionNode() {
	// Parent constructor
	ve.ce.BlockImageCaptionNode.super.apply( this, arguments );

	// Mixin constructor
	ve.ce.ActiveNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ce.BlockImageCaptionNode, ve.ce.BranchNode );

OO.mixinClass( ve.ce.BlockImageCaptionNode, ve.ce.ActiveNode );

/* Static Properties */

ve.ce.BlockImageCaptionNode.static.name = 'imageCaption';

ve.ce.BlockImageCaptionNode.static.tagName = 'figcaption';

ve.ce.BlockImageCaptionNode.static.isMultiline = false;

/* Registration */

ve.ce.nodeFactory.register( ve.ce.BlockImageCaptionNode );
