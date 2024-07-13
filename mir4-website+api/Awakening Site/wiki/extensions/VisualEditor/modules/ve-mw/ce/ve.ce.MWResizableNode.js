/*!
 * VisualEditor ContentEditable MWResizableNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki resizable node.
 *
 * @class
 * @abstract
 * @extends ve.ce.ResizableNode
 *
 * @constructor
 * @param {jQuery} [$resizable=this.$element] Resizable DOM element
 * @param {Object} [config] Configuration options
 */
ve.ce.MWResizableNode = function VeCeMWResizableNode( $resizable, config ) {
	ve.ce.ResizableNode.call( this, $resizable, config );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWResizableNode, ve.ce.ResizableNode );

/**
 * Generate an object of attributes changes from the new width and height.
 *
 * If either property changes, clear the defaultSize flag.
 *
 * @param {number} width New image width
 * @param {number} height New image height
 * @return {Object} Attribute changes
 */
ve.ce.MWResizableNode.prototype.getAttributeChanges = function ( width, height ) {
	var attrChanges = ve.ce.ResizableNode.prototype.getAttributeChanges.call( this, width, height );
	if ( !ve.isEmptyObject( attrChanges ) ) {
		attrChanges.defaultSize = false;
	}
	// type=frame is not resizeable, so change it to type=thumb
	if ( this.getModel().getAttribute( 'type' ) === 'frame' ) {
		attrChanges.type = 'thumb';
	}
	return attrChanges;
};
