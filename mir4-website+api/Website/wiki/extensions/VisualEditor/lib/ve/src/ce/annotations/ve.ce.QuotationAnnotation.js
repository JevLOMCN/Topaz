/*!
 * VisualEditor ContentEditable QuotationAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable quotation annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.QuotationAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.QuotationAnnotation = function VeCeQuotationAnnotation() {
	// Parent constructor
	ve.ce.QuotationAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-quotationAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.QuotationAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.QuotationAnnotation.static.name = 'textStyle/quotation';

ve.ce.QuotationAnnotation.static.tagName = 'q';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.QuotationAnnotation );
