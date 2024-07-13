/*!
 * VisualEditor DataModel FontAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel font annotation.
 *
 * Represents `<font>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.FontAnnotation = function VeDmFontAnnotation() {
	// Parent constructor
	ve.dm.FontAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.FontAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.FontAnnotation.static.name = 'textStyle/font';

ve.dm.FontAnnotation.static.matchTagNames = [ 'font' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.FontAnnotation );
