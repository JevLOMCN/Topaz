/*!
 * VisualEditor DataModel AbbreviationAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel abbreviation annotation.
 *
 * Represents `<abbr>` tags.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.AbbreviationAnnotation = function VeDmAbbreviationAnnotation() {
	// Parent constructor
	ve.dm.AbbreviationAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.AbbreviationAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.AbbreviationAnnotation.static.name = 'textStyle/abbreviation';

ve.dm.AbbreviationAnnotation.static.matchTagNames = [ 'abbr' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.AbbreviationAnnotation );
