/*!
 * VisualEditor DataModel QuotationAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel quotation annotation.
 *
 * Represents `<q>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.QuotationAnnotation = function VeDmQuotationAnnotation() {
	// Parent constructor
	ve.dm.QuotationAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.QuotationAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.QuotationAnnotation.static.name = 'textStyle/quotation';

ve.dm.QuotationAnnotation.static.matchTagNames = [ 'q' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.QuotationAnnotation );
