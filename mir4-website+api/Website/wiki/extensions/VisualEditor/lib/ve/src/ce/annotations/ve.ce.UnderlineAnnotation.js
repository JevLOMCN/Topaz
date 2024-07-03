/*!
 * VisualEditor ContentEditable UnderlineAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable underline annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.UnderlineAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.UnderlineAnnotation = function VeCeUnderlineAnnotation() {
	// Parent constructor
	ve.ce.UnderlineAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-underlineAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.UnderlineAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.UnderlineAnnotation.static.name = 'textStyle/underline';

ve.ce.UnderlineAnnotation.static.tagName = 'u';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.UnderlineAnnotation );
