/*!
 * VisualEditor ContentEditable SuperscriptAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable superscript annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.SuperscriptAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.SuperscriptAnnotation = function VeCeSuperscriptAnnotation() {
	// Parent constructor
	ve.ce.SuperscriptAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-superscriptAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.SuperscriptAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.SuperscriptAnnotation.static.name = 'textStyle/superscript';

ve.ce.SuperscriptAnnotation.static.tagName = 'sup';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.SuperscriptAnnotation );
