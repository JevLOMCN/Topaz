/*!
 * VisualEditor ContentEditable block image node class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable block image node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @mixins ve.ce.ImageNode
 * @mixins ve.ce.AlignableNode
 *
 * @constructor
 * @param {ve.dm.BlockImageNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.BlockImageNode = function VeCeBlockImageNode( model, config ) {
	config = ve.extendObject( {
		minDimensions: { width: 1, height: 1 }
	}, config );

	// Parent constructor
	ve.ce.BlockImageNode.super.call( this, model, config );

	// Build DOM
	this.$image = $( '<img>' )
		.prop( 'src', this.getResolvedAttribute( 'src' ) )
		.prependTo( this.$element );

	// Mixin constructors
	ve.ce.ImageNode.call( this, this.$image, this.$image, config );
	ve.ce.AlignableNode.call( this, this.$element, config );

	// Initialization
	this.$element.addClass( 've-ce-blockImageNode' );
	this.$image
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

OO.inheritClass( ve.ce.BlockImageNode, ve.ce.BranchNode );

OO.mixinClass( ve.ce.BlockImageNode, ve.ce.ImageNode );

// Mixin Alignable's parent class
OO.mixinClass( ve.ce.BlockImageNode, ve.ce.ClassAttributeNode );

OO.mixinClass( ve.ce.BlockImageNode, ve.ce.AlignableNode );

/* Static Properties */

ve.ce.BlockImageNode.static.name = 'blockImage';

ve.ce.BlockImageNode.static.tagName = 'figure';

ve.ce.BlockImageNode.static.autoFocus = false;

/* Registration */

ve.ce.nodeFactory.register( ve.ce.BlockImageNode );
