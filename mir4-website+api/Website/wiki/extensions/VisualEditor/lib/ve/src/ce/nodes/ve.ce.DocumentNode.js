/*!
 * VisualEditor ContentEditable DocumentNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable document node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @mixins ve.ce.ContentEditableNode
 * @constructor
 * @param {ve.dm.DocumentNode} model Model to observe
 * @param {ve.ce.Surface} surface Surface document is part of
 * @param {Object} [config] Configuration options
 */
ve.ce.DocumentNode = function VeCeDocumentNode( model, surface, config ) {
	// Properties
	this.surface = surface;

	// Parent constructor
	ve.ce.DocumentNode.super.call( this, model, config );

	// Mixin constructor
	ve.ce.ContentEditableNode.call( this );

	// Set root
	this.setRoot( this );

	// DOM changes
	// TODO: Remove ve-ce-rootNode class
	this.$element
		.addClass( 've-ce-documentNode ve-ce-attachedRootNode ve-ce-rootNode' )
		.attr( 'tabindex', 0 );
	// Prevent Grammarly from polluting the DOM (T165746)
	this.$element.attr( 'data-gramm', 'false' );

	this.$element.attr( 'role', 'textbox' );
};

/* Inheritance */

OO.inheritClass( ve.ce.DocumentNode, ve.ce.BranchNode );
OO.mixinClass( ve.ce.DocumentNode, ve.ce.ContentEditableNode );

/* Events */

/* Static Properties */

ve.ce.DocumentNode.static.name = 'document';

/* Methods */

/**
 * Get the outer length.
 *
 * For a document node is the same as the inner length, which is why we override it here.
 *
 * @return {number} Length of the entire node
 */
ve.ce.DocumentNode.prototype.getOuterLength = function () {
	return this.length;
};

/**
 * Get the surface the document is attached to.
 *
 * @return {ve.ce.Surface} Surface the document is attached to
 */
ve.ce.DocumentNode.prototype.getSurface = function () {
	return this.surface;
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.DocumentNode );
