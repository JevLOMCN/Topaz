/*!
 * VisualEditor ContentEditable CodeAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable code annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.CodeAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.CodeAnnotation = function VeCeCodeAnnotation() {
	// Parent constructor
	ve.ce.CodeAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-codeAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.CodeAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.CodeAnnotation.static.name = 'textStyle/code';

ve.ce.CodeAnnotation.static.tagName = 'code';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.CodeAnnotation );
