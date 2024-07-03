/*!
 * VisualEditor ContentEditable MWMagicLinkNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki magic link node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.FocusableNode
 * @constructor
 * @param {ve.dm.MWMagicLinkNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWMagicLinkNode = function VeCeMWMagicLinkNode() {
	// Parent constructor
	ve.ce.MWMagicLinkNode.super.apply( this, arguments );

	// Mixin constructors
	ve.ce.FocusableNode.call( this );

	// DOM changes
	this.$element.addClass( 've-ce-mwMagicLinkNode' );

	// Add link
	this.$link = $( '<a>' ).appendTo( this.$element );

	// Events
	this.model.connect( this, { update: 'onUpdate' } );

	// Initialization
	this.onUpdate();
};

/* Inheritance */

OO.inheritClass( ve.ce.MWMagicLinkNode, ve.ce.LeafNode );

OO.mixinClass( ve.ce.MWMagicLinkNode, ve.ce.FocusableNode );

/* Static Properties */

ve.ce.MWMagicLinkNode.static.name = 'link/mwMagic';

ve.ce.MWMagicLinkNode.static.tagName = 'span';

ve.ce.MWMagicLinkNode.static.primaryCommandName = 'link';

/* Static Methods */

/**
 * @inheritdoc
 */
ve.ce.MWMagicLinkNode.static.getDescription = function ( model ) {
	return model.getAttribute( 'content' );
};

/* Methods */

/**
 * Handle model update events.
 */
ve.ce.MWMagicLinkNode.prototype.onUpdate = function () {
	this.$link
		.attr( 'rel', this.model.getRel() )
		.text( this.model.getAttribute( 'content' ) );

	// T322704
	ve.setAttributeSafe( this.$link[ 0 ], 'href', this.model.getHref(), '#' );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWMagicLinkNode );
