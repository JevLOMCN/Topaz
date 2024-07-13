/*!
 * VisualEditor ContentEditable ClassAttributeNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable class-attribute node.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {jQuery} [$classedElement=this.$element] Element to which attribute-based classes are attached
 */
ve.ce.ClassAttributeNode = function VeCeClassAttributeNode( $classedElement ) {
	// Properties
	this.$classedElement = $classedElement || this.$element;
	this.currentAttributeClasses = '';

	// eslint-disable-next-line mediawiki/class-doc
	this.$classedElement
		// Clear all but unrecognized classes. Attributes classes will be applied
		// correctly on setup.
		.removeClass( this.getModel().getAttribute( 'originalClasses' ) )
		.addClass( this.getModel().getAttribute( 'unrecognizedClasses' ) );

	// Events
	this.connect( this, { setup: 'updateAttributeClasses' } );
	this.model.connect( this, { attributeChange: 'updateAttributeClasses' } );
};

/* Inheritance */

OO.initClass( ve.ce.ClassAttributeNode );

/**
 * Update classes from attributes
 */
ve.ce.ClassAttributeNode.prototype.updateAttributeClasses = function () {
	// eslint-disable-next-line mediawiki/class-doc
	this.$classedElement.removeClass( this.currentAttributeClasses );
	this.currentAttributeClasses = this.model.constructor.static.getClassAttrFromAttributes( this.model.element.attributes );
	// eslint-disable-next-line mediawiki/class-doc
	this.$classedElement.addClass( this.currentAttributeClasses );
};
