/*!
 * VisualEditor ContentEditable SpanAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable span annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.SpanAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.SpanAnnotation = function VeCeSpanAnnotation() {
	// Parent constructor
	ve.ce.SpanAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-spanAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.SpanAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.SpanAnnotation.static.name = 'textStyle/span';

ve.ce.SpanAnnotation.static.tagName = 'span';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.SpanAnnotation );
