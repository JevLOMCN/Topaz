/*!
 * VisualEditor ContentEditable AbbreviationAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable abbreviation annotation.
 *
 * @class
 * @extends ve.ce.TextStyleAnnotation
 * @constructor
 * @param {ve.dm.AbbreviationAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.AbbreviationAnnotation = function VeCeAbbreviationAnnotation() {
	// Parent constructor
	ve.ce.AbbreviationAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-abbreviationAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.AbbreviationAnnotation, ve.ce.TextStyleAnnotation );

/* Static Properties */

ve.ce.AbbreviationAnnotation.static.name = 'textStyle/abbreviation';

ve.ce.AbbreviationAnnotation.static.tagName = 'abbr';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.AbbreviationAnnotation );
