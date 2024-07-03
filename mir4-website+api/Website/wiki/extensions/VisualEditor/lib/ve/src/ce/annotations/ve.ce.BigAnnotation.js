/*!
 * VisualEditor ContentEditable BigAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable big annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.BigAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.BigAnnotation = function VeCeBigAnnotation() {
	// Parent constructor
	ve.ce.BigAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-bigAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.BigAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.BigAnnotation.static.name = 'textStyle/big';

ve.ce.BigAnnotation.static.tagName = 'big';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.BigAnnotation );
