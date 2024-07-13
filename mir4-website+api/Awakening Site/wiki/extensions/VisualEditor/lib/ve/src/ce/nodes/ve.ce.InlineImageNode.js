/*!
 * VisualEditor ContentEditable InlineImageNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable inline image node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.ImageNode
 * @mixins ve.ce.ResizableNode
 *
 * @constructor
 * @param {ve.dm.InlineImageNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.InlineImageNode = function VeCeInlineImageNode( model, config ) {
	config = ve.extendObject( {
		minDimensions: { width: 1, height: 1 }
	}, config );

	// Parent constructor
	ve.ce.InlineImageNode.super.call( this, model, config );

	// Mixin constructors
	ve.ce.ImageNode.call( this, this.$element, null, config );

	// Initialization
	this.$element
		.addClass( 've-ce-inlineImageNode' )
		.prop( {
			alt: this.model.getAttribute( 'alt' ),
			src: this.getResolvedAttribute( 'src' )
		} )
		.css( {
			width: this.model.getAttribute( 'width' ),
			height: this.model.getAttribute( 'height' )
		} );
};

/* Inheritance */

OO.inheritClass( ve.ce.InlineImageNode, ve.ce.LeafNode );

OO.mixinClass( ve.ce.InlineImageNode, ve.ce.ImageNode );

/* Static Properties */

ve.ce.InlineImageNode.static.name = 'inlineImage';

ve.ce.InlineImageNode.static.tagName = 'img';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.InlineImageNode );
