/*!
 * VisualEditor ContentEditable DeleteAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable delete annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.DeleteAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.DeleteAnnotation = function VeCeDeleteAnnotation() {
	// Parent constructor
	ve.ce.DeleteAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-deleteAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.DeleteAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.DeleteAnnotation.static.name = 'textStyle/delete';

ve.ce.DeleteAnnotation.static.tagName = 'del';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.DeleteAnnotation );
