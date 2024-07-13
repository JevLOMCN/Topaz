/*!
 * VisualEditor DataModel InlineImageNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel inline image node.
 *
 * @class
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.ImageNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.InlineImageNode = function VeDmInlineImageNode() {
	// Parent constructor
	ve.dm.InlineImageNode.super.apply( this, arguments );

	// Mixin constructor
	ve.dm.ImageNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.InlineImageNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.InlineImageNode, ve.dm.ImageNode );

/* Static Properties */

ve.dm.InlineImageNode.static.name = 'inlineImage';

ve.dm.InlineImageNode.static.isContent = true;

ve.dm.InlineImageNode.static.matchTagNames = [ 'img' ];

ve.dm.InlineImageNode.static.toDataElement = function ( domElements ) {
	var domElement = domElements[ 0 ],
		alt = domElement.getAttribute( 'alt' ),
		width = domElement.getAttribute( 'width' ),
		height = domElement.getAttribute( 'height' );

	return {
		type: this.name,
		attributes: {
			src: domElement.getAttribute( 'src' ),
			alt: alt,
			width: width !== null && width !== '' ? +width : null,
			height: height !== null && height !== '' ? +height : null
		}
	};
};

ve.dm.InlineImageNode.static.toDomElements = function ( dataElement, doc ) {
	var domElement = doc.createElement( 'img' );
	ve.setDomAttributes( domElement, dataElement.attributes, [ 'alt', 'src', 'width', 'height' ] );
	return [ domElement ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.InlineImageNode );
