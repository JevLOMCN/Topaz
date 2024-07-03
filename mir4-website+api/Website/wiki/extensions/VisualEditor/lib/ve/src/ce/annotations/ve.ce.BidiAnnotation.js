/*!
 * VisualEditor ContentEditable BidiAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable bi-di annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.BidiAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.BidiAnnotation = function VeCeBidiAnnotation() {
	// Parent constructor
	ve.ce.BidiAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-bidiAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.BidiAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.BidiAnnotation.static.name = 'textStyle/bidi';

ve.ce.BidiAnnotation.static.tagName = 'bdi';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.BidiAnnotation );
