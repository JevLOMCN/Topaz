/*!
 * VisualEditor ContentEditable LeafNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable leaf node.
 *
 * Leaf nodes can not have any children.
 *
 * @abstract
 * @extends ve.ce.Node
 * @mixins ve.LeafNode
 *
 * @constructor
 * @param {ve.dm.LeafNode} model
 * @param {Object} [config]
 */
ve.ce.LeafNode = function VeCeLeafNode() {
	// Mixin constructor
	ve.LeafNode.call( this );

	// Parent constructor
	ve.ce.LeafNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.LeafNode, ve.ce.Node );

OO.mixinClass( ve.ce.LeafNode, ve.LeafNode );

/* Static Properties */

ve.ce.LeafNode.static.tagName = 'span';

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.LeafNode.prototype.initialize = function () {
	// Parent method
	ve.ce.LeafNode.super.prototype.initialize.call( this );

	if ( this.model.isWrapped() ) {
		this.$element.addClass( 've-ce-leafNode' );
	}
};

/**
 * Get annotated HTML fragments.
 *
 * @see ve.ce.ContentBranchNode
 *
 * An HTML fragment can be:
 * - a plain text string
 * - a jQuery object
 * - an array with a plain text string or jQuery object at index 0 and a ve.dm.AnnotationSet at index 1,
 *   i.e. ['textstring', ve.dm.AnnotationSet] or [$jQueryObj, ve.dm.AnnotationSet]
 *
 * The default implementation should be fine in most cases. A subclass only needs to override this
 * if the annotations aren't necessarily the same across the entire node (like in ve.ce.TextNode).
 *
 * @return {Array} Array of HTML fragments, i.e.
 *  [ string | jQuery | [string|jQuery, ve.dm.AnnotationSet] ]
 */
ve.ce.LeafNode.prototype.getAnnotatedHtml = function () {
	return [ [ this.$element, this.getModel().getAnnotations() ] ];
};
