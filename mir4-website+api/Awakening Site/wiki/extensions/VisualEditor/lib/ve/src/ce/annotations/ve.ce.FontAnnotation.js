/*!
 * VisualEditor ContentEditable FontAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable font annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.FontAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.FontAnnotation = function VeCeFontAnnotation() {
	// Parent constructor
	ve.ce.FontAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-fontAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.FontAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.FontAnnotation.static.name = 'textStyle/font';

ve.ce.FontAnnotation.static.tagName = 'font';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.FontAnnotation );
