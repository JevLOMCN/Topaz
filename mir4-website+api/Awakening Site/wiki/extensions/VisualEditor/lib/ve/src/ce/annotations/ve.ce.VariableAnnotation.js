/*!
 * VisualEditor ContentEditable VariableAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable variable annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.VariableAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.VariableAnnotation = function VeCeVariableAnnotation() {
	// Parent constructor
	ve.ce.VariableAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-variableAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.VariableAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.VariableAnnotation.static.name = 'textStyle/variable';

ve.ce.VariableAnnotation.static.tagName = 'var';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.VariableAnnotation );
