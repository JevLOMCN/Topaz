/*!
 * VisualEditor ContentEditable DefinitionAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable definition annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.DefinitionAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.DefinitionAnnotation = function VeCeDefinitionAnnotation() {
	// Parent constructor
	ve.ce.DefinitionAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-definitionAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.DefinitionAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.DefinitionAnnotation.static.name = 'textStyle/definition';

ve.ce.DefinitionAnnotation.static.tagName = 'dfn';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.DefinitionAnnotation );
