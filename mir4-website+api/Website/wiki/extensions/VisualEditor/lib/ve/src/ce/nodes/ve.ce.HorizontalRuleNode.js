/*!
 * VisualEditor ContentEditable HorizontalRuleNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable horizontal rule node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.FocusableNode
 *
 * @constructor
 * @param {ve.dm.HorizontalRuleNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.HorizontalRuleNode = function VeCeHorizontalRuleNode() {
	// Parent constructor
	ve.ce.HorizontalRuleNode.super.apply( this, arguments );

	// Wrap the <hr> in a div so the margins become focusable
	// and the user has a click target of more than 2px
	this.$element = $( '<div>' ).append( this.$element );

	// Mixin constructors
	ve.ce.FocusableNode.call( this );

	// DOM changes
	this.$element.addClass( 've-ce-horizontalRuleNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.HorizontalRuleNode, ve.ce.LeafNode );

OO.mixinClass( ve.ce.HorizontalRuleNode, ve.ce.FocusableNode );

/* Static Properties */

ve.ce.HorizontalRuleNode.static.name = 'horizontalRule';

ve.ce.HorizontalRuleNode.static.tagName = 'hr';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.HorizontalRuleNode );
