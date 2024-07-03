/*!
 * VisualEditor ContentEditable ParagraphNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable paragraph node.
 *
 * @class
 * @extends ve.ce.ContentBranchNode
 * @constructor
 * @param {ve.dm.ParagraphNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.ParagraphNode = function VeCeParagraphNode() {
	// Parent constructor
	ve.ce.ParagraphNode.super.apply( this, arguments );

	// DOM changes
	if (
		this.model.getElement().internal &&
		this.model.getElement().internal.generated === 'wrapper'
	) {
		this.$element.addClass( 've-ce-generated-wrapper' );
	}
};

/* Inheritance */

OO.inheritClass( ve.ce.ParagraphNode, ve.ce.ContentBranchNode );

/* Static Properties */

ve.ce.ParagraphNode.static.name = 'paragraph';

ve.ce.ParagraphNode.static.tagName = 'p';

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.ParagraphNode.prototype.initialize = function () {
	ve.ce.ParagraphNode.super.prototype.initialize.call( this );
	this.$element.addClass( 've-ce-paragraphNode' );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.ParagraphNode );
