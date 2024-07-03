/*!
 * VisualEditor ContentEditable CommentNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable comment node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.FocusableNode
 *
 * @constructor
 * @param {ve.dm.CommentNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.CommentNode = function VeCeCommentNode( model, config ) {
	// Parent constructor
	ve.ce.CommentNode.super.call( this, model, config );

	// Mixin constructors
	ve.ce.FocusableNode.call( this, this.$element, config );

	// Events
	this.model.connect( this, { attributeChange: 'onAttributeChange' } );

	// DOM changes
	this.$element.addClass( 've-ce-commentNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.CommentNode, ve.ce.LeafNode );
OO.mixinClass( ve.ce.CommentNode, ve.ce.FocusableNode );

/* Static Properties */

ve.ce.CommentNode.static.name = 'comment';

ve.ce.CommentNode.static.primaryCommandName = 'comment';

ve.ce.CommentNode.static.iconWhenInvisible = 'notice';

/* Static Methods */

/**
 * @inheritdoc
 */
ve.ce.CommentNode.static.getDescription = function ( model ) {
	return model.getAttribute( 'text' );
};

/**
 * Update the rendering of the 'text' attribute
 * when it changes in the model.
 *
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.ce.CommentNode.prototype.onAttributeChange = function ( key ) {
	if ( key === 'text' ) {
		this.updateInvisibleIconLabel();
	}
};

/* Method */

/**
 * @inheritdoc ve.ce.FocusableNode
 */
ve.ce.CommentNode.prototype.hasRendering = function () {
	// Comment nodes never have a rendering, don't bother with expensive DOM inspection
	return false;
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.CommentNode );
