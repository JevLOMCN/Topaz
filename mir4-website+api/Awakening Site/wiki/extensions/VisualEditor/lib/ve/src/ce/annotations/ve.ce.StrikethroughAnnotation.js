/*!
 * VisualEditor ContentEditable StrikethroughAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable strikethrough annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.StrikethroughAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.StrikethroughAnnotation = function VeCeStrikethroughAnnotation() {
	// Parent constructor
	ve.ce.StrikethroughAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-strikethroughAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.StrikethroughAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.StrikethroughAnnotation.static.name = 'textStyle/strikethrough';

ve.ce.StrikethroughAnnotation.static.tagName = 's';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.StrikethroughAnnotation );
