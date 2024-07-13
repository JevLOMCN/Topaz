/*!
 * VisualEditor ContentEditable BoldAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable bold annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.BoldAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.BoldAnnotation = function VeCeBoldAnnotation() {
	// Parent constructor
	ve.ce.BoldAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-boldAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.BoldAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.BoldAnnotation.static.name = 'textStyle/bold';

ve.ce.BoldAnnotation.static.tagName = 'b';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.BoldAnnotation );
