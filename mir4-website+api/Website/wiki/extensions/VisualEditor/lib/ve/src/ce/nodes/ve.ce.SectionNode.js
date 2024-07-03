/*!
 * VisualEditor ContentEditable SectionNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable section node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @mixins ve.ce.ActiveNode
 * @constructor
 * @param {ve.dm.SectionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.SectionNode = function VeCeSectionNode() {
	// Parent constructor
	ve.ce.SectionNode.super.apply( this, arguments );

	// Events
	this.model.connect( this, { update: 'onUpdate' } );

	// Mixin constructors
	ve.ce.ActiveNode.call( this );

	// DOM changes
	this.$element.addClass( 've-ce-sectionNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.SectionNode, ve.ce.BranchNode );

OO.mixinClass( ve.ce.SectionNode, ve.ce.ActiveNode );

/* Static Properties */

ve.ce.SectionNode.static.name = 'section';

/* Methods */

/**
 * Get the HTML tag name.
 *
 * Tag name is selected based on the model's style attribute.
 *
 * @return {string} HTML tag name
 */
ve.ce.SectionNode.prototype.getTagName = function () {
	var style = this.model.getAttribute( 'style' );

	if ( this.model.constructor.static.matchTagNames.indexOf( style ) === -1 ) {
		throw new Error( 'Invalid style' );
	}
	return style;
};

/**
 * Handle model update events.
 *
 * If the style changed since last update the DOM wrapper will be replaced with an appropriate one.
 */
ve.ce.SectionNode.prototype.onUpdate = function () {
	this.updateTagName();
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.SectionNode );
