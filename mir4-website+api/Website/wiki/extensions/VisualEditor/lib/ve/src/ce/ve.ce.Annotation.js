/*!
 * VisualEditor ContentEditable Annotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic ContentEditable annotation.
 *
 * This is an abstract class, annotations should extend this and call this constructor from their
 * constructor. You should not instantiate this class directly.
 *
 * Subclasses of ve.dm.Annotation should have a corresponding subclass here that controls rendering.
 *
 * @abstract
 * @extends ve.ce.View
 *
 * @constructor
 * @param {ve.dm.Annotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.Annotation = function VeCeAnnotation( model, parentNode, config ) {
	// Parent constructor
	ve.ce.Annotation.super.call( this, model, config );

	// Properties
	this.parentNode = parentNode || null;

	this.$element
		// Make data available before setup
		.data( 'view', this )
		.addClass( 've-ce-annotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.Annotation, ve.ce.View );

/* Static Properties */

ve.ce.Annotation.static.tagName = 'span';

/**
 * Annotations which can be active get a ve-ce-annotation-active class when focused.
 */
ve.ce.Annotation.static.canBeActive = false;

/* Static Methods */

/**
 * Get a plain text description.
 *
 * @static
 * @inheritable
 * @param {ve.dm.Annotation} annotation Annotation model
 * @return {string} Description of annotation
 */
ve.ce.Annotation.static.getDescription = function () {
	return '';
};

/* Methods */

/**
 * Get the content branch node this annotation is rendered in, if any.
 *
 * @return {ve.ce.ContentBranchNode|null} Content branch node or null if none
 */
ve.ce.Annotation.prototype.getParentNode = function () {
	return this.parentNode;
};

/**
 * @inheritdoc
 */
ve.ce.Annotation.prototype.getModelHtmlDocument = function () {
	return this.parentNode && this.parentNode.getModelHtmlDocument();
};

/**
 * Append a child node to the annotation
 *
 * @param {Node} childNode Child node to append
 */
ve.ce.Annotation.prototype.appendChild = function ( childNode ) {
	this.$element[ 0 ].appendChild( childNode );
};

/**
 * Get the container into which annotation contents should be appended
 *
 * @return {HTMLElement} Content container
 */
ve.ce.Annotation.prototype.getContentContainer = function () {
	return this.$element[ 0 ];
};

/**
 * Attach completed contents to the annotation as descendant nodes, if not already attached
 *
 * No further contents should be appended into the content container after calling this
 */
ve.ce.Annotation.prototype.attachContents = function () {
	// Do nothing; already attached
};

/**
 * Append the completed annotation to a parent node
 *
 * #attachContents should have been called first
 *
 * @param {Node} node Parent node
 */
ve.ce.Annotation.prototype.appendTo = function ( node ) {
	node.appendChild( this.$element[ 0 ] );
};

/**
 * Check if the annotation can be active
 *
 * @return {boolean}
 */
ve.ce.Annotation.prototype.canBeActive = function () {
	return this.constructor.static.canBeActive;
};

/**
 * Release all memory
 */
ve.ce.Annotation.prototype.destroy = function () {
	this.parentNode = null;

	// Parent method
	ve.ce.Annotation.super.prototype.destroy.call( this );
};
